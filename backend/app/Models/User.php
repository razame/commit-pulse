<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'github_id',
        'github_token',
        'avatar_url',
        'last_synced_at',
    ];

    protected $hidden = [
        'github_token',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function repositories()
    {
        return $this->hasMany(Repository::class);
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }

    public function weeklyStats()
    {
        return $this->hasMany(WeeklyStat::class);
    }
}

