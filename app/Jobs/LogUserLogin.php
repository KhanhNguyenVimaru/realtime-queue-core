<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogUserLogin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;

    public int $userId;
    public string $email;
    public ?string $ip;
    public ?string $userAgent;

    public function __construct(int $userId, string $email, ?string $ip, ?string $userAgent)
    {
        $this->userId = $userId;
        $this->email = $email;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
    }

    public function handle(): void
    {
        Log::info('User login job started.', [
            'user_id' => $this->userId,
            'email' => $this->email,
        ]);

        User::query()
            ->whereKey($this->userId)
            ->update(['last_login_at' => now()]);

        Log::info('User logged in.', [
            'user_id' => $this->userId,
            'email' => $this->email,
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('User login job failed.', [
            'user_id' => $this->userId,
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
