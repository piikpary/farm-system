<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\TaskCategory;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $rows = [];

    public function mount()
    {
        $this->rows = [
            $this->emptyRow(),
        ];
    }

    public function emptyRow()
    {
        return [
            'work_date' => now()->format('Y-m-d'),
            'tractor_id' => '',
            'driver_id' => '',
            'zone_id' => '',
            'task_category_id' => '',
            'working_duration' => 0,
            'working_area' => 0,
            'diesel_start' => 0,
            'diesel_refill' => 0,
            'diesel_end' => 0,
            'note' => '',
        ];
    }

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function removeRow($index)
    {
        if (count($this->rows) <= 1) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function duplicateRow($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->rows[] = $this->rows[$index];
    }

    public function calculateDieselUsed($row)
    {
        $start = (float) ($row['diesel_start'] ?? 0);
        $refill = (float) ($row['diesel_refill'] ?? 0);
        $end = (float) ($row['diesel_end'] ?? 0);

        return max(($start + $refill) - $end, 0);
    }

    public function calculateLha($row)
    {
        $area = (float) ($row['working_area'] ?? 0);
        $diesel = $this->calculateDieselUsed($row);

        return $area > 0 ? $diesel / $area : 0;
    }

    public function calculateHaHr($row)
    {
        $hour = (float) ($row['working_duration'] ?? 0);
        $area = (float) ($row['working_area'] ?? 0);

        return $hour > 0 ? $area / $hour : 0;
    }

    public function getTotalHourProperty()
    {
        return collect($this->rows)->sum(function ($row) {
            return (float) ($row['working_duration'] ?? 0);
        });
    }

    public function getTotalAreaProperty()
    {
        return collect($this->rows)->sum(function ($row) {
            return (float) ($row['working_area'] ?? 0);
        });
    }

    public function getTotalDieselRefillProperty()
    {
        return collect($this->rows)->sum(function ($row) {
            return (float) ($row['diesel_refill'] ?? 0);
        });
    }

    public function getTotalDieselUsedProperty()
    {
        return collect($this->rows)->sum(function ($row) {
            return $this->calculateDieselUsed($row);
        });
    }

    public function getAverageLhaProperty()
    {
        return $this->totalArea > 0 ? $this->totalDieselUsed / $this->totalArea : 0;
    }

    public function getAverageHaHrProperty()
    {
        return $this->totalHour > 0 ? $this->totalArea / $this->totalHour : 0;
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('work_logs.create')) {
            abort(403, 'Permission denied.');
        }

        $this->validate([
            'rows' => 'required|array|min:1',
            'rows.*.work_date' => 'required|date',
            'rows.*.tractor_id' => 'required|exists:tractors,id',
            'rows.*.driver_id' => 'required|exists:drivers,id',
            'rows.*.zone_id' => 'required|exists:zones,id',
            'rows.*.task_category_id' => 'required|exists:task_categories,id',
            'rows.*.working_duration' => 'required|numeric|min:0.01',
            'rows.*.working_area' => 'required|numeric|min:0.01',
            'rows.*.diesel_start' => 'required|numeric|min:0',
            'rows.*.diesel_refill' => 'required|numeric|min:0',
            'rows.*.diesel_end' => 'required|numeric|min:0',
            'rows.*.note' => 'nullable|string',
        ]);

        $totalDieselUsed = collect($this->rows)->sum(function ($row) {
            return $this->calculateDieselUsed($row);
        });

        $fuelStock = FuelStock::where('status', 'active')
            ->orderByDesc('current_stock')
            ->first();

        if (!$fuelStock) {
            session()->flash('error', __('pages.no_active_stock_fuel'));
            return;
        }

        if ($fuelStock->current_stock < $totalDieselUsed) {
            session()->flash(
                'error',
                __('pages.not_enough_stock_fuel') . ' ' . number_format($fuelStock->current_stock, 2) . ' L'
            );
            return;
        }

        DB::transaction(function () use ($fuelStock) {
            foreach ($this->rows as $row) {
                $task = TaskCategory::find($row['task_category_id']);

                $dieselUsed = $this->calculateDieselUsed($row);
                $workingArea = (float) $row['working_area'];
                $workingDuration = (float) $row['working_duration'];

                $requestFuelPerHectare = $task?->standard_fuel_per_hectare ?? 0;
                $requestFuel = $workingArea * $requestFuelPerHectare;

                $dieselPerHectare = $workingArea > 0 ? $dieselUsed / $workingArea : 0;
                $hectarePerHour = $workingDuration > 0 ? $workingArea / $workingDuration : 0;
                $varianceFuel = $requestFuel - $dieselUsed;

                $workLog = FarmWorkLog::create([
                    'work_date' => $row['work_date'],
                    'tractor_id' => $row['tractor_id'],
                    'driver_id' => $row['driver_id'],
                    'zone_id' => $row['zone_id'],
                    'task_category_id' => $row['task_category_id'],
                    'working_duration' => $workingDuration,
                    'working_area' => $workingArea,
                    'diesel_start' => $row['diesel_start'],
                    'diesel_refill' => $row['diesel_refill'],
                    'diesel_end' => $row['diesel_end'],
                    'diesel_consumed' => $dieselUsed,
                    'request_fuel_per_hectare' => $requestFuelPerHectare,
                    'request_fuel' => $requestFuel,
                    'diesel_per_hectare' => $dieselPerHectare,
                    'hectare_per_hour' => $hectarePerHour,
                    'variance_fuel' => $varianceFuel,
                    'note' => $row['note'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                $fuelStock->current_stock = $fuelStock->current_stock - $dieselUsed;
                $fuelStock->updated_by = Auth::id();
                $fuelStock->save();

                FuelTransaction::create([
                    'fuel_stock_id' => $fuelStock->id,
                    'transaction_date' => $row['work_date'],
                    'type' => 'refill_to_tractor',
                    'tractor_id' => $row['tractor_id'],
                    'farm_work_log_id' => $workLog->id,
                    'quantity' => $dieselUsed,
                    'balance_after' => $fuelStock->current_stock,
                    'reference_no' => 'WORK-LOG-' . $workLog->id,
                    'note' => 'Fuel deducted from batch work log',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        });

        session()->flash('success', __('pages.work_logs_created_success'));

        return redirect()->route('farm-work-logs.index');
    }

    public function with()
    {
        return [
            'tractors' => Tractor::where('status', 'active')->orderBy('tractor_no')->get(),
            'drivers' => Driver::where('status', 'active')->orderBy('name')->get(),
            'zones' => Zone::where('status', 'active')->orderBy('zone_code')->get(),
            'taskCategories' => TaskCategory::where('status', 'active')->orderBy('name')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .excel-toolbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .excel-table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .excel-table {
            min-width: 1780px;
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        .excel-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .excel-table td {
            padding: 8px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .excel-table input,
        .excel-table select {
            width: 100%;
            min-width: 120px;
            height: 44px;
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            background: #fff;
        }

        .excel-note-input {
            min-width: 180px !important;
        }

        .excel-number {
            font-weight: 900;
            color: #0f172a;
            white-space: nowrap;
        }

        .excel-danger {
            color: #dc2626;
            font-weight: 900;
            white-space: nowrap;
        }

        .row-no {
            width: 45px;
            min-width: 45px;
            text-align: center;
            font-weight: 900;
            color: #64748b;
        }

        .excel-actions {
            display: flex;
            gap: 6px;
        }

        .work-total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .work-total-row td {
            padding: 14px 10px;
            color: #0f172a;
            white-space: nowrap;
            border-bottom: 0;
        }

        .work-total-row .total-label {
            text-align: right;
            font-weight: 900;
        }

        .work-total-row .total-diesel {
            color: #dc2626;
            font-weight: 900;
        }

        @media (max-width: 900px) {
            .excel-table {
                min-width: 1700px;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.add_work_log') }}</h1>
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

            <a href="{{ route('farm-work-logs.index') }}" class="btn gray">
                {{ __('pages.back') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <div class="excel-toolbar">
            <button type="button" wire:click="addRow" class="btn light">
                + {{ __('pages.add_row') }}
            </button>
        </div>

        <div class="excel-table-wrap">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('pages.date') }}</th>
                        <th>{{ __('pages.tractor') }}</th>
                        <th>{{ __('pages.driver') }}</th>
                        <th>{{ __('pages.zone') }}</th>
                        <th>{{ __('pages.task') }}</th>
                        <th>{{ __('pages.hour') }}</th>
                        <th>{{ __('pages.area') }}</th>
                        <th>{{ __('pages.diesel_start') }}</th>
                        <th>{{ __('pages.diesel_refill') }}</th>
                        <th>{{ __('pages.diesel_end') }}</th>
                        <th>{{ __('pages.diesel_used') }}</th>
                        <th>{{ __('pages.lha') }}</th>
                        <th>{{ __('pages.hahr') }}</th>
                        <th>{{ __('pages.note') }}</th>
                        <th>{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $index => $row)
                        @php
                            $dieselUsed = $this->calculateDieselUsed($row);
                            $lha = $this->calculateLha($row);
                            $hahr = $this->calculateHaHr($row);
                        @endphp

                        <tr>
                            <td class="row-no">{{ $index + 1 }}</td>

                            <td>
                                <input type="date" wire:model.live="rows.{{ $index }}.work_date">
                                @error("rows.$index.work_date") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.tractor_id">
                                    <option value="">{{ __('pages.select_tractor') }}</option>
                                    @foreach($tractors as $tractor)
                                        <option value="{{ $tractor->id }}">{{ $tractor->tractor_no }}</option>
                                    @endforeach
                                </select>
                                @error("rows.$index.tractor_id") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.driver_id">
                                    <option value="">{{ __('pages.select_driver') }}</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                                @error("rows.$index.driver_id") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.zone_id">
                                    <option value="">{{ __('pages.select_zone') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->zone_code }}</option>
                                    @endforeach
                                </select>
                                @error("rows.$index.zone_id") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.task_category_id">
                                    <option value="">{{ __('pages.select_task') }}</option>
                                    @foreach($taskCategories as $task)
                                        <option value="{{ $task->id }}">{{ $task->name }}</option>
                                    @endforeach
                                </select>
                                @error("rows.$index.task_category_id") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.working_duration">
                                @error("rows.$index.working_duration") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.working_area">
                                @error("rows.$index.working_area") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_start">
                                @error("rows.$index.diesel_start") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_refill">
                                @error("rows.$index.diesel_refill") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_end">
                                @error("rows.$index.diesel_end") <small>{{ $message }}</small> @enderror
                            </td>

                            <td>
                                <span class="excel-danger">
                                    {{ number_format($dieselUsed, 2) }} L
                                </span>
                            </td>

                            <td>
                                <span class="excel-number">
                                    {{ number_format($lha, 2) }}
                                </span>
                            </td>

                            <td>
                                <span class="excel-number">
                                    {{ number_format($hahr, 2) }}
                                </span>
                            </td>

                            <td>
                                <input type="text"
                                       class="excel-note-input"
                                       wire:model.live="rows.{{ $index }}.note"
                                       placeholder="{{ __('pages.note') }}">
                            </td>

                            <td>
                                <div class="excel-actions">
                                    <button type="button" wire:click="duplicateRow({{ $index }})" class="mini">
                                        {{ __('pages.copy') }}
                                    </button>

                                    <button type="button" wire:click="removeRow({{ $index }})" class="mini danger">
                                        {{ __('pages.remove') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="work-total-row">
                        <td colspan="6" class="total-label">Total</td>

                        <td>{{ number_format((float) $this->totalHour, 2) }}</td>
                        <td>{{ number_format((float) $this->totalArea, 2) }}</td>

                        <td>-</td>

                        <td>{{ number_format((float) $this->totalDieselRefill, 2) }}</td>

                        <td>-</td>

                        <td class="total-diesel">
                            {{ number_format((float) $this->totalDieselUsed, 2) }}
                        </td>

                        <td>{{ number_format((float) $this->averageLha, 2) }}</td>
                        <td>{{ number_format((float) $this->averageHaHr, 2) }}</td>

                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="btn-row">
            <button type="button" wire:click="save" class="btn">
                {{ __('pages.save_all_work_logs') }}
            </button>

            <a href="{{ route('farm-work-logs.index') }}" class="btn gray">
                {{ __('pages.cancel') }}
            </a>
        </div>
    </div>
</div>