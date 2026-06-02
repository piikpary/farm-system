<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

new class extends Component
{
    public $from_date;
    public $to_date;

    public function resetFilter()
    {
        $this->reset(['from_date', 'to_date']);
    }

    public function exportExcel()
{
    $query = FarmWorkLog::with('taskCategory')
        ->when($this->from_date, fn ($q) => $q->whereDate('work_date', '>=', $this->from_date))
        ->when($this->to_date, fn ($q) => $q->whereDate('work_date', '<=', $this->to_date))
        ->get();

    $rows = $query
        ->groupBy('task_category_id')
        ->map(function ($items) {
            $first = $items->first();

            $totalArea = $items->sum('working_area');
            $finishArea = $items->sum('working_area');
            $remainingArea = max($totalArea - $finishArea, 0);

            $requestFuel = $items->sum('request_fuel');
            $consumedFuel = $items->sum('diesel_consumed');
            $remainingFuel = $requestFuel - $consumedFuel;
            $varianceFuel = $consumedFuel - $requestFuel;

            $totalWorkingHour = $items->sum('working_duration');

            return [
                'task_category' => $first->taskCategory->name ?? '-',
                'total_area' => $totalArea,
                'finish_area' => $finishArea,
                'remaining_area' => $remainingArea,
                'request_fuel' => $requestFuel,
                'consumed_fuel' => $consumedFuel,
                'total_working_hour' => $totalWorkingHour,
            ];
        })
        ->values();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Task Summary');

    $headers = [
        'A1' => __('pages.no'),
        'B1' => __('pages.task_category'),
        'C1' => __('pages.total_area_hec'),
        'D1' => __('pages.finish_area_hec'),
        'E1' => __('pages.remaining_area_hec'),
        'F1' => __('pages.request_fuel_l_hec'),
        'G1' => __('pages.request_fuel_l'),
        'H1' => __('pages.consumed_fuel_l'),
        'I1' => __('pages.consumed_fuel_l_hec'),
        'J1' => __('pages.remaining_fuel_l'),
        'K1' => __('pages.remaining_fuel_l_hec'),
        'L1' => __('pages.variance_fuel_l'),
        'M1' => __('pages.variance_fuel_l_hec'),
        'N1' => __('pages.total_working_hour_hr'),
        'O1' => __('pages.total_working_hour_hec_hr'),
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    $rowNumber = 2;

    foreach ($rows as $index => $row) {
        $sheet->setCellValue('A' . $rowNumber, $index + 1);
        $sheet->setCellValue('B' . $rowNumber, $row['task_category']);

        $sheet->setCellValue('C' . $rowNumber, (float) $row['total_area']);
        $sheet->setCellValue('D' . $rowNumber, (float) $row['finish_area']);

        // Remaining Area = Total Area - Finish Area
        $sheet->setCellValue('E' . $rowNumber, '=MAX(C' . $rowNumber . '-D' . $rowNumber . ',0)');

        // Request Fuel / Ha = Request Fuel / Total Area
        $sheet->setCellValue('F' . $rowNumber, '=IF(C' . $rowNumber . '>0,G' . $rowNumber . '/C' . $rowNumber . ',0)');

        $sheet->setCellValue('G' . $rowNumber, (float) $row['request_fuel']);
        $sheet->setCellValue('H' . $rowNumber, (float) $row['consumed_fuel']);

        // Consumed Fuel / Ha = Consumed Fuel / Total Area
        $sheet->setCellValue('I' . $rowNumber, '=IF(C' . $rowNumber . '>0,H' . $rowNumber . '/C' . $rowNumber . ',0)');

        // Remaining Fuel = Request Fuel - Consumed Fuel
        $sheet->setCellValue('J' . $rowNumber, '=G' . $rowNumber . '-H' . $rowNumber);

        // Remaining Fuel / Ha = Remaining Fuel / Total Area
        $sheet->setCellValue('K' . $rowNumber, '=IF(C' . $rowNumber . '>0,J' . $rowNumber . '/C' . $rowNumber . ',0)');

        // Variance Fuel = Consumed Fuel - Request Fuel
        $sheet->setCellValue('L' . $rowNumber, '=H' . $rowNumber . '-G' . $rowNumber);

        // Variance Fuel / Ha = Variance Fuel / Total Area
        $sheet->setCellValue('M' . $rowNumber, '=IF(C' . $rowNumber . '>0,L' . $rowNumber . '/C' . $rowNumber . ',0)');

        $sheet->setCellValue('N' . $rowNumber, (float) $row['total_working_hour']);

        // Hectare / Hour = Total Area / Total Working Hour
        $sheet->setCellValue('O' . $rowNumber, '=IF(N' . $rowNumber . '>0,C' . $rowNumber . '/N' . $rowNumber . ',0)');

        $rowNumber++;
    }

    $lastDataRow = $rowNumber - 1;

    if ($lastDataRow >= 2) {
        $sheet->setCellValue('B' . $rowNumber, 'Total');
        $sheet->setCellValue('C' . $rowNumber, '=SUM(C2:C' . $lastDataRow . ')');
        $sheet->setCellValue('D' . $rowNumber, '=SUM(D2:D' . $lastDataRow . ')');
        $sheet->setCellValue('E' . $rowNumber, '=MAX(C' . $rowNumber . '-D' . $rowNumber . ',0)');
        $sheet->setCellValue('F' . $rowNumber, '=IF(C' . $rowNumber . '>0,G' . $rowNumber . '/C' . $rowNumber . ',0)');
        $sheet->setCellValue('G' . $rowNumber, '=SUM(G2:G' . $lastDataRow . ')');
        $sheet->setCellValue('H' . $rowNumber, '=SUM(H2:H' . $lastDataRow . ')');
        $sheet->setCellValue('I' . $rowNumber, '=IF(C' . $rowNumber . '>0,H' . $rowNumber . '/C' . $rowNumber . ',0)');
        $sheet->setCellValue('J' . $rowNumber, '=G' . $rowNumber . '-H' . $rowNumber);
        $sheet->setCellValue('K' . $rowNumber, '=IF(C' . $rowNumber . '>0,J' . $rowNumber . '/C' . $rowNumber . ',0)');
        $sheet->setCellValue('L' . $rowNumber, '=H' . $rowNumber . '-G' . $rowNumber);
        $sheet->setCellValue('M' . $rowNumber, '=IF(C' . $rowNumber . '>0,L' . $rowNumber . '/C' . $rowNumber . ',0)');
        $sheet->setCellValue('N' . $rowNumber, '=SUM(N2:N' . $lastDataRow . ')');
        $sheet->setCellValue('O' . $rowNumber, '=IF(N' . $rowNumber . '>0,C' . $rowNumber . '/N' . $rowNumber . ',0)');
    }

    $highestRow = $sheet->getHighestRow();

    $sheet->getStyle('A1:O1')->getFont()->setBold(true);
    $sheet->getStyle('A' . $highestRow . ':O' . $highestRow)->getFont()->setBold(true);

    $sheet->getStyle('C2:O' . $highestRow)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');

    foreach (range('A', 'O') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $sheet->freezePane('A2');
    $sheet->setAutoFilter('A1:O1');

    $filename = 'task-category-summary-' . now()->format('Y-m-d-His') . '.xlsx';

    return response()->streamDownload(function () use ($spreadsheet) {
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
    }, $filename, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}

    public function with()
    {
        $query = FarmWorkLog::with('taskCategory')
            ->when($this->from_date, fn ($q) => $q->whereDate('work_date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->whereDate('work_date', '<=', $this->to_date))
            ->get();

        $rows = $query
            ->groupBy('task_category_id')
            ->map(function ($items) {
                $first = $items->first();

                $totalArea = $items->sum('working_area');
                $finishArea = $items->sum('working_area');
                $remainingArea = max($totalArea - $finishArea, 0);

                $requestFuel = $items->sum('request_fuel');
                $consumedFuel = $items->sum('diesel_consumed');
                $remainingFuel = $requestFuel - $consumedFuel;
                $varianceFuel = $consumedFuel - $requestFuel;

                $totalWorkingHour = $items->sum('working_duration');

                return [
                    'task_category' => $first->taskCategory->name ?? '-',
                    'total_area' => $totalArea,
                    'finish_area' => $finishArea,
                    'remaining_area' => $remainingArea,

                    'request_fuel_per_hectare' => $totalArea > 0 ? $requestFuel / $totalArea : 0,
                    'request_fuel' => $requestFuel,

                    'consumed_fuel' => $consumedFuel,
                    'consumed_fuel_per_hectare' => $totalArea > 0 ? $consumedFuel / $totalArea : 0,

                    'remaining_fuel' => $remainingFuel,
                    'remaining_fuel_per_hectare' => $totalArea > 0 ? $remainingFuel / $totalArea : 0,

                    'variance_fuel' => $varianceFuel,
                    'variance_fuel_per_hectare' => $totalArea > 0 ? $varianceFuel / $totalArea : 0,

                    'total_working_hour' => $totalWorkingHour,
                    'hectare_per_hour' => $totalWorkingHour > 0 ? $totalArea / $totalWorkingHour : 0,
                ];
            })
            ->values();

        return [
            'rows' => $rows,
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')
    <style>
    .page {
        padding-bottom: 40px;
    }

    .panel {
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
        background: #ffffff;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        align-items: end !important;
    }

    .form-grid > div {
        min-width: 0;
    }

    .form-grid label {
        display: block;
        font-size: 13px;
        font-weight: 900;
        color: #334155;
        margin-bottom: 7px;
    }

    .form-grid input[type="date"] {
        width: 100%;
        height: 46px;
        border: 1px solid #d1d5db;
        border-radius: 13px;
        padding: 10px 13px;
        font-weight: 800;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        transition: 0.15s ease;
    }

    .form-grid input[type="date"]:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12);
    }

    .form-grid .btn,
    .form-grid button,
    .form-grid a.btn {
        width: 100%;
        height: 46px;
        border-radius: 13px;
        font-size: 13px;
        font-weight: 950;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
    }

    .form-grid button[wire\:click="$refresh"] {
        background: #15803d;
        color: #ffffff;
        border: none;
    }

    .form-grid button[wire\:click="$refresh"]:hover {
        background: #166534;
    }

    .form-grid button[wire\:click="resetFilter"] {
        background: #f1f5f9;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    .form-grid button[wire\:click="resetFilter"]:hover {
        background: #e2e8f0;
    }

    .form-grid button[wire\:click="exportExcel"] {
        background: #15803d;
        color: #ffffff;
        border: none;
    }

    .form-grid button[wire\:click="exportExcel"]:hover {
        background: #166534;
    }

    .form-grid a.btn.gray {
        background: #1f2937;
        color: #ffffff;
        border: none;
    }

    .form-grid a.btn.gray:hover {
        background: #111827;
    }

    .table-wrap {
        margin-top: 18px !important;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        overflow-x: auto;
        overflow-y: hidden;
        background: #ffffff;
        box-shadow: inset 0 -1px 0 rgba(15, 23, 42, 0.04);
    }

    .table-wrap::-webkit-scrollbar {
        height: 10px;
    }

    .table-wrap::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 999px;
    }

    .table-wrap::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
    }

    .table-wrap::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    .table-wrap table {
        width: 100%;
        min-width: 1750px;
        border-collapse: separate;
        border-spacing: 0;
        background: #ffffff;
    }

    .table-wrap th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f8fafc;
        color: #0f172a;
        font-size: 12px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .035em;
        padding: 14px 13px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        text-align: left;
    }

    .table-wrap td {
        padding: 14px 13px;
        border-bottom: 1px solid #eef2f7;
        font-size: 14px;
        font-weight: 800;
        color: #1e293b;
        white-space: nowrap;
        background: #ffffff;
    }

    .table-wrap tbody tr:hover td {
        background: #f8fafc;
    }

    .table-wrap th:first-child,
    .table-wrap td:first-child {
        position: sticky;
        left: 0;
        z-index: 4;
        background: #ffffff;
        width: 70px;
        text-align: center;
    }

    .table-wrap th:first-child {
        background: #f8fafc;
        z-index: 5;
    }

    .table-wrap th:nth-child(2),
    .table-wrap td:nth-child(2) {
        position: sticky;
        left: 70px;
        z-index: 4;
        background: #ffffff;
        min-width: 180px;
        box-shadow: 8px 0 12px rgba(15, 23, 42, 0.04);
    }

    .table-wrap th:nth-child(2) {
        background: #f8fafc;
        z-index: 5;
    }

    .table-wrap td:nth-child(n+3) {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .table-wrap td:nth-child(2) {
        text-align: left;
        font-weight: 950;
        color: #0f172a;
    }

    .table-wrap td strong {
        display: inline-flex;
        justify-content: center;
        min-width: 82px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 950;
        background: #dcfce7;
        color: #166534;
    }

    .table-wrap td strong[style*="#dc2626"] {
        background: #fee2e2;
        color: #b91c1c !important;
    }

    .empty {
        padding: 38px 20px !important;
        text-align: center !important;
        color: #64748b !important;
        font-weight: 900 !important;
        background: #f8fafc !important;
    }

    .page-title {
        letter-spacing: -0.03em;
    }

    .page-subtitle {
        max-width: 760px;
        line-height: 1.6;
    }

    @media (max-width: 1300px) {
        .form-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 800px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .page-actions {
            width: 100%;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .table-wrap table {
            min-width: 1550px;
        }
    }
</style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.task_category_summary_report') }}</h1>
            <p class="page-subtitle">{{ __('pages.task_category_summary_report_subtitle') }}</p>
        </div>

        <div class="page-actions">
            <div class="language-switcher">
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
                    EN
                </a>

                <a href="{{ route('language.switch', 'km') }}"
                   class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
                    ខ្មែរ
                </a>
            </div>

            <a href="{{ route('dashboard') }}" class="btn gray">
                {{ __('pages.dashboard_button') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="form-grid" style="align-items: end;">
            <div>
                <label>{{ __('pages.from_date') }}</label>
                <input type="date" wire:model.live="from_date">
            </div>

            <div>
                <label>{{ __('pages.to_date') }}</label>
                <input type="date" wire:model.live="to_date">
            </div>

            <div>
                <button wire:click="$refresh" class="btn">
                    {{ __('pages.filter') }}
                </button>
            </div>

            <div>
                <button wire:click="resetFilter" class="btn light">
                    {{ __('pages.reset') }}
                </button>
            </div>

            <div>
               <button type="button" wire:click="exportExcel" class="btn">
                    {{ __('pages.export_excel') }}
                </button>
            </div>

            <div>
                <a href="{{ route('reports.task-category-summary.export.csv', [
                    'from_date' => $from_date,
                    'to_date' => $to_date,
                ]) }}" class="btn gray">
                    {{ __('pages.export_csv') }}
                </a>
            </div>
        </div>

        <div class="table-wrap" style="margin-top: 18px;">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('pages.no') }}</th>
                        <th>{{ __('pages.task_category') }}</th>
                        <th>{{ __('pages.total_area_hec') }}</th>
                        <th>{{ __('pages.finish_area_hec') }}</th>
                        <th>{{ __('pages.remaining_area_hec') }}</th>
                        <th>{{ __('pages.request_fuel_l_hec') }}</th>
                        <th>{{ __('pages.request_fuel_l') }}</th>
                        <th>{{ __('pages.consumed_fuel_l') }}</th>
                        <th>{{ __('pages.consumed_fuel_l_hec') }}</th>
                        <th>{{ __('pages.remaining_fuel_l') }}</th>
                        <th>{{ __('pages.remaining_fuel_l_hec') }}</th>
                        <th>{{ __('pages.variance_fuel_l') }}</th>
                        <th>{{ __('pages.variance_fuel_l_hec') }}</th>
                        <th>{{ __('pages.total_working_hour_hr') }}</th>
                        <th>{{ __('pages.total_working_hour_hec_hr') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['task_category'] }}</td>

                            <td>{{ number_format($row['total_area'], 2) }}</td>
                            <td>{{ number_format($row['finish_area'], 2) }}</td>
                            <td>{{ number_format($row['remaining_area'], 2) }}</td>

                            <td>{{ number_format($row['request_fuel_per_hectare'], 2) }}</td>
                            <td>{{ number_format($row['request_fuel'], 2) }}</td>

                            <td>{{ number_format($row['consumed_fuel'], 2) }}</td>
                            <td>{{ number_format($row['consumed_fuel_per_hectare'], 2) }}</td>

                            <td>{{ number_format($row['remaining_fuel'], 2) }}</td>
                            <td>{{ number_format($row['remaining_fuel_per_hectare'], 2) }}</td>

                            <td>
                                <strong style="color: {{ $row['variance_fuel'] > 0 ? '#dc2626' : '#166534' }}">
                                    {{ number_format($row['variance_fuel'], 2) }}
                                </strong>
                            </td>

                            <td>
                                <strong style="color: {{ $row['variance_fuel_per_hectare'] > 0 ? '#dc2626' : '#166534' }}">
                                    {{ number_format($row['variance_fuel_per_hectare'], 2) }}
                                </strong>
                            </td>

                            <td>{{ number_format($row['total_working_hour'], 2) }}</td>
                            <td>{{ number_format($row['hectare_per_hour'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="empty">
                                {{ __('pages.no_report_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>