<?php

namespace App\Console;

use App\Jobs\FetchCommitsJob;
use App\Jobs\GenerateWeeklyStatsJob;
use App\Jobs\SendWeeklyDigestEmail;
use App\Models\User;
use App\Models\WeeklyStat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Fetch commits daily for all active users
        $schedule->call(function () {
            User::whereNotNull('github_token')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    FetchCommitsJob::dispatch($user);
                }
            });
        })->daily();

        // Generate weekly stats every Monday
        $schedule->call(function () {
            User::whereNotNull('github_token')->chunk(100, function ($users) {
                foreach ($users as $user) {
                    GenerateWeeklyStatsJob::dispatch($user);
                }
            });
        })->weeklyOn(1, '2:00'); // Monday at 2 AM

        // Send weekly digest every Sunday night
        $schedule->call(function () {
            $weekStart = Carbon::now()->subWeek()->startOfWeek();
            
            WeeklyStat::where('week_start', $weekStart->format('Y-m-d'))
                ->with('user')
                ->chunk(100, function ($stats) {
                    foreach ($stats as $stat) {
                        SendWeeklyDigestEmail::dispatch($stat->user, $stat);
                    }
                });
        })->weeklyOn(0, '20:00'); // Sunday at 8 PM
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

