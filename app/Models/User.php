<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function dailyProgresses(): HasMany
    {
        return $this->hasMany(DailyProgress::class);
    }

    public function encryptedNotes(): HasMany
    {
        return $this->hasMany(EncryptedNote::class);
    }
}
