<?php

namespace App\Jobs;

use App\Models\Url;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IncrementClickJob implements ShouldQueue
{
    use Queueable;

    public $urlId;

    /**
     * Create a new job instance.
     */
    public function __construct($urlId)
    {
        $this->urlId = $urlId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Url::where('id', $this->urlId)->increment('clicks');
    }
}
