<?php

namespace App\Exports;

use App\Exports\Concerns\WithFormulaCache;
use App\Models\FarmWorkLog;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;
use Maatwebsite\Excel\Events\AfterSheet;

class FarmWorkLogsExport implements FromArray, WithHeadings, WithEvents, WithPreCalculateFormulas
{
    use WithFormulaCache;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return [
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
            'L/Ha',
            'Ha/Hr',
            'GPS Distance',
            'Estimated Plowed Area',
            'GPS Progress',
            'Variance',
            'Note',
        ];
    }

    public function array(): array
    {
        $logs = FarmWorkLog::with(['tractor', 'driver', 'zone', 'taskCategory'])
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('tractor', fn ($sub) => $sub->where('tractor_no', 'like', "%{$search}%"))
                        ->orWhereHas('driver', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('zone', fn ($sub) => $sub->where('zone_code', 'like', "%{$search}%"))
                        ->orWhereHas('taskCategory', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))
            ->when($this->filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date))
            ->when($this->filters['tractor_id'] ?? null, fn ($q, $id) => $q->where('tractor_id', $id))
            ->when($this->filters['driver_id'] ?? null, fn ($q, $id) => $q->where('driver_id', $id))
            ->when($this->filters['zone_id'] ?? null, fn ($q, $id) => $q->where('zone_id', $id))
            ->when($this->filters['task_category_id'] ?? null, fn ($q, $id) => $q->where('task_category_id', $id))
            ->latest('work_date')
            ->get();

        $rows = [];

        foreach ($logs as $index => $log) {
            $excelRow = $index + 2;

            $rows[] = [
                optional($log->work_date)->format('Y-m-d'),
                ucfirst($log->work_status ?? 'pending'),
                $log->tractor->tractor_no ?? '-',
                $log->driver->name ?? '-',
                $log->zone->zone_code ?? '-',
                $log->taskCategory->name ?? '-',

                (float) ($log->working_duration ?? 0),
                (float) ($log->working_area ?? 0),
                (float) ($log->diesel_start ?? 0),
                (float) ($log->diesel_refill ?? 0),
                (float) ($log->diesel_end ?? 0),

                "=I{$excelRow}+J{$excelRow}-K{$excelRow}",
                "=IF(H{$excelRow}>0,L{$excelRow}/H{$excelRow},0)",
                "=IF(G{$excelRow}>0,H{$excelRow}/G{$excelRow},0)",

                (float) ($log->gps_distance_meters ?? 0),
                (float) ($log->estimated_plowed_area ?? 0),
                (float) ($log->gps_progress_percent ?? 0),
                "=IF(L{$excelRow}>0,0-L{$excelRow},0)",
                $log->note ?? '',
            ];
        }

        if (count($rows) > 0) {
            $totalRow = count($rows) + 2;
            $lastDataRow = $totalRow - 1;

            $rows[] = [
                'TOTAL',
                '',
                '',
                '',
                '',
                '',
                "=SUM(G2:G{$lastDataRow})",
                "=SUM(H2:H{$lastDataRow})",
                '',
                "=SUM(J2:J{$lastDataRow})",
                '',
                "=SUM(L2:L{$lastDataRow})",
                "=IF(H{$totalRow}>0,L{$totalRow}/H{$totalRow},0)",
                "=IF(G{$totalRow}>0,H{$totalRow}/G{$totalRow},0)",
                "=SUM(O2:O{$lastDataRow})",
                "=SUM(P2:P{$lastDataRow})",
                "=IF(H{$totalRow}>0,P{$totalRow}/H{$totalRow}*100,0)",
                "=SUM(R2:R{$lastDataRow})",
                '',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->registerFormulaCacheEvents($event);

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);

                if ($highestRow > 1) {
                    $sheet->getStyle("A{$highestRow}:{$highestColumn}{$highestRow}")
                        ->getFont()
                        ->setBold(true);
                }

                foreach (range('A', $highestColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->freezePane('A2');
            },
        ];
    }
}