<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stores private PDFs with review and publication states kept separate. */
class Document extends Model
{
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const CONTENT_ACTIVE = 'active';
    public const CONTENT_ARCHIVED = 'archived';

    protected $table = 'documents';
    protected $primaryKey = 'document_id';

    // SECURITY: Workflow and file metadata are set by trusted controller actions only.
    protected $fillable = ['title', 'description', 'category'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function getRouteKeyName(): string { return 'document_id'; }

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id', 'user_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by_user_id', 'user_id'); }
    public function scopePending(Builder $query): Builder { return $query->where('approval_status', self::APPROVAL_PENDING); }
    public function scopeApproved(Builder $query): Builder { return $query->where('approval_status', self::APPROVAL_APPROVED); }
    public function scopeRejected(Builder $query): Builder { return $query->where('approval_status', self::APPROVAL_REJECTED); }
    public function scopeActive(Builder $query): Builder { return $query->where('content_status', self::CONTENT_ACTIVE); }
    public function scopeArchived(Builder $query): Builder { return $query->where('content_status', self::CONTENT_ARCHIVED); }
    public function scopePubliclyVisible(Builder $query): Builder { return $query->approved()->active(); }
    public function isPending(): bool { return $this->approval_status === self::APPROVAL_PENDING; }
    public function isApproved(): bool { return $this->approval_status === self::APPROVAL_APPROVED; }
    public function isRejected(): bool { return $this->approval_status === self::APPROVAL_REJECTED; }
    public function isArchived(): bool { return $this->content_status === self::CONTENT_ARCHIVED; }
}
