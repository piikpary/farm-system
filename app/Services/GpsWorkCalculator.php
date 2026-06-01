<?php

namespace App\Services;

use App\Models\FarmWorkLog;

class GpsWorkCalculator
{
    public function calculateForWorkLog(FarmWorkLog $workLog): array
    {
        $workLog->loadMissing(['tractor', 'zone']);

        $points = $workLog->gpsTracks()
            ->orderBy('tracked_at')
            ->get(['lat', 'lng']);

        if ($points->count() < 2) {
            return [
                'distance_meters' => 0,
                'estimated_plowed_area' => 0,
                'progress_percent' => 0,
            ];
        }

        $distanceMeters = 0;

        for ($i = 1; $i < $points->count(); $i++) {
            $distanceMeters += $this->haversineDistance(
                (float) $points[$i - 1]->lat,
                (float) $points[$i - 1]->lng,
                (float) $points[$i]->lat,
                (float) $points[$i]->lng
            );
        }

        $plowWidth = (float) ($workLog->tractor->plow_width ?? 0);

        $areaM2 = $distanceMeters * $plowWidth;
        $areaHectare = $areaM2 / 10000;

        $zoneTotalArea = (float) ($workLog->zone->total_area ?? 0);

        $progressPercent = $zoneTotalArea > 0
            ? min(($areaHectare / $zoneTotalArea) * 100, 100)
            : 0;

        return [
            'distance_meters' => round($distanceMeters, 2),
            'estimated_plowed_area' => round($areaHectare, 4),
            'progress_percent' => round($progressPercent, 2),
        ];
    }

    private function haversineDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000;

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;

        $a = sin($latDelta / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}