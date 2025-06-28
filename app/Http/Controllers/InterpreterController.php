<?php

namespace App\Http\Controllers;

use App\Services\LBRoads;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InterpreterController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if ($request->isMethod('options')) {
            return response('')->withHeaders([
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers', ''),
                'Access-Control-Allow-Origin' => $request->header('Origin', '*'),
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        $bbox = null;
        foreach (explode(';', $request->input('data', '')) as $line) {
            preg_match_all('/\([\d.,-]+\)/', $line, $out);
            if (empty(array_filter($out))) {
                continue;
            }
            $bbox = substr($out[0][0], 1, -1);
        }

        if (!$bbox) {
            return response()->json([], 400);
        }

        $bbox = explode(',', $bbox);
        $bbox = $bbox[1] . ',' . $bbox[0] . ',' . $bbox[3] . ',' . $bbox[2];

        $service = new LBRoads();
        $elements = $service->get_elements('planet-black.o5m', $bbox);

        $result = [
            'elements' => $elements,
            'version' => 0.6,
            'generator' => 'Overpass API 0.7.56.8 7d656e78',
            'osm3s' => [
                'timestamp_osm_base' => '2021-02-05T12:58:03Z',
                'copyright' => 'The data included in this document is from www.openstreetmap.org. The data is made available under ODbL.',
            ],
        ];

        return response()->json($result)->withHeaders([
            'Access-Control-Allow-Origin' => $request->header('Origin', '*'),
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
        ]);
    }
}
