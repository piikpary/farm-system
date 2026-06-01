<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FarmWorkLog;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\ZoneBlock;
use App\Models\TaskCategory;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    use WithPagination;

    public $paginationTheme = 'tailwind';

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $tractorId = '';
    public $driverId = '';
    public $zoneId = '';
    public $taskCategoryId = '';
    public $perPage = 15;

    public $rows = [];
    public $editingId = null;

    public $editRow = [
        'work_date' => '',
        'work_status' => 'pending',
        'tractor_id' => '',
        'driver_id' => '',
        'zone_id' => '',
        'zone_block_id' => '',
        'zone_block_select' => '',
        'task_category_id' => '',
        'working_duration' => '',
        'working_area' => '',
        'diesel_start' => '',
        'diesel_refill' => '',
        'diesel_end' => '',
        'note' => '',
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedDateFrom() { $this->resetPage(); }
    public function updatedDateTo() { $this->resetPage(); }
    public function updatedTractorId() { $this->resetPage(); }
    public function updatedDriverId() { $this->resetPage(); }
    public function updatedZoneId() { $this->resetPage(); }
    public function updatedTaskCategoryId() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'work_date' => now()->format('Y-m-d'),
            'work_status' => 'pending',
            'tractor_id' => '',
            'driver_id' => '',
            'zone_id' => '',
            'zone_block_id' => '',
            'zone_block_select' => '',
            'task_category_id' => '',
            'working_duration' => '',
            'working_area' => '',
            'diesel_start' => '',
            'diesel_refill' => '',
            'diesel_end' => '',
            'note' => '',
        ];
    }

    public function removeRow($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function syncZoneSelection($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $value = $this->rows[$index]['zone_block_select'] ?? '';

        $this->rows[$index]['zone_id'] = '';
        $this->rows[$index]['zone_block_id'] = '';

        if (!$value) {
            return;
        }

        if (str_starts_with((string) $value, 'zone_')) {
            $this->rows[$index]['zone_id'] = (int) str_replace('zone_', '', $value);
            $this->rows[$index]['zone_block_id'] = '';
            return;
        }

        $block = ZoneBlock::find($value);

        if ($block) {
            $this->rows[$index]['zone_id'] = $block->zone_id;
            $this->rows[$index]['zone_block_id'] = $block->id;
        }
    }

    public function syncEditZoneSelection()
    {
        $value = $this->editRow['zone_block_select'] ?? '';

        $this->editRow['zone_id'] = '';
        $this->editRow['zone_block_id'] = '';

        if (!$value) {
            return;
        }

        if (str_starts_with((string) $value, 'zone_')) {
            $this->editRow['zone_id'] = (int) str_replace('zone_', '', $value);
            $this->editRow['zone_block_id'] = '';
            return;
        }

        $block = ZoneBlock::find($value);

        if ($block) {
            $this->editRow['zone_id'] = $block->zone_id;
            $this->editRow['zone_block_id'] = $block->id;
        }
    }

    public function calculateRow($row)
    {
        $workingDuration = (float) ($row['working_duration'] ?: 0);
        $workingArea = (float) ($row['working_area'] ?: 0);
        $dieselStart = (float) ($row['diesel_start'] ?: 0);
        $dieselRefill = (float) ($row['diesel_refill'] ?: 0);
        $dieselEnd = (float) ($row['diesel_end'] ?: 0);

        $dieselUsed = ($dieselStart + $dieselRefill) - $dieselEnd;
        $dieselUsed = max($dieselUsed, 0);

        $literPerHa = $workingArea > 0 ? $dieselUsed / $workingArea : 0;
        $haPerHr = $workingDuration > 0 ? $workingArea / $workingDuration : 0;

        return [
            'diesel_consumed' => $dieselUsed,
            'diesel_per_hectare' => $literPerHa,
            'hectare_per_hour' => $haPerHr,
        ];
    }

    private function getActiveFuelStock()
    {
        return FuelStock::where('status', 'active')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    private function deductFuelStock($amount, $tractorId = null, $workLogId = null, $date = null)
    {
        $amount = (float) $amount;

        if ($amount <= 0) {
            return;
        }

        $fuelStock = $this->getActiveFuelStock();

        if (!$fuelStock) {
            throw new \Exception('No active fuel stock found. Please create fuel stock first.');
        }

        if ((float) $fuelStock->current_stock < $amount) {
            throw new \Exception('Not enough fuel stock. Current stock: ' . number_format((float) $fuelStock->current_stock, 2) . ' L');
        }

        $newBalance = (float) $fuelStock->current_stock - $amount;

        $fuelStock->update([
            'current_stock' => $newBalance,
            'total_stock_out' => (float) $fuelStock->total_stock_out + $amount,
            'updated_by' => Auth::id(),
        ]);

        FuelTransaction::create([
            'fuel_stock_id' => $fuelStock->id,
            'tractor_id' => $tractorId,
            'farm_work_log_id' => $workLogId,
            'type' => 'refill_to_tractor',
            'quantity' => $amount,
            'balance_after' => $newBalance,
            'reference_no' => 'WORKLOG-' . $workLogId,
            'transaction_date' => $date ?: now()->toDateString(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'note' => 'Fuel deducted from work log #' . $workLogId,
        ]);
    }

    private function returnFuelStock($amount, $tractorId = null, $workLogId = null, $date = null)
    {
        $amount = (float) $amount;

        if ($amount <= 0) {
            return;
        }

        $fuelStock = $this->getActiveFuelStock();

        if (!$fuelStock) {
            throw new \Exception('No active fuel stock found. Please create fuel stock first.');
        }

        $newBalance = (float) $fuelStock->current_stock + $amount;

        $fuelStock->update([
            'current_stock' => $newBalance,
            'total_stock_out' => max(((float) $fuelStock->total_stock_out - $amount), 0),
            'updated_by' => Auth::id(),
        ]);

        FuelTransaction::create([
            'fuel_stock_id' => $fuelStock->id,
            'tractor_id' => $tractorId,
            'farm_work_log_id' => $workLogId,
            'type' => 'adjustment',
            'quantity' => $amount,
            'balance_after' => $newBalance,
            'reference_no' => 'RETURN-WORKLOG-' . $workLogId . '-' . now()->format('YmdHis'),
            'transaction_date' => $date ?: now()->toDateString(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'note' => 'Fuel returned from work log #' . $workLogId,
        ]);
    }

    public function saveRow($index)
    {
        if (!auth()->user()->hasPermission('work_logs.create')) {
            abort(403, 'Permission denied.');
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->syncZoneSelection($index);

        $this->validate([
            "rows.$index.work_date" => 'required|date',
            "rows.$index.work_status" => 'required|in:pending,working,paused,finished,problem',
            "rows.$index.tractor_id" => 'required|exists:tractors,id',
            "rows.$index.driver_id" => 'required|exists:drivers,id',
            "rows.$index.zone_id" => 'required|exists:zones,id',
            "rows.$index.zone_block_id" => 'nullable|exists:zone_blocks,id',
            "rows.$index.task_category_id" => 'required|exists:task_categories,id',
            "rows.$index.working_duration" => 'nullable|numeric|min:0',
            "rows.$index.working_area" => 'nullable|numeric|min:0',
            "rows.$index.diesel_start" => 'nullable|numeric|min:0',
            "rows.$index.diesel_refill" => 'nullable|numeric|min:0',
            "rows.$index.diesel_end" => 'nullable|numeric|min:0',
            "rows.$index.note" => 'nullable|string|max:2000',
        ]);

        $row = $this->rows[$index];
        $calculated = $this->calculateRow($row);

        try {
            DB::transaction(function () use ($row, $calculated) {
                $workLog = FarmWorkLog::create([
                    'work_date' => $row['work_date'],
                    'work_status' => $row['work_status'],
                    'tractor_id' => $row['tractor_id'],
                    'driver_id' => $row['driver_id'],
                    'zone_id' => $row['zone_id'],
                    'zone_block_id' => $row['zone_block_id'] ?: null,
                    'task_category_id' => $row['task_category_id'],
                    'working_duration' => $row['working_duration'] ?: 0,
                    'working_area' => $row['working_area'] ?: 0,
                    'diesel_start' => $row['diesel_start'] ?: 0,
                    'diesel_refill' => $row['diesel_refill'] ?: 0,
                    'diesel_end' => $row['diesel_end'] ?: 0,
                    'diesel_consumed' => $calculated['diesel_consumed'],
                    'diesel_per_hectare' => $calculated['diesel_per_hectare'],
                    'hectare_per_hour' => $calculated['hectare_per_hour'],
                    'gps_distance_meters' => 0,
                    'estimated_plowed_area' => 0,
                    'gps_progress_percent' => 0,
                    'note' => $row['note'] ?: null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                $this->deductFuelStock(
                    $calculated['diesel_consumed'],
                    $row['tractor_id'],
                    $workLog->id,
                    $row['work_date']
                );
            });

            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);

            session()->flash('success', 'Work log saved successfully, fuel stock deducted, and history created.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('work_logs.edit')) {
            abort(403, 'Permission denied.');
        }

        $log = FarmWorkLog::findOrFail($id);

        $this->editingId = $log->id;

        $zoneSelect = $log->zone_block_id
            ? (string) $log->zone_block_id
            : ($log->zone_id ? 'zone_' . $log->zone_id : '');

        $this->editRow = [
            'work_date' => optional($log->work_date)->format('Y-m-d') ?: $log->work_date,
            'work_status' => $log->work_status,
            'tractor_id' => $log->tractor_id,
            'driver_id' => $log->driver_id,
            'zone_id' => $log->zone_id,
            'zone_block_id' => $log->zone_block_id,
            'zone_block_select' => $zoneSelect,
            'task_category_id' => $log->task_category_id,
            'working_duration' => $log->working_duration,
            'working_area' => $log->working_area,
            'diesel_start' => $log->diesel_start,
            'diesel_refill' => $log->diesel_refill,
            'diesel_end' => $log->diesel_end,
            'note' => $log->note,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'work_date' => '',
            'work_status' => 'pending',
            'tractor_id' => '',
            'driver_id' => '',
            'zone_id' => '',
            'zone_block_id' => '',
            'zone_block_select' => '',
            'task_category_id' => '',
            'working_duration' => '',
            'working_area' => '',
            'diesel_start' => '',
            'diesel_refill' => '',
            'diesel_end' => '',
            'note' => '',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('work_logs.edit')) {
            abort(403, 'Permission denied.');
        }

        $this->syncEditZoneSelection();

        $log = FarmWorkLog::findOrFail($this->editingId);

        $this->validate([
            'editRow.work_date' => 'required|date',
            'editRow.work_status' => 'required|in:pending,working,paused,finished,problem',
            'editRow.tractor_id' => 'required|exists:tractors,id',
            'editRow.driver_id' => 'required|exists:drivers,id',
            'editRow.zone_id' => 'required|exists:zones,id',
            'editRow.zone_block_id' => 'nullable|exists:zone_blocks,id',
            'editRow.task_category_id' => 'required|exists:task_categories,id',
            'editRow.working_duration' => 'nullable|numeric|min:0',
            'editRow.working_area' => 'nullable|numeric|min:0',
            'editRow.diesel_start' => 'nullable|numeric|min:0',
            'editRow.diesel_refill' => 'nullable|numeric|min:0',
            'editRow.diesel_end' => 'nullable|numeric|min:0',
            'editRow.note' => 'nullable|string|max:2000',
        ]);

        $calculated = $this->calculateRow($this->editRow);
        $oldDieselUsed = (float) $log->diesel_consumed;

        try {
            DB::transaction(function () use ($log, $calculated, $oldDieselUsed) {
                $newDieselUsed = (float) $calculated['diesel_consumed'];
                $difference = $newDieselUsed - $oldDieselUsed;

                if ($difference > 0) {
                    $this->deductFuelStock(
                        $difference,
                        $this->editRow['tractor_id'],
                        $log->id,
                        $this->editRow['work_date']
                    );
                }

                if ($difference < 0) {
                    $this->returnFuelStock(
                        abs($difference),
                        $this->editRow['tractor_id'],
                        $log->id,
                        $this->editRow['work_date']
                    );
                }

                $log->update([
                    'work_date' => $this->editRow['work_date'],
                    'work_status' => $this->editRow['work_status'],
                    'tractor_id' => $this->editRow['tractor_id'],
                    'driver_id' => $this->editRow['driver_id'],
                    'zone_id' => $this->editRow['zone_id'],
                    'zone_block_id' => $this->editRow['zone_block_id'] ?: null,
                    'task_category_id' => $this->editRow['task_category_id'],
                    'working_duration' => $this->editRow['working_duration'] ?: 0,
                    'working_area' => $this->editRow['working_area'] ?: 0,
                    'diesel_start' => $this->editRow['diesel_start'] ?: 0,
                    'diesel_refill' => $this->editRow['diesel_refill'] ?: 0,
                    'diesel_end' => $this->editRow['diesel_end'] ?: 0,
                    'diesel_consumed' => $newDieselUsed,
                    'diesel_per_hectare' => $calculated['diesel_per_hectare'],
                    'hectare_per_hour' => $calculated['hectare_per_hour'],
                    'gps_distance_meters' => $log->gps_distance_meters ?? 0,
                    'estimated_plowed_area' => $log->estimated_plowed_area ?? 0,
                    'gps_progress_percent' => $log->gps_progress_percent ?? 0,
                    'note' => $this->editRow['note'] ?: null,
                    'updated_by' => Auth::id(),
                ]);
            });

            $this->cancelEdit();

            session()->flash('success', 'Work log updated, fuel stock adjusted, and history created.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('work_logs.delete')) {
            abort(403, 'Permission denied.');
        }

        try {
            DB::transaction(function () use ($id) {
                $log = FarmWorkLog::findOrFail($id);

                $this->returnFuelStock(
                    (float) $log->diesel_consumed,
                    $log->tractor_id,
                    $log->id,
                    $log->work_date
                );

                $log->delete();
            });

            session()->flash('success', 'Work log deleted, fuel returned, and history created.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function resetFilter()
    {
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->tractorId = '';
        $this->driverId = '';
        $this->zoneId = '';
        $this->taskCategoryId = '';
        $this->perPage = 15;

        $this->resetPage();
    }

    private function logsQuery()
    {
        return FarmWorkLog::with(['tractor', 'driver', 'zone', 'zoneBlock', 'taskCategory'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->whereHas('tractor', function ($t) {
                        $t->where('tractor_no', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('driver', function ($d) {
                        $d->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('zone', function ($z) {
                        $z->where('zone_code', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('zoneBlock', function ($b) {
                        $b->where('block_code', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('taskCategory', function ($tc) {
                        $tc->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('work_status', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('work_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('work_date', '<=', $this->dateTo))
            ->when($this->tractorId, fn ($q) => $q->where('tractor_id', $this->tractorId))
            ->when($this->driverId, fn ($q) => $q->where('driver_id', $this->driverId))
            ->when($this->zoneId, fn ($q) => $q->where('zone_id', $this->zoneId))
            ->when($this->taskCategoryId, fn ($q) => $q->where('task_category_id', $this->taskCategoryId));
    }

    public function getLogsProperty()
    {
        return $this->logsQuery()
            ->latest('work_date')
            ->latest('id')
            ->paginate((int) $this->perPage);
    }

    public function getTotalHoursProperty()
    {
        return (clone $this->logsQuery())->sum('working_duration');
    }

    public function getTotalAreaProperty()
    {
        return (clone $this->logsQuery())->sum('working_area');
    }

    public function getTotalDieselRefillProperty()
    {
        return (clone $this->logsQuery())->sum('diesel_refill');
    }

    public function getTotalDieselUsedProperty()
    {
        return (clone $this->logsQuery())->sum('diesel_consumed');
    }

    public function getAvgLiterPerHaProperty()
    {
        return $this->totalArea > 0 ? $this->totalDieselUsed / $this->totalArea : 0;
    }

    public function getAvgHaPerHrProperty()
    {
        return $this->totalHours > 0 ? $this->totalArea / $this->totalHours : 0;
    }

    public function with()
    {
        return [
            'tractors' => Tractor::where('status', 'active')->orderBy('tractor_no')->get(),
            'drivers' => Driver::where('status', 'active')->orderBy('name')->get(),
            'zones' => Zone::where('status', 'active')->orderBy('zone_code')->get(),
            'zoneBlocks' => ZoneBlock::with('zone')->where('status', 'active')->orderBy('block_code')->get(),
            'taskCategories' => TaskCategory::where('status', 'active')->orderBy('name')->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .filter-panel { margin-bottom:18px; }
        .filter-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .filter-grid label { display:block; font-weight:900; font-size:13px; margin-bottom:6px; color:#334155; }
        .filter-grid input, .filter-grid select { width:100%; height:46px; border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; font-weight:700; background:#ffffff; }

        .list-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
            flex-wrap:wrap;
            margin-bottom:14px;
        }

        .rows-control {
            display:flex;
            align-items:center;
            gap:10px;
        }

        .rows-control label {
            font-size:13px;
            font-weight:900;
            color:#334155;
            margin:0;
        }

        .rows-control select {
            width:130px;
            height:40px;
            border:1px solid #d1d5db;
            border-radius:10px;
            padding:8px 10px;
            font-weight:800;
            background:#ffffff;
        }

        .table-wrap { overflow-x:auto; border:1px solid #e5e7eb; border-radius:16px; }
        .work-table { width:100%; min-width:1600px; border-collapse:collapse; background:#ffffff; }
        .work-table th { background:#f8fafc; color:#0f172a; font-size:12px; font-weight:900; text-transform:uppercase; padding:12px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
        .work-table td { padding:10px; border-bottom:1px solid #eef2f7; vertical-align:middle; white-space:nowrap; }
        .work-table input, .work-table select { width:100%; min-width:125px; height:42px; padding:8px 10px; border:1px solid #d1d5db; border-radius:10px; font-size:13px; background:#ffffff; font-weight:700; }

        .zone-combo { min-width:300px; }
        .zone-block-select { min-width:280px !important; width:100% !important; border-color:#bbf7d0 !important; background:#ffffff !important; }
        .zone-display { font-weight:900; color:#0f172a; }
        .sub-zone-display { display:block; margin-top:4px; font-size:12px; font-weight:900; color:#15803d; }

        .row-no { width:45px; min-width:45px; text-align:center; font-weight:900; color:#64748b; }
        .new-row { background:#f0fdf4; }
        .new-row td { border-bottom:1px solid #bbf7d0; }

        .table-actions { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
        .total-row { background:#f8fafc; font-weight:900; border-top:2px solid #d1d5db; }
        .total-row td { border-bottom:0; padding:14px 10px; color:#0f172a; }

        .plus-cell { width:34px; height:34px; border:none; border-radius:10px; background:#16a34a; color:#ffffff; font-size:20px; font-weight:900; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
        .plus-cell:hover { background:#15803d; }
        .danger-plus { background:#dc2626; }
        .danger-plus:hover { background:#b91c1c; }

        .error { display:block; color:#dc2626; font-size:12px; margin-top:4px; font-weight:700; }
        .status-text { font-weight:900; text-transform:capitalize; }

        .pagination-wrap { padding:14px; border-top:1px solid #e5e7eb; background:#ffffff; }
        .pagination-wrap nav { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .pagination-wrap a, .pagination-wrap span { font-weight:800; }

        @media (max-width:1200px) { .filter-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
        @media (max-width:900px) { .filter-grid { grid-template-columns:1fr; } }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">Farm Work Logs</h1>
            <p class="page-subtitle">Daily tractor work, area, fuel, and productivity records.</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('farm-work-logs.export.csv') }}" class="btn gray">Export CSV</a>
            <a href="{{ route('farm-work-logs.export.excel') }}" class="btn gray">Export Excel</a>
            <a href="{{ route('dashboard') }}" class="btn gray">Dashboard</a>
        </div>
    </div>

    <div class="panel filter-panel">
        <h2 class="panel-title">Filter</h2>

        <div class="filter-grid">
            <div>
                <label>Search</label>
                <input type="text" wire:model.live="search" placeholder="Search tractor, driver, zone, sub zone, task">
            </div>

            <div>
                <label>Date From</label>
                <input type="date" wire:model.live="dateFrom">
            </div>

            <div>
                <label>Date To</label>
                <input type="date" wire:model.live="dateTo">
            </div>

            <div>
                <label>Tractor</label>
                <select wire:model.live="tractorId">
                    <option value="">All Tractors</option>
                    @foreach($tractors as $tractor)
                        <option value="{{ $tractor->id }}">{{ $tractor->tractor_no }} {{ $tractor->name ? '- ' . $tractor->name : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Driver</label>
                <select wire:model.live="driverId">
                    <option value="">All Drivers</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Zone</label>
                <select wire:model.live="zoneId">
                    <option value="">All Zones</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->zone_code }} {{ $zone->name ? '- ' . $zone->name : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Task Category</label>
                <select wire:model.live="taskCategoryId">
                    <option value="">All Tasks</option>
                    @foreach($taskCategories as $taskCategory)
                        <option value="{{ $taskCategory->id }}">{{ $taskCategory->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-top:14px;">
            <button type="button" wire:click="resetFilter" class="btn gray">Reset Filter</button>
        </div>
    </div>

    <div class="panel">
        <div class="list-header">
            <h2 class="panel-title" style="margin:0;">Work Log List</h2>

            <div class="rows-control">
                <label>Rows Per Page</label>
                <select wire:model.live="perPage">
                    <option value="10">10 rows</option>
                    <option value="15">15 rows</option>
                    <option value="25">25 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>
            </div>
        </div>

        <div class="table-wrap">
            <table class="work-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Tractor</th>
                        <th>Driver</th>
                        <th>Zone / Sub Zone</th>
                        <th>Task</th>
                        <th>Hour</th>
                        <th>Total Area</th>
                        <th>Diesel Start</th>
                        <th>Diesel Refill</th>
                        <th>Diesel End</th>
                        <th>Diesel Used</th>
                        <th>L/Ha</th>
                        <th>Ha/Hr</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->logs as $log)
                        @if($editingId === $log->id)
                            <tr class="new-row">
                                <td class="row-no">{{ ($this->logs->firstItem() ?? 1) + $loop->index }}</td>

                                <td><input type="date" wire:model.live="editRow.work_date"></td>

                                <td>
                                    <select wire:model.live="editRow.work_status">
                                        <option value="pending">Pending</option>
                                        <option value="working">Working</option>
                                        <option value="paused">Paused</option>
                                        <option value="finished">Finished</option>
                                        <option value="problem">Problem</option>
                                    </select>
                                </td>

                                <td>
                                    <select wire:model.live="editRow.tractor_id">
                                        <option value="">Select Tractor</option>
                                        @foreach($tractors as $tractor)
                                            <option value="{{ $tractor->id }}">{{ $tractor->tractor_no }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select wire:model.live="editRow.driver_id">
                                        <option value="">Select Driver</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="zone-combo">
                                    <select wire:model.live="editRow.zone_block_select" wire:change="syncEditZoneSelection" class="zone-block-select">
                                        <option value="">Select Zone / Sub Zone</option>

                                        @foreach($zones as $zone)
                                            <option value="zone_{{ $zone->id }}">
                                                {{ $zone->zone_code }}{{ $zone->name ? ' - ' . $zone->name : '' }} / No Sub Zone
                                            </option>

                                            @foreach($zoneBlocks->where('zone_id', $zone->id) as $block)
                                                <option value="{{ $block->id }}">
                                                    {{ $zone->zone_code }}{{ $zone->name ? ' - ' . $zone->name : '' }}
                                                    / {{ $block->block_code }}{{ $block->name ? ' - ' . $block->name : '' }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select wire:model.live="editRow.task_category_id">
                                        <option value="">Select Task</option>
                                        @foreach($taskCategories as $taskCategory)
                                            <option value="{{ $taskCategory->id }}">{{ $taskCategory->name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td><input type="number" step="0.01" wire:model.live="editRow.working_duration"></td>
                                <td><input type="number" step="0.01" wire:model.live="editRow.working_area"></td>
                                <td><input type="number" step="0.01" wire:model.live="editRow.diesel_start"></td>
                                <td><input type="number" step="0.01" wire:model.live="editRow.diesel_refill"></td>
                                <td><input type="number" step="0.01" wire:model.live="editRow.diesel_end"></td>

                                @php($calc = $this->calculateRow($editRow))

                                <td><strong>{{ number_format((float) $calc['diesel_consumed'], 2) }}</strong></td>
                                <td>{{ number_format((float) $calc['diesel_per_hectare'], 2) }}</td>
                                <td>{{ number_format((float) $calc['hectare_per_hour'], 2) }}</td>

                                <td>
                                    <div class="table-actions">
                                        <button type="button" wire:click="updateRow" class="mini">Save</button>
                                        <button type="button" wire:click="cancelEdit" class="mini danger">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="row-no">{{ ($this->logs->firstItem() ?? 1) + $loop->index }}</td>
                                <td>{{ optional($log->work_date)->format('d M Y') ?: $log->work_date }}</td>
                                <td><span class="status-text">{{ $log->work_status }}</span></td>
                                <td>{{ $log->tractor->tractor_no ?? '-' }}</td>
                                <td>{{ $log->driver->name ?? '-' }}</td>

                                <td>
                                    <span class="zone-display">
                                        {{ $log->zone->zone_code ?? '-' }}
                                        {{ $log->zone && $log->zone->name ? '- ' . $log->zone->name : '' }}
                                    </span>

                                    @if($log->zoneBlock)
                                        <span class="sub-zone-display">
                                            ↳ {{ $log->zoneBlock->block_code }}
                                            {{ $log->zoneBlock->name ? '- ' . $log->zoneBlock->name : '' }}
                                        </span>
                                    @else
                                        <span class="sub-zone-display" style="color:#94a3b8;">↳ No sub zone</span>
                                    @endif
                                </td>

                                <td>{{ $log->taskCategory->name ?? '-' }}</td>
                                <td>{{ number_format((float) $log->working_duration, 2) }}</td>
                                <td>{{ number_format((float) $log->working_area, 2) }}</td>
                                <td>{{ number_format((float) $log->diesel_start, 2) }}</td>
                                <td>{{ number_format((float) $log->diesel_refill, 2) }}</td>
                                <td>{{ number_format((float) $log->diesel_end, 2) }}</td>
                                <td><strong>{{ number_format((float) $log->diesel_consumed, 2) }}</strong></td>
                                <td>{{ number_format((float) $log->diesel_per_hectare, 2) }}</td>
                                <td>{{ number_format((float) $log->hectare_per_hour, 2) }}</td>

                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('work_logs.edit'))
                                            <button type="button" wire:click="edit({{ $log->id }})" class="mini">Edit</button>
                                        @endif

                                        @if(auth()->user()->hasPermission('work_logs.delete'))
                                            <button type="button" wire:click="delete({{ $log->id }})" class="mini danger" onclick="return confirm('Delete this work log?')">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(count($rows) === 0)
                            <tr>
                                <td colspan="16" class="empty">No work log found.</td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr class="new-row">
                            <td class="row-no">
                                <button type="button" wire:click="removeRow({{ $index }})" class="plus-cell danger-plus">×</button>
                            </td>

                            <td><input type="date" wire:model.live="rows.{{ $index }}.work_date"></td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.work_status">
                                    <option value="pending">Pending</option>
                                    <option value="working">Working</option>
                                    <option value="paused">Paused</option>
                                    <option value="finished">Finished</option>
                                    <option value="problem">Problem</option>
                                </select>
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.tractor_id">
                                    <option value="">Select Tractor</option>
                                    @foreach($tractors as $tractor)
                                        <option value="{{ $tractor->id }}">{{ $tractor->tractor_no }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.driver_id">
                                    <option value="">Select Driver</option>
                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="zone-combo">
                                <select wire:model.live="rows.{{ $index }}.zone_block_select" wire:change="syncZoneSelection({{ $index }})" class="zone-block-select">
                                    <option value="">Select Zone / Sub Zone</option>

                                    @foreach($zones as $zone)
                                        <option value="zone_{{ $zone->id }}">
                                            {{ $zone->zone_code }}{{ $zone->name ? ' - ' . $zone->name : '' }} / No Sub Zone
                                        </option>

                                        @foreach($zoneBlocks->where('zone_id', $zone->id) as $block)
                                            <option value="{{ $block->id }}">
                                                {{ $zone->zone_code }}{{ $zone->name ? ' - ' . $zone->name : '' }}
                                                / {{ $block->block_code }}{{ $block->name ? ' - ' . $block->name : '' }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.task_category_id">
                                    <option value="">Select Task</option>
                                    @foreach($taskCategories as $taskCategory)
                                        <option value="{{ $taskCategory->id }}">{{ $taskCategory->name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.working_duration"></td>
                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.working_area"></td>
                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_start"></td>
                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_refill"></td>
                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_end"></td>

                            @php($calc = $this->calculateRow($row))

                            <td><strong>{{ number_format((float) $calc['diesel_consumed'], 2) }}</strong></td>
                            <td>{{ number_format((float) $calc['diesel_per_hectare'], 2) }}</td>
                            <td>{{ number_format((float) $calc['hectare_per_hour'], 2) }}</td>

                            <td>
                                <div class="table-actions">
                                    <button type="button" wire:click="saveRow({{ $index }})" class="mini">Save</button>
                                    <button type="button" wire:click="removeRow({{ $index }})" class="mini danger">Remove</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td>
                            @if(auth()->user()->hasPermission('work_logs.create'))
                                <button type="button" wire:click="addRow" class="plus-cell">+</button>
                            @else
                                -
                            @endif
                        </td>

                        <td colspan="6" style="text-align:right;">Total</td>
                        <td>{{ number_format((float) $this->totalHours, 2) }}</td>
                        <td>{{ number_format((float) $this->totalArea, 2) }}</td>
                        <td>-</td>
                        <td>{{ number_format((float) $this->totalDieselRefill, 2) }}</td>
                        <td>-</td>
                        <td>{{ number_format((float) $this->totalDieselUsed, 2) }}</td>
                        <td>{{ number_format((float) $this->avgLiterPerHa, 2) }}</td>
                        <td>{{ number_format((float) $this->avgHaPerHr, 2) }}</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>

            <div class="pagination-wrap">
                {{ $this->logs->links() }}
            </div>
        </div>
    </div>
</div>