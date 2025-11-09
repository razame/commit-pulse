<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Commit;
use App\Models\WeeklyStat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function currentWeek()
    {
        $user = Auth::user();
        
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $commits = Commit::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $commitsByDay = $commits->groupBy(function ($commit) {
            return $commit->date->format('Y-m-d');
        })->map(function ($dayCommits) {
            return $dayCommits->count();
        });

        $totalCommits = $commits->count();
        $totalAdditions = $commits->sum('additions');
        $totalDeletions = $commits->sum('deletions');

        $topRepos = $commits->groupBy('repo_id')
            ->map(function ($repoCommits) {
                return [
                    'count' => $repoCommits->count(),
                    'repo' => $repoCommits->first()->repository->repo_name,
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $topLanguages = $user->repositories()
            ->whereNotNull('language')
            ->selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->pluck('count', 'language');

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'total_commits' => $totalCommits,
            'total_additions' => $totalAdditions,
            'total_deletions' => $totalDeletions,
            'commits_by_day' => $commitsByDay,
            'top_repos' => $topRepos,
            'top_languages' => $topLanguages,
            'last_synced_at' => $user->last_synced_at?->toIso8601String(),
        ]);
    }

    public function sync(Request $request)
    {
        $user = Auth::user();
        
        // This endpoint will be called by the Golang worker
        // For now, return a success response
        // The actual sync logic will be handled by the worker
        
        return response()->json([
            'message' => 'Sync initiated. Data will be updated shortly.',
        ]);
    }
}

