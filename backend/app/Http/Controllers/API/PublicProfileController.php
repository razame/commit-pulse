<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WeeklyStat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PublicProfileController extends Controller
{
    public function show($username)
    {
        $user = User::where('name', $username)
            ->orWhere('email', $username)
            ->firstOrFail();

        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $weeklyStat = WeeklyStat::where('user_id', $user->id)
            ->where('week_start', $weekStart->format('Y-m-d'))
            ->first();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
            'stats' => $weeklyStat ? [
                'week_start' => $weeklyStat->week_start->format('Y-m-d'),
                'week_end' => $weeklyStat->week_end->format('Y-m-d'),
                'commits_count' => $weeklyStat->commits_count,
                'total_additions' => $weeklyStat->total_additions,
                'total_deletions' => $weeklyStat->total_deletions,
                'top_repo' => $weeklyStat->top_repo,
                'top_language' => $weeklyStat->top_language,
            ] : null,
        ]);
    }
}

