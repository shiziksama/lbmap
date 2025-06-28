<?php

namespace App\Http\Controllers;

use App\Services\OverlayRenderer;
use App\Services\MapRenderer;
use Illuminate\Http\Response;

class MapController extends Controller
{
    public function overlay($z, $x, $y)
    {
        $pzoom = $z;
        $px = $x;
        $py = $y;
        $parent = base_path("lb_json/l_{$pzoom}.{$px}.{$py}.packed");
        while ($pzoom > 6 && !file_exists($parent)) {
            $pzoom -= 1;
            $px = floor($px / 2);
            $py = floor($py / 2);
            $parent = base_path("lb_json/l_{$pzoom}.{$px}.{$py}.packed");
        }
        if (filesize(base_path("lb_json/l_{$pzoom}.{$px}.{$py}.packed")) < 13718638) {
            OverlayRenderer::handle($z, $x, $y);
            $path = base_path("lb_overlay/$z/$x/$y.png");
            return response()->file($path, [
                'Content-Type' => 'image/png',
            ]);
        }
        file_put_contents(base_path("queue/$z.$x.$y"), '');
        return new Response('', 202);
    }

    public function map($z, $x, $y)
    {
        MapRenderer::handle($z, $x, $y);
        $path = base_path("lb_map/$z/$x/$y.png");
        return response()->file($path, [
            'Content-Type' => 'image/png',
        ]);
    }
}
