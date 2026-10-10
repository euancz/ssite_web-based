<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stores private liquidation PDFs with independent review and publication states. */
class Liquidation extends Model
{
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const CONTENT_ACTIVE = 'active';
    public const CONTENT_ARCHIVED = 'archived';

    protected $table = 'liquidations';
    protected $primaryKey = 'liquidation_id';

    // SECURITY: Workflow state and uploaded-file metadata are never mass assignable.
    protected $fillable = ['title', 'description', 'amount', 'report_date'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'report_date' => 'date', 'reviewed_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function getRouteKeyName(): string { return 'liquidation_id'; }
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

    /** Format the decimal string without converting a monetary value to floating point. */
    public function formattedAmount(): string
    {
        if ($this->amount === null || $this->amount === '') return '—';
        [$whole, $fraction] = array_pad(explode('.', (string) $this->amount, 2), 2, '00');
        return '₱' . number_format((int) $whole) . '.' . str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
