<?php

namespace App\Http\Controllers;

use App\Models\FarmWorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TaskCategorySummaryReportController extends Controller
{
    public function index(Request $request)
    {
        $rows = $this->getReportRows($request);

        return view('reports.task-category-summary', [
            'rows' => $rows,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ]);
    }

    private function getReportRows(Request $request)
{
    $logs = FarmWorkLog::query()
        ->with('taskCategory')
        ->when($request->from_date, function ($q) use ($request) {
            $q->whereDate('work_date', '>=', $request->from_date);
        })
        ->when($request->to_date, function ($q) use ($request) {
            $q->whereDate('work_date', '<=', $request->to_date);
        })
        ->get()
        ->groupBy('task_category_id');

    return $logs->map(function ($items) {
        $first = $items->first();
        $taskCategory = $first->taskCategory;

        $taskCategoryName = $taskCategory->name ?? '-';

        $finishArea = (float) $items->sum('working_area');

        $totalWorkingHour = (float) $items->sum('working_duration');

        $consumedFuel = (float) $items->sum('diesel_consumed');

        $requestFuelPerHectare = (float) (
            $taskCategory->standard_fuel_per_hectare
            ?? $first->request_fuel_per_hectare
            ?? 0
        );

        $requestFuel = $finishArea * $requestFuelPerHectare;

        $consumedFuelPerHectare = $finishArea > 0
            ? $consumedFuel / $finishArea
            : 0;

        $remainingFuel = $requestFuel - $consumedFuel;

        $remainingFuelPerHectare = $finishArea > 0
            ? $remainingFuel / $finishArea
            : 0;

        $varianceFuel = $consumedFuel - $requestFuel;

        $varianceFuelPerHectare = $finishArea > 0
            ? $varianceFuel / $finishArea
            : 0;

        $hectarePerHour = $totalWorkingHour > 0
            ? $finishArea / $totalWorkingHour
            : 0;

        return [
            'task_category' => $taskCategoryName,

            // Sheet2 does not have real total land area in your current FarmWorkLog model.
            // So we use working_area as finish area.
            'total_area' => $finishArea,
            'finish_area' => $finishArea,
            'remaining_area' => 0,

            'request_fuel_per_hectare' => $requestFuelPerHectare,
            'request_fuel' => $requestFuel,

            'consumed_fuel' => $consumedFuel,
            'consumed_fuel_per_hectare' => $consumedFuelPerHectare,

            'remaining_fuel' => $remainingFuel,
            'remaining_fuel_per_hectare' => $remainingFuelPerHectare,

            'variance_fuel' => $varianceFuel,
            'variance_fuel_per_hectare' => $varianceFuelPerHectare,

            'total_working_hour' => $totalWorkingHour,
            'hectare_per_hour' => $hectarePerHour,
        ];
    })->values();
}
    public function exportCsv(Request $request)
    {
        $rows = $this->getReportRows($request);

        $filename = 'task-category-summary-report-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No',
                'Task Category',
                'Total Area (hec)',
                'Finish Area (hec)',
                'Remaining Area (hec)',
                'Request Fuel (L/hec)',
                'Request Fuel (L)',
                'Consumed Fuel (L)',
                'Consumed Fuel (L/hec)',
                'Remaining Fuel (L)',
                'Remaining Fuel (L/hec)',
                'Variance Fuel (L)',
                'Variance Fuel (L/hec)',
                'Total Working Hour (Hr)',
                'Total Working Hour (hec/hr)',
            ]);

            foreach ($rows as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row['task_category'],
                    number_format($row['total_area'], 2, '.', ''),
                    number_format($row['finish_area'], 2, '.', ''),
                    number_format($row['remaining_area'], 2, '.', ''),
                    number_format($row['request_fuel_per_hec'], 2, '.', ''),
                    number_format($row['request_fuel'], 2, '.', ''),
                    number_format($row['consumed_fuel'], 2, '.', ''),
                    number_format($row['consumed_fuel_per_hec'], 2, '.', ''),
                    number_format($row['remaining_fuel'], 2, '.', ''),
                    number_format($row['remaining_fuel_per_hec'], 2, '.', ''),
                    number_format($row['variance_fuel'], 2, '.', ''),
                    number_format($row['variance_fuel_per_hec'], 2, '.', ''),
                    number_format($row['total_working_hour'], 2, '.', ''),
                    number_format($row['working_hour_hec_per_hr'], 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->getReportRows($request);

        $filename = 'task-category-summary-report-' . now()->format('Ymd_His') . '.xls';

        return response()
            ->view('reports.exports.task-category-summary-excel', [
                'rows' => $rows,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ])
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }
   
}