<?php

namespace App\Jobs;

use App\Models\StudentSession;
use App\Services\AI\FlagResult;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3 stub — Phase 4 persists SafetyAlert records and notifies teachers.
 */
class ProcessSafetyAlert
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public StudentSession $session,
        public FlagResult $flag,
        public string $triggerContent,
    ) {}

    public function handle(): void
    {
        Log::warning('Safety alert (Phase 3 stub)', [
            'session_id' => $this->session->id,
            'category' => $this->flag->category,
            'severity' => $this->flag->severity,
        ]);
    }
}
