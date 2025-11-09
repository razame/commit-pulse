<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repo_id',
        'date',
        'message',
        'additions',
        'deletions',
        'total_changes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'additions' => 'integer',
            'deletions' => 'integer',
            'total_changes' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }
}

