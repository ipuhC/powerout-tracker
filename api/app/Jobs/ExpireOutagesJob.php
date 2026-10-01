<?php

namespace App\Jobs;

use App\Enums\OutageStatus;
use App\Models\OutageReport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireOutagesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        OutageReport::where('status', OutageStatus::ACTIVE->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => OutageStatus::EXPIRED->value]);
    }
}
