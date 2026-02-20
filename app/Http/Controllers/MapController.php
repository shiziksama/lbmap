<?php

namespace App\Http\Controllers;

use App\Jobs\RenderOverlay;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    public function overlay($z, $x, $y)
    {
        if ($z > 7) {
            RenderOverlay::dispatchSync($z, $x, $y);
            $path = Storage::disk('public')->path("lb_overlay/$z/$x/$y.png");

            return response()->file($path, ['Content-Type' => 'image/png']);

        }
        RenderOverlay::dispatch($z, $x, $y);

        return new Response('', 202);

    }
}
