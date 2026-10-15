<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores one user's like without mass-assigning post or user identifiers. */
class PostLike extends Model
{
    protected $table = 'post_likes';
    protected $primaryKey = 'like_id';
    protected $keyType = 'int';
    protected $fillable = [];
    public $timestamps = false;
}
