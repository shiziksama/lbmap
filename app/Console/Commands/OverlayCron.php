<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OverlayRenderer;

class OverlayCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'overlay:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run overlay rendering cron tasks';

    public function handle(): int
    {
        ini_set('memory_limit', '25G');

        $renderChilds = function (int $zoom, int $x, int $y) use (&$renderChilds) {
            OverlayRenderer::handle($zoom + 1, $x * 2, $y * 2);
            OverlayRenderer::handle($zoom + 1, $x * 2, $y * 2 + 1);
            OverlayRenderer::handle($zoom + 1, $x * 2 + 1, $y * 2);
            OverlayRenderer::handle($zoom + 1, $x * 2 + 1, $y * 2 + 1);
        };

        OverlayRenderer::handle(6, 32, 20);
        return Command::SUCCESS;

        OverlayRenderer::handle(4, 8, 5);
        OverlayRenderer::handle(3, 4, 2);

        OverlayRenderer::handle(1, 0, 0);
        OverlayRenderer::handle(1, 0, 1);
        OverlayRenderer::handle(1, 1, 0);
        OverlayRenderer::handle(1, 1, 1);

        for ($i = 6; $i < 11; $i++) {
            $files = glob(base_path('lb_json/l_' . $i . '*.packed'));
            $files = array_filter($files, fn($item) => filesize($item) > 9000000);
            foreach ($files as $file) {
                preg_match('~/lb_json/l_(?<zoom>(\\d+)).(?<x>(\\d+)).(?<y>(\\d+)).packed~', $file, $m);
                $renderChilds((int)$m['zoom'], (int)$m['x'], (int)$m['y']);
            }
        }

        return Command::SUCCESS;
    }
}
