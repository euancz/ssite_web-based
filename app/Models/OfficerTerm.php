<?php

namespace App\Models;

use App\Support\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Stores a yearly officer snapshot; copied name, position, and photo preserve history after profile changes.
 */
class OfficerTerm extends Model
{
    protected $table = 'officer_terms';
    protected $primaryKey = 'term_id';
    protected $fillable = ['academic_year', 'name', 'position', 'photo', 'sort_order'];

    // SECURITY: Linked user IDs are assigned only by trusted controller logic, never by mass assignment.

    public function getRouteKeyName(): string
    {
        return 'term_id';
    }

    /** The nullable relationship allows a historical snapshot to outlive an account. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function scopeForYear(Builder $query, string $ay): Builder
    {
        return $query->where('academic_year', $ay);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->forYear(AcademicYear::current());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('academic_year', '!=', AcademicYear::current());
    }

    /** Return a safe URL only when an external or stored photo can be served. */
    public function photoUrl(): ?string
    {
        $photo = trim((string) $this->photo);
        if ($photo === '') {
            return null;
        }
        if (preg_match('/^https?:\/\//i', $photo)) {
            return $photo;
        }
        // Preserve the About page's existing public asset path during the one-time transition.
        if (file_exists(public_path($photo))) {
            return asset($photo);
        }
        if (! Storage::disk('public')->exists($photo)) {
            return null;
        }

        return asset('storage/' . ltrim($photo, '/'));
    }

    /** Apply configured position order, then adviser-defined order for duplicate positions. */
    public function scopeInDisplayOrder(Builder $query): Builder
    {
        $positions = config('school.officer_positions', []);
        $cases = [];
        foreach ($positions as $index => $position) {
            $escaped = str_replace("'", "''", $position);
            $cases[] = "WHEN '{$escaped}' THEN {$index}";
        }
        $order = $cases ? 'CASE position ' . implode(' ', $cases) . ' ELSE ' . count($cases) . ' END' : 'sort_order';

        return $query->orderByRaw($order)->orderBy('sort_order')->orderBy('name');
    }
}
