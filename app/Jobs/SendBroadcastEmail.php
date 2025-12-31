<?php

namespace App\Jobs;

use App\Mail\UserNotificationMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable; // ← ADD THIS
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable; // ← ADD Batchable here

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $subject,
        public string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new UserNotificationMail(
                user: $this->user,
                subjectLine: $this->subject,
                messageBody: $this->message
            )
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        \Log::error("Broadcast email failed permanently for user {$this->user->id}: " . $exception->getMessage());
    }
}