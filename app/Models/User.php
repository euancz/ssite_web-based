<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * Stores authentication, role, and student information together on the users table.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_OFFICER = 'officer';
    public const ROLE_ADVISER = 'adviser';
    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_OFFICER,
        self::ROLE_ADVISER,
        self::ROLE_SUPERADMIN,
    ];

    protected $table = 'users';

    // SECURITY: User records use the schema's user_id key, not Laravel's default id.
    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    // SECURITY: profile_picture is intentionally excluded from $fillable; only trusted picture actions set it.

    /*
    |--------------------------------------------------------------------------
    | Timestamps
    |--------------------------------------------------------------------------
    */

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    // SECURITY: Only profile fields submitted by the signed-in user are mass-assignable.
    // Identity, role, authentication, and officer fields are set through trusted server-side flows.
    protected $fillable = [
        'student_number',
        'name',
        'institute',
        'program',
        'year_level',
        'gender',
        'contact_number',
        'address',
    ];


    /*
    |--------------------------------------------------------------------------
    | Hidden
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    /**
     * Cast timestamps used for account creation, updates, and profile completion.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'profile_completed_at' => 'datetime',
        ];
    }

    /**
     * Return an existing public image URL, or null so views can safely show initials.
     */
    public function avatarUrl(): ?string
    {
        $picture = trim((string) $this->profile_picture);

        if ($picture === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $picture)) {
            return $picture;
        }

        // Check storage first so manually removed files never become broken image links.
        if (! Storage::disk('public')->exists($picture)) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? 0;

        return asset('storage/' . $picture) . '?v=' . $version . '-' . substr(sha1($picture), 0, 8);
    }

    /**
     * Return up to two initials for accounts without an available picture.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }

    /**
     * Identify student accounts for role-aware routes and views.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /** Identify officer accounts; position capabilities are checked separately. */
    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    /** Identify adviser accounts for role assignment. */
    public function isAdviser(): bool
    {
        return $this->role === self::ROLE_ADVISER;
    }

    /** Identify superadmin accounts for adviser role assignment. */
    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /** Return the configured position only when this account currently has the officer role. */
    public function position(): ?string
    {
        if (! $this->isOfficer()) {
            return null;
        }

        $stored = mb_strtolower(trim((string) $this->officer_position));
        foreach (array_keys(config('school.positions', [])) as $position) {
            if ($stored !== '' && mb_strtolower(trim((string) $position)) === $stored) {
                return $position;
            }
        }

        return null;
    }

    /** President authority follows the configured canonical position, not the stored label alone. */
    public function isPresident(): bool
    {
        return $this->position() === 'President';
    }

    /** Return whether this officer position may submit to the requested module. */
    public function canPostIn(string $module): bool
    {
        $position = $this->position();

        return $position !== null
            && in_array($module, config('school.positions.' . $position . '.modules', []), true);
    }

    /** Return only the modules granted by the current canonical officer position. */
    public function postableModules(): array
    {
        $position = $this->position();

        return $position === null ? [] : config('school.positions.' . $position . '.modules', []);
    }

    /** Apply the configured auto-approval flag only to positions marked for direct publication. */
    public function postsAutoApproved(): bool
    {
        $position = $this->position();

        return $position !== null
            && (bool) config('school.positions.' . $position . '.auto_approved', false)
            && (! $this->isPresident() || (bool) config('school.president_posts_auto_approved', true));
    }

    /**
     * Return the roles the current actor may assign to another user.
     */
    public function assignableRoles(): array
    {
        return $this->canAssignOfficers()
            ? [self::ROLE_STUDENT, self::ROLE_OFFICER]
            : ($this->canAssignAdvisers() ? [self::ROLE_STUDENT, self::ROLE_ADVISER] : []);
    }

    /** Only advisers assign student and officer roles and officer positions. */
    public function canAssignOfficers(): bool
    {
        return $this->isAdviser();
    }

    /** Only superadmins assign or revoke adviser roles. */
    public function canAssignAdvisers(): bool
    {
        return $this->isSuperadmin();
    }

    /**
     * Determine whether this account can review submitted content.
     */
    public function canReview(): bool
    {
        return $this->isPresident()
            && (bool) config('school.positions.' . $this->position() . '.can_review', false);
    }

    /** The President manages the public officer roster; advisers assign account roles separately. */
    public function canManageOfficers(): bool
    {
        return $this->isPresident();
    }

    /**
     * Determine whether this account can manage adviser assignments.
     */
    public function canManageAdvisers(): bool
    {
        return $this->canAssignAdvisers();
    }

    /**
     * Determine whether this account can permanently delete content.
     */
    public function canPermanentlyDelete(): bool
    {
        return $this->isPresident();
    }

    /** Return whether the current officer position can submit to any module. */
    public function canPost(): bool
    {
        return $this->postableModules() !== [];
    }

    /**
     * Tell the profile middleware whether required student information has been saved.
     */
    public function hasCompletedProfile(): bool
    {
        return $this->profile_completed_at !== null;
    }

    /**
     * Select the signed-in user's home destination from their stored role.
     */
    public function dashboardRoute(): string
    {
        if ($this->isSuperadmin() || $this->isAdviser()) {
            return 'adviser.dashboard';
        }

        return match (true) {
            $this->canPost() => 'officer.dashboard',
            default => 'home',
        };
    }
}
