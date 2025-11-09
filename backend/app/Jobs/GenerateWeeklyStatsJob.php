<?php

namespace App\Jobs;

use App\Models\Commit;
use App\Models\User;
use App\Models\WeeklyStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GenerateWeeklyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $commits = Commit::where('user_id', $this->user->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $topRepo = $commits->groupBy('repo_id')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();

        $topRepoName = $topRepo 
            ? Commit::find($topRepo)?->repository?->repo_name 
            : null;

        $topLanguage = $this->user->repositories()
            ->whereNotNull('language')
            ->selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->orderByDesc('count')
            ->first()?->language;

        WeeklyStat::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'week_start' => $weekStart->format('Y-m-d'),
            ],
            [
                'week_end' => $weekEnd->format('Y-m-d'),
                'commits_count' => $commits->count(),
                'total_additions' => $commits->sum('additions'),
                'total_deletions' => $commits->sum('deletions'),
                'top_repo' => $topRepoName,
                'top_language' => $topLanguage,
            ]
        );
    }
}

