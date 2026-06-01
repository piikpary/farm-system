<?php

namespace App\Http\Controllers;

use App\Models\DriverGpsTrack;
use App\Models\DriverWorkAction;
use App\Models\FarmWorkLog;
use App\Services\GpsWorkCalculator;
use Illuminate\Http\Request;

class DriverMobileWorkController extends Controller
{
    public function show($token)
    {
        $log = FarmWorkLog::with([
                'driver',
                'tractor',
                'zone',
                'taskCategory',
            ])
            ->where('driver_access_token', $token)
            ->firstOrFail();

        $trackPoints = $log->gpsTracks()
            ->orderBy('tracked_at')
            ->get()
            ->map(fn ($track) => [
                'lat' => (float) $track->lat,
                'lng' => (float) $track->lng,
                'tracked_at' => optional($track->tracked_at)->format('Y-m-d H:i:s'),
            ])
            ->toArray();

        return view('components.drivers.work-show', [
            'log' => $log,
            'token' => $token,
            'trackPoints' => $trackPoints,
        ]);
    }

    public function action(Request $request, $token)
    {
        $data = $request->validate([
            'action_type' => 'required|in:start_work,pause_work,resume_work,refill_diesel,problem,finish_work',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'note' => 'nullable|string|max:1000',
            'action_at' => 'nullable|date',
        ]);

        $log = FarmWorkLog::with(['tractor', 'zone'])
            ->where('driver_access_token', $token)
            ->firstOrFail();

        $action = DriverWorkAction::create([
            'farm_work_log_id' => $log->id,
            'driver_id' => $log->driver_id,
            'tractor_id' => $log->tractor_id,
            'zone_id' => $log->zone_id,
            'action_type' => $data['action_type'],
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'note' => $data['note'] ?? null,
            'action_at' => $data['action_at'] ?? now(),
        ]);

        if ($data['action_type'] === 'start_work') {
            $log->update([
                'work_status' => 'working',
                'started_at' => $log->started_at ?? now(),
            ]);
        }

        if ($data['action_type'] === 'pause_work') {
            $log->update([
                'work_status' => 'paused',
            ]);
        }

        if ($data['action_type'] === 'resume_work') {
            $log->update([
                'work_status' => 'resumed',
            ]);
        }

        if ($data['action_type'] === 'finish_work') {
            $result = app(GpsWorkCalculator::class)->calculateForWorkLog($log);

            $log->update([
                'work_status' => 'finished',
                'finished_at' => now(),
                'gps_distance_meters' => $result['distance_meters'],
                'estimated_plowed_area' => $result['estimated_plowed_area'],
                'gps_progress_percent' => $result['progress_percent'],
            ]);
        }

        return response()->json([
            'success' => true,
            'action_id' => $action->id,
            'work_status' => $log->fresh()->work_status,
            'message' => 'Action saved successfully.',
        ]);
    }

    public function gps(Request $request, $token)
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric|min:0',
            'accuracy' => 'nullable|numeric|min:0',
            'tracked_at' => 'nullable|date',
        ]);

        $log = FarmWorkLog::where('driver_access_token', $token)
            ->firstOrFail();

        if (!in_array($log->work_status, ['working', 'resumed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Work is not active.',
            ], 422);
        }

        $track = DriverGpsTrack::create([
            'farm_work_log_id' => $log->id,
            'driver_id' => $log->driver_id,
            'tractor_id' => $log->tractor_id,
            'zone_id' => $log->zone_id,
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'speed' => $data['speed'] ?? 0,
            'accuracy' => $data['accuracy'] ?? null,
            'tracked_at' => $data['tracked_at'] ?? now(),
        ]);

        if ($log->tractor) {
            $log->tractor->update([
                'last_lat' => $data['lat'],
                'last_lng' => $data['lng'],
                'last_seen_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'GPS saved successfully.',
            'track_id' => $track->id,
        ]);
    }
}