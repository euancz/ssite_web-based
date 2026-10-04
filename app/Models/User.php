<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Stores authentication, role, and student information together on the users table.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    // SECURITY: User records use the schema's user_id key, not Laravel's default id.
    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

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
     * Identify student accounts for role-aware routes and views.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Identify officers, who may create posts and use the officer dashboard.
     */
    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    /**
     * Identify advisers, who can review posts and manage user roles.
     */
    public function isAdviser(): bool
    {
        return $this->role === 'adviser';
    }

    /**
     * Return whether this role is allowed to submit organization posts.
     */
    public function canPost(): bool
    {
        return $this->isOfficer() || $this->isAdviser();
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
        return match (true) {
            $this->isAdviser() => 'adviser.dashboard',
            $this->isOfficer() => 'officer.dashboard',
            default => 'home',
        };
    }
}