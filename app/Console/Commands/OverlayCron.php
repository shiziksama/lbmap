<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\RenderOverlay;
use Illuminate\Support\Facades\Storage;

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
            RenderOverlay::dispatchSync($zoom + 1, $x * 2, $y * 2);
            RenderOverlay::dispatchSync($zoom + 1, $x * 2, $y * 2 + 1);
            RenderOverlay::dispatchSync($zoom + 1, $x * 2 + 1, $y * 2);
            RenderOverlay::dispatchSync($zoom + 1, $x * 2 + 1, $y * 2 + 1);
        };

        RenderOverlay::dispatchSync(6, 32, 20);
        return Command::SUCCESS;

        RenderOverlay::dispatchSync(4, 8, 5);
        RenderOverlay::dispatchSync(3, 4, 2);

        RenderOverlay::dispatchSync(1, 0, 0);
        RenderOverlay::dispatchSync(1, 0, 1);
        RenderOverlay::dispatchSync(1, 1, 0);
        RenderOverlay::dispatchSync(1, 1, 1);

        for ($i = 6; $i < 11; $i++) {
            $pattern = Storage::disk('data_cache')->path('l_' . $i . '*.packed');
            $files = glob($pattern);
            $files = array_filter($files, fn($item) => Storage::disk('data_cache')->size(basename($item)) > 9000000);
            foreach ($files as $file) {
                preg_match('/l_(?<zoom>\d+)\.(?<x>\d+)\.(?<y>\d+)\.packed/', basename($file), $m);
                $renderChilds((int)$m['zoom'], (int)$m['x'], (int)$m['y']);
            }
        }

        return Command::SUCCESS;
    }
}
