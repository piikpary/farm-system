<?php

namespace App\Http\Controllers;

use App\Models\DriverWorkAction;
use App\Models\FarmWorkLog;
use App\Services\GpsWorkCalculator;
use Illuminate\Http\Request;

class DriverWorkActionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'farm_work_log_id' => 'required|exists:farm_work_logs,id',
            'action_type' => 'required|in:start_work,pause_work,resume_work,refill_diesel,problem,finish_work',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'note' => 'nullable|string|max:1000',
            'action_at' => 'nullable|date',
        ]);

        $log = FarmWorkLog::with(['tractor', 'zone'])
            ->findOrFail($data['farm_work_log_id']);

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
            'message' => 'Work action saved successfully.',
        ]);
    }
}