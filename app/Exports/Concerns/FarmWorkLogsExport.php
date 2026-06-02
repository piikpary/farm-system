<?php

namespace App\Exports;

use App\Models\FarmWorkLog;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class FarmWorkLogsExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $logs = FarmWorkLog::with(['tractor', 'driver', 'zone', 'zoneBlock', 'taskCategory'])
            ->when($this->filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('tractor', function ($t) use ($search) {
                        $t->where('tractor_no', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('driver', function ($d) use ($search) {
                        $d->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('zone', function ($z) use ($search) {
                        $z->where('zone_code', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('zoneBlock', function ($b) use ($search) {
                        $b->where('block_code', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('taskCategory', function ($tc) use ($search) {
                        $tc->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('work_status', 'like', '%' . $search . '%');
                });
            })
            ->when($this->filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))
            ->when($this->filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date))
            ->when($this->filters['tractor_id'] ?? null, fn ($q, $id) => $q->where('tractor_id', $id))
            ->when($this->filters['driver_id'] ?? null, fn ($q, $id) => $q->where('driver_id', $id))
            ->when($this->filters['zone_id'] ?? null, fn ($q, $id) => $q->where('zone_id', $id))
            ->when($this->filters['task_category_id'] ?? null, fn ($q, $id) => $q->where('task_category_id', $id))
            ->latest('work_date')
            ->latest('id')
            ->get();

        $rows = [];

        $rows[] = [
            '#',
            'Date',
            'Status',
            'Tractor',
            'Driver',
            'Zone / Sub Zone',
            'Task',
            'Hour',
            'Total Area',
            'Diesel Start',
            'Diesel Refill',
            'Diesel End',
            'Diesel Used',
            'L/Ha',
            'Ha/Hr',
            'Note',
        ];

        $excelRow = 2;

        foreach ($logs as $index => $log) {
            $zoneLabel = '-';

            if ($log->zone) {
                $zoneLabel = $log->zone->zone_code;

                if ($log->zone->name) {
                    $zoneLabel .= ' - ' . $log->zone->name;
                }
            }

            if ($log->zoneBlock) {
                $zoneLabel .= ' / ' . $log->zoneBlock->block_code;

                if ($log->zoneBlock->name) {
                    $zoneLabel .= ' - ' . $log->zoneBlock->name;
                }
            } else {
                $zoneLabel .= ' / No sub zone';
            }

            $rows[] = [
                $index + 1,
                optional($log->work_date)->format('Y-m-d') ?: $log->work_date,
                ucfirst($log->work_status ?? 'pending'),
                $log->tractor->tractor_no ?? '-',
                $log->driver->name ?? '-',
                $zoneLabel,
                $log->taskCategory->name ?? '-',

                (float) ($log->working_duration ?? 0),
                (float) ($log->working_area ?? 0),
                (float) ($log->diesel_start ?? 0),
                (float) ($log->diesel_refill ?? 0),
                (float) ($log->diesel_end ?? 0),

                '=MAX((J' . $excelRow . '+K' . $excelRow . ')-L' . $excelRow . ',0)',
                '=IF(I' . $excelRow . '>0,M' . $excelRow . '/I' . $excelRow . ',0)',
                '=IF(H' . $excelRow . '>0,I' . $excelRow . '/H' . $excelRow . ',0)',

                $log->note ?? '',
            ];

            $excelRow++;
        }

        $lastDataRow = $excelRow - 1;

        if ($lastDataRow >= 2) {
            $rows[] = [
                '',
                '',
                '',
                '',
                '',
                '',
                'Total',
                '=SUM(H2:H' . $lastDataRow . ')',
                '=SUM(I2:I' . $lastDataRow . ')',
                '-',
                '=SUM(K2:K' . $lastDataRow . ')',
                '-',
                '=SUM(M2:M' . $lastDataRow . ')',
                '=IF(I' . $excelRow . '>0,M' . $excelRow . '/I' . $excelRow . ',0)',
                '=IF(H' . $excelRow . '>0,I' . $excelRow . '/H' . $excelRow . ',0)',
                '-',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle('A1:P1')->getFont()->setBold(true);

                $sheet->getStyle('H2:O' . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:P1');
            },
        ];
    }
}