<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    /*
    |--------------------------------------------------------------------------
    | Timestamps
    |--------------------------------------------------------------------------
    */

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'student_number',
        'name',
        'year_level',
        'program',
        'institute',
        'gender',
        'contact_number',
        'email',
        'address',
        'password',
        'profile_picture',
        'role',
        'officer_position',
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

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}