<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Stores articles with separate review and publication visibility states.
 */
class Article extends Model
{
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const CONTENT_ACTIVE = 'active';
    public const CONTENT_ARCHIVED = 'archived';

    protected $table = 'articles';

    // SECURITY: The schema uses article_id as its primary key instead of Laravel's default id.
    protected $primaryKey = 'article_id';

    protected $keyType = 'int';

    // SECURITY: Status, ownership, and review fields can only be changed by trusted controller actions.
    protected $fillable = ['title', 'content', 'image'];

    /**
     * Return the schema key used for article URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'article_id';
    }

    /**
     * Find the author through the users table's custom user_id key; views use Former member when absent.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'user_id');
    }

    /**
     * Find the adviser who last reviewed this article.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id');
    }

    /**
     * Limit articles to those awaiting adviser review.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    /**
     * Limit articles to those approved by an adviser.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    /**
     * Limit articles rejected by an adviser.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_REJECTED);
    }

    /**
     * Limit articles that are currently in circulation.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('content_status', self::CONTENT_ACTIVE);
    }

    /**
     * Limit articles shelved from public listings.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('content_status', self::CONTENT_ARCHIVED);
    }

    /**
     * Limit public listings to approved articles that have not been archived.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->approved()->active();
    }

    /**
     * Check whether adviser review is still required.
     */
    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Check whether an adviser has approved the article.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Check whether an adviser has rejected the article.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /**
     * Check whether the article is shelved from active listings.
     */
    public function isArchived(): bool
    {
        return $this->content_status === self::CONTENT_ARCHIVED;
    }

    /**
     * Return a current-app storage URL or the existing placeholder when the file is missing.
     */
    public function imageUrl(): string
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            // Build from the current request so XAMPP subfolder installs keep their public path.
            return asset('storage/' . ltrim($this->image, '/'));
        }

        return asset(config('school.default_profile_picture', 'images/Wolf.png'));
    }
}
