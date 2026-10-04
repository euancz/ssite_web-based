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

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

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

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'profile_completed_at' => 'datetime',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isOfficer(): bool
    {
        return $this->role === 'officer';
    }

    public function isAdviser(): bool
    {
        return $this->role === 'adviser';
    }

    public function canPost(): bool
    {
        return $this->isOfficer() || $this->isAdviser();
    }

    public function hasCompletedProfile(): bool
    {
        return $this->profile_completed_at !== null;
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->isAdviser() => 'adviser.dashboard',
            $this->isOfficer() => 'officer.dashboard',
            default => 'home',
        };
    }
}