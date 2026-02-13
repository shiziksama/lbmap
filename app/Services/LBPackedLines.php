<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class LBPackedLines
{
    private const DISK_DEFAULT = 'data_cache';
    private const DISK_PERMANENT = 'data_cache_permanent';

    public function get_lines($zoom, $x, $y)
    {
        $currentZoom = $zoom;
        $currentX = $x;
        $currentY = $y;

        while ($currentZoom >= 0) {
            $lines = $this->get_lines_for_tile($currentZoom, $currentX, $currentY);
            if ($lines !== null) {
                return $lines;
            }

            if ($currentZoom === 0) {
                break;
            }

            $currentZoom -= 1;
            $currentX = floor($currentX / 2);
            $currentY = floor($currentY / 2);
        }

        return null;
    }

    public function set_lines($zoom, $x, $y, $lines, $is_permanent = false)
    {
        $disk = $is_permanent ? self::DISK_PERMANENT : self::DISK_DEFAULT;
        $file = $this->fileName($zoom, $x, $y);

        Storage::disk($disk)->put($file, $this->lines2file($lines));
    }

    private function get_lines_for_tile($zoom, $x, $y)
    {
        $file = $this->fileName($zoom, $x, $y);

        foreach ([self::DISK_PERMANENT, self::DISK_DEFAULT] as $disk) {
            if (Storage::disk($disk)->exists($file)) {
                return $this->file2lines(Storage::disk($disk)->get($file));
            }
        }

        return null;
    }

    private function fileName($zoom, $x, $y)
    {
        return 'l_'.$zoom.'.'.$x.'.'.$y.'.packed';
    }

    private function file2lines($content)
    {
        $lbroads = $this->getLbroadsMapping();
        $encoded = gzuncompress($content);
        $pointlines = array_values(unpack('i*', $encoded));
        $lines = [];
        while (! empty($pointlines)) {
            $lines[] = $this->parseLine($pointlines, $lbroads);
        }

        return $lines;
    }

    private function getLbroadsMapping()
    {
        return [
            1 => 'great',
            2 => 'bicycle_undefined',
            3 => 'bikelane',
            4 => 'greatfoot',
            5 => 'foot',
            6 => 'undefined',
        ];
    }

    private function parseLine(&$pointlines, $lbroads)
    {
        $line = [];
        $line['tags']['lbroads'] = $lbroads[array_shift($pointlines)];
        $count = round(array_shift($pointlines) / 2);
        $line['points'] = $this->parsePoints($pointlines, $count);

        return $line;
    }

    private function parsePoints(&$pointlines, $count)
    {
        $points = [];
        while ($count > 0) {
            $points[] = [
                'lat' => array_shift($pointlines) / 10000000,
                'lng' => array_shift($pointlines) / 10000000,
            ];
            $count--;
        }

        return $points;
    }

    private function lines2file($lines)
    {
        $lbroads = ['great' => 1, 'bicycle_undefined' => 2, 'bikelane' => 3, 'greatfoot' => 4, 'foot' => 5, 'undefined' => 6];
        $packed = '';
        foreach ($lines as $line) {
            $pointline = [];
            $type = $lbroads[$line['tags']['lbroads'] ?? 'undefined'];
            foreach ($line['points'] as $point) {
                $pointline[] = $point['lat'] * 10000000;
                $pointline[] = $point['lng'] * 10000000;
            }
            $pointlines[] = $pointline;
            $packed .= pack('i*', $type, count($pointline), ...$pointline);
        }
        $compressed = gzcompress($packed, 9);

        return $compressed;
    }
}
