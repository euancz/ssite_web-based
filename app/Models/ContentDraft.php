<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ContentDraft extends Model
{
    use HasUuids;

    protected $primaryKey = 'draft_id';

    protected function casts(): array
    {
        return ['payload' => 'array', 'lock_version' => 'integer'];
    }
}
