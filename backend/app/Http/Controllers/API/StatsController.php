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
        
        $weekStart = Carbon::now()->startOfWeek()->startOfDay();
        $weekEnd = Carbon::now()->endOfWeek()->endOfDay();

        // Eager load repository relationship to avoid N+1 queries
        $commits = Commit::with('repository')
            ->where('user_id', $user->id)
            ->whereBetween('date', [
                $weekStart->format('Y-m-d'),
                $weekEnd->format('Y-m-d')
            ])
            ->get();

        // Group commits by day of week (Mon, Tue, etc.)
        // Carbon's dayOfWeek: 0=Sunday, 1=Monday, ..., 6=Saturday
        // We want: Mon=0, Tue=1, ..., Sun=6
        $commitsByDay = $commits->groupBy(function ($commit) {
            $dayOfWeek = $commit->date->dayOfWeek;
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            // Convert: 0(Sun)->6, 1(Mon)->0, 2(Tue)->1, ..., 6(Sat)->5
            $index = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1;
            return $days[$index];
        })->map(function ($dayCommits) {
            return $dayCommits->count();
        })->toArray();

        $totalCommits = $commits->count();
        $totalAdditions = $commits->sum('additions');
        $totalDeletions = $commits->sum('deletions');

        $topRepos = $commits->groupBy('repo_id')
            ->map(function ($repoCommits) {
                $firstCommit = $repoCommits->first();
                // Add null check for repository
                if (!$firstCommit || !$firstCommit->repository) {
                    return null;
                }
                return [
                    'count' => $repoCommits->count(),
                    'repo' => $firstCommit->repository->repo_name,
                ];
            })
            ->filter() // Remove null entries
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

        // Format commits for frontend
        $commitsList = $commits->map(function ($commit) {
            return [
                'id' => $commit->id,
                'date' => $commit->date->format('Y-m-d'),
                'message' => $commit->message,
                'additions' => $commit->additions,
                'deletions' => $commit->deletions,
                'total_changes' => $commit->total_changes,
                'repository' => $commit->repository ? $commit->repository->repo_name : null,
            ];
        })->sortByDesc('date')->values();

        return response()->json([
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'total_commits' => $totalCommits,
            'total_additions' => $totalAdditions ?? 0,
            'total_deletions' => $totalDeletions ?? 0,
            'commits_by_day' => $commitsByDay,
            'top_repos' => $topRepos->toArray(),
            'top_languages' => $topLanguages->toArray(),
            'commits' => $commitsList->toArray(),
            'last_synced_at' => $user->last_synced_at?->toIso8601String(),
        ]);
    }

    public function sync(Request $request)
    {
        $user = Auth::user();
        
        // Dispatch the job to fetch repositories from GitHub
        \App\Jobs\FetchRepositoriesJob::dispatch($user);
        
        return response()->json([
            'message' => 'Sync initiated. Your commits are being fetched from GitHub. This may take a few moments.',
        ]);
    }
}

