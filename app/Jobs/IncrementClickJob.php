<?php

namespace App\Jobs;

use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncrementClickJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $urlId;

    public function __construct($urlId)
    {
        $this->urlId = $urlId;
    }

    public function handle()
    {
        Url::where('id', $this->urlId)->increment('clicks');
    }
}
