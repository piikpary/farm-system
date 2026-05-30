<?php

namespace App\Http\Controllers;

use App\Models\DriverGpsTrack;
use App\Models\FarmWorkLog;
use Illuminate\Http\Request;

class DriverGpsTrackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'farm_work_log_id' => 'required|exists:farm_work_logs,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'tracked_at' => 'nullable|date',
        ]);

        $log = FarmWorkLog::findOrFail($data['farm_work_log_id']);

        $track = DriverGpsTrack::create([
            'farm_work_log_id' => $log->id,
            'driver_id' => $log->driver_id,
            'tractor_id' => $log->tractor_id,
            'zone_id' => $log->zone_id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'speed' => $data['speed'] ?? null,
            'accuracy' => $data['accuracy'] ?? null,
            'tracked_at' => $data['tracked_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'track_id' => $track->id,
        ]);
    }
}