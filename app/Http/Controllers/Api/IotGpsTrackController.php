<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverGpsTrack;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use Illuminate\Http\Request;

class IotGpsTrackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:150',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric|min:0',
            'accuracy' => 'nullable|numeric|min:0',
            'tracked_at' => 'nullable|date',
        ]);

        $tractor = Tractor::where('iot_device_id', $data['device_id'])
            ->where('status', 'active')
            ->first();

        if (!$tractor) {
            return response()->json([
                'success' => false,
                'message' => 'Tractor device not registered.',
            ], 404);
        }

        $workLog = FarmWorkLog::where('tractor_id', $tractor->id)
            ->whereIn('work_status', ['working', 'resumed'])
            ->latest('started_at')
            ->first();

        if (!$workLog) {
            $tractor->update([
                'last_lat' => $data['lat'],
                'last_lng' => $data['lng'],
                'last_seen_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No active work log for this tractor.',
            ], 422);
        }

        $track = DriverGpsTrack::create([
            'farm_work_log_id' => $workLog->id,
            'driver_id' => $workLog->driver_id,
            'tractor_id' => $workLog->tractor_id,
            'zone_id' => $workLog->zone_id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'speed' => $data['speed'] ?? 0,
            'accuracy' => $data['accuracy'] ?? null,
            'tracked_at' => $data['tracked_at'] ?? now(),
        ]);

        $tractor->update([
            'last_lat' => $data['lat'],
            'last_lng' => $data['lng'],
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'GPS track saved successfully.',
            'track_id' => $track->id,
            'work_log_id' => $workLog->id,
        ]);
    }
}