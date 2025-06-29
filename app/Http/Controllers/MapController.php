<?php

namespace App\Http\Controllers;

use App\Jobs\RenderOverlay;
use App\Services\MapRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    public function overlay($z, $x, $y)
    {
        $pzoom = $z;
        $px = $x;
        $py = $y;
        $parent = "l_{$pzoom}.{$px}.{$py}.packed";
        while ($pzoom > 6 && !Storage::disk('data_cache')->exists($parent)) {
            $pzoom -= 1;
            $px = floor($px / 2);
            $py = floor($py / 2);
            $parent = "l_{$pzoom}.{$px}.{$py}.packed";
        }
        if (Storage::disk('data_cache')->size($parent) < 13718638) {
            RenderOverlay::dispatchSync($z, $x, $y);
            $path = Storage::disk('public')->path("lb_overlay/$z/$x/$y.png");
            return response()->file($path, [
                'Content-Type' => 'image/png',
            ]);
        }

        RenderOverlay::dispatch($z, $x, $y);
        return new Response('', 202);
    }

    public function map($z, $x, $y)
    {
        MapRenderer::handle($z, $x, $y);
        $path = Storage::disk('public')->path("lb_map/$z/$x/$y.png");
        return response()->file($path, [
            'Content-Type' => 'image/png',
        ]);
    }
}
