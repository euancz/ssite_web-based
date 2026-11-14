<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Sponsor extends Model
{
    protected $fillable = ['name', 'description', 'website_url', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeInDisplayOrder(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name')->orderBy('id');
    }

    public static function forDisplay(): Collection
    {
        return Schema::hasTable('sponsors')
            ? static::where('is_active', true)->inDisplayOrder()->get()
            : collect();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function forMobile(): array
    {
        return $this->only(['id', 'name', 'description', 'website_url']) + ['logo_url' => $this->logoUrl()];
    }
}
