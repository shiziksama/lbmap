<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OverlayRenderer;

class ProcessQueue extends Command
{
    protected $signature = 'overlay:queue';

    protected $description = 'Process overlay render queue';

    public function handle(): int
    {
        ini_set('memory_limit', '5G');
        while (true) {
            $files = glob(base_path('queue/*'));
            foreach ($files as $file) {
                $basename = pathinfo($file, PATHINFO_BASENAME);
                [$zoom, $x, $y] = explode('.', $basename);
                OverlayRenderer::handle($zoom, $x, $y);
                unlink($file);
            }
            $this->info('end queue');
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
