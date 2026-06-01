<?php

namespace App\Http\Controllers;

use App\Exports\FarmWorkLogsExport;
use App\Models\FarmWorkLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FarmWorkLogExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        $fileName = 'farm-work-logs-' . now()->format('Y-m-d-His') . '.csv';

        $logs = $this->queryLogs($request)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        return response()->streamDownload(function () use ($logs) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'Date',
                'Status',
                'Tractor',
                'Driver',
                'Zone',
                'Task',
                'Hour',
                'Area',
                'Diesel Start',
                'Diesel Refill',
                'Diesel End',
                'Diesel Used',
                'L/ha',
                'ha/hr',
                'GPS Distance',
                'Estimated Plowed Area',
                'GPS Progress',
                'Variance',
                'Note',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    optional($log->work_date)->format('d M Y'),
                    ucfirst($log->work_status ?? 'pending'),
                    $log->tractor->tractor_no ?? '',
                    $log->driver->name ?? '',
                    $log->zone->zone_code ?? '',
                    $log->taskCategory->name ?? '',
                    number_format($log->working_duration ?? 0, 2, '.', ''),
                    number_format($log->working_area ?? 0, 2, '.', ''),
                    number_format($log->diesel_start ?? 0, 2, '.', ''),
                    number_format($log->diesel_refill ?? 0, 2, '.', ''),
                    number_format($log->diesel_end ?? 0, 2, '.', ''),
                    number_format($log->diesel_consumed ?? 0, 2, '.', ''),
                    number_format($log->diesel_per_hectare ?? 0, 2, '.', ''),
                    number_format($log->hectare_per_hour ?? 0, 2, '.', ''),
                    number_format($log->gps_distance_meters ?? 0, 2, '.', ''),
                    number_format($log->estimated_plowed_area ?? 0, 4, '.', ''),
                    number_format($log->gps_progress_percent ?? 0, 2, '.', ''),
                    number_format($log->variance_fuel ?? 0, 2, '.', ''),
                    $log->note ?? '',
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }

    public function excel(Request $request)
    {
        $filters = $request->only([
            'search',
            'date_from',
            'date_to',
            'tractor_id',
            'driver_id',
            'zone_id',
            'task_category_id',
        ]);

        return Excel::download(
            new FarmWorkLogsExport($filters),
            'farm-work-logs-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    private function queryLogs(Request $request)
    {
        return FarmWorkLog::with([
                'tractor',
                'driver',
                'zone',
                'taskCategory',
            ])
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($query) use ($search) {
                    $query->whereHas('tractor', function ($t) use ($search) {
                        $t->where('tractor_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($d) use ($search) {
                        $d->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('zone', function ($z) use ($search) {
                        $z->where('zone_code', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('taskCategory', function ($task) use ($search) {
                        $task->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->whereDate('work_date', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->whereDate('work_date', '<=', $request->date_to);
            })
            ->when($request->tractor_id, function ($q) use ($request) {
                $q->where('tractor_id', $request->tractor_id);
            })
            ->when($request->driver_id, function ($q) use ($request) {
                $q->where('driver_id', $request->driver_id);
            })
            ->when($request->zone_id, function ($q) use ($request) {
                $q->where('zone_id', $request->zone_id);
            })
            ->when($request->task_category_id, function ($q) use ($request) {
                $q->where('task_category_id', $request->task_category_id);
            })
            ->latest('work_date');
    }
}