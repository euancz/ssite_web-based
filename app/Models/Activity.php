<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Stores activities with separate adviser-review and public-visibility states.
 */
class Activity extends Model
{
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const CONTENT_ACTIVE = 'active';
    public const CONTENT_ARCHIVED = 'archived';

    protected $table = 'activities';

    // SECURITY: The SQL table uses activity_id as its key instead of Laravel's default id.
    protected $primaryKey = 'activity_id';

    protected $keyType = 'int';

    // SECURITY: Ownership, status, rejection, and review fields are set only by trusted actions.
    protected $fillable = ['title', 'description', 'activity_date', 'location', 'image'];

    /** Cast optional event dates for consistent formatting in lists and detail pages. */
    protected function casts(): array
    {
        return ['activity_date' => 'date'];
    }

    /**
     * Return the database key used in activity URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'activity_id';
    }

    /**
     * Find the author through users.user_id; views label a missing author Former member.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'user_id');
    }

    /**
     * Find the adviser who last reviewed this activity.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id');
    }

    /** Limit activities to those awaiting adviser review. */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    /** Limit activities approved by an adviser. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    /** Limit activities rejected by an adviser. */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_REJECTED);
    }

    /** Limit activities currently in circulation. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('content_status', self::CONTENT_ACTIVE);
    }

    /** Limit activities shelved from public listings. */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('content_status', self::CONTENT_ARCHIVED);
    }

    /** Limit public results to approved activities that are active. */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->approved()->active();
    }

    /** Check whether adviser review is still required. */
    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /** Check whether an adviser has approved this activity. */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /** Check whether an adviser has rejected this activity. */
    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /** Check whether this activity is shelved from active listings. */
    public function isArchived(): bool
    {
        return $this->content_status === self::CONTENT_ARCHIVED;
    }

    /** Return the stored image URL or the established placeholder when its file is missing. */
    public function imageUrl(): string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . ltrim($this->image, '/'));
        }

        return asset(config('school.default_profile_picture', 'images/Wolf.png'));
    }
}
