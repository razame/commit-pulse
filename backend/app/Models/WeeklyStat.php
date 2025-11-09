<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start',
        'week_end',
        'commits_count',
        'total_additions',
        'total_deletions',
        'top_repo',
        'top_language',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'commits_count' => 'integer',
            'total_additions' => 'integer',
            'total_deletions' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

