<?php

namespace App\Jobs;

use App\Services\OverlayRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenderOverlay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $z, public int $x, public int $y)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        OverlayRenderer::handle($this->z, $this->x, $this->y);
    }
}
