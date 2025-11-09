<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WeeklyStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigestEmail extends Mailable implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public WeeklyStat $weeklyStat
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send($this);
    }

    public function build()
    {
        return $this->subject('Your Week in Code - CommitPulse')
            ->view('emails.weekly-digest')
            ->with([
                'user' => $this->user,
                'stats' => $this->weeklyStat,
            ]);
    }
}

