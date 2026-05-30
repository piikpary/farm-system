<?php

namespace App\Http\Controllers;

use App\Models\DriverWorkAction;
use App\Models\FarmWorkLog;
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

        $log = FarmWorkLog::findOrFail($data['farm_work_log_id']);

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

        return response()->json([
            'success' => true,
            'action_id' => $action->id,
        ]);
    }
}