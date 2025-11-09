<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repo_name',
        'repo_url',
        'language',
        'last_commit_date',
    ];

    protected function casts(): array
    {
        return [
            'last_commit_date' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commits()
    {
        return $this->hasMany(Commit::class);
    }
}

