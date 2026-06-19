<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\FarmWorkPlan;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\ZoneBlock;
use App\Models\TaskCategory;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

new class extends Component
{
    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $tractorId = '';
    public $driverId = '';
    public $zoneId = '';
    public $taskCategoryId = '';
    public $workPlanId = '';
    public $perPage = 15;

    public $rows = [];
    public $editingId = null;

    public $editRow = [
        'farm_work_plan_id' => '',
        'task_category_group_name' => '',
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

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'farm_work_plan_id' => '',
            'task_category_group_name' => '',
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

        $block = ZoneBlock::find($value);

        if ($block) {
            $this->editRow['zone_id'] = $block->zone_id;
            $this->editRow['zone_block_id'] = $block->id;
        }
    }

    public function formatZoneBlockLabel($block)
    {
        if (!$block) {
            return '-';
        }

        $zoneCode = optional($block->zone)->zone_code;

        return ($zoneCode ? $zoneCode . '.' : '') . $block->block_code;
    }

    public function getWorkPlanTaskCategories($planId)
    {
        if (!$planId) {
            return collect();
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])->find($planId);

        if (!$plan) {
            return collect();
        }

        $taskCategories = $plan->activities
            ->map(fn ($activity) => $activity->taskCategory)
            ->filter()
            ->unique('id')
            ->values();

        if ($taskCategories->isEmpty() && $plan->taskCategory) {
            $taskCategories = collect([$plan->taskCategory]);
        }

        return $taskCategories;
    }

    public function getWorkPlanTaskGroupName($planId): string
    {
        if (!$planId) {
            return '';
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])->find($planId);

        if (!$plan) {
            return '';
        }

        $taskCategory = $plan->activities->first()?->taskCategory
            ?? $plan->taskCategory;

        return $taskCategory?->group?->name
            ?? $plan->title
            ?? '';
    }

    public function getWorkPlanZoneBlocks($planId)
    {
        if (!$planId) {
            return collect();
        }

        $plan = FarmWorkPlan::find($planId);

        if (!$plan) {
            return collect();
        }

        $zoneBlockIds = is_array($plan->zone_block_ids)
            ? $plan->zone_block_ids
            : [];

        if (empty($zoneBlockIds)) {
            return collect();
        }

        return ZoneBlock::with('zone')
            ->whereIn('id', $zoneBlockIds)
            ->where('status', 'active')
            ->orderBy('zone_id')
            ->orderBy('block_code')
            ->get();
    }

    public function applyWorkPlanToRow($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $planId = $this->rows[$index]['farm_work_plan_id'] ?? null;

        $this->rows[$index]['task_category_group_name'] = '';
        $this->rows[$index]['task_category_id'] = '';
        $this->rows[$index]['zone_id'] = '';
        $this->rows[$index]['zone_block_id'] = '';
        $this->rows[$index]['zone_block_select'] = '';

        if (!$planId) {
            return;
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])->find($planId);

        if (!$plan) {
            return;
        }

        $taskCategories = $plan->activities
            ->map(fn ($activity) => $activity->taskCategory)
            ->filter()
            ->unique('id')
            ->values();

        if ($taskCategories->isEmpty() && $plan->taskCategory) {
            $taskCategories = collect([$plan->taskCategory]);
        }

        $firstTaskCategory = $taskCategories->first();

        $this->rows[$index]['task_category_group_name'] =
            $firstTaskCategory?->group?->name
            ?? $plan->title
            ?? '';

        if ($firstTaskCategory) {
            $this->rows[$index]['task_category_id'] =
                (string) $firstTaskCategory->id;
        }

        $this->rows[$index]['work_date'] =
            optional($plan->plan_date)->format('Y-m-d')
            ?: now()->format('Y-m-d');

        $blocks = $this->getWorkPlanZoneBlocks($planId);

        if ($blocks->isNotEmpty()) {
            $block = $blocks->first();

            $this->rows[$index]['zone_id'] = $block->zone_id;
            $this->rows[$index]['zone_block_id'] = $block->id;
            $this->rows[$index]['zone_block_select'] = (string) $block->id;
        }
    }

    public function applyWorkPlanToEdit()
    {
        $planId = $this->editRow['farm_work_plan_id'] ?? null;

        $this->editRow['task_category_group_name'] = '';
        $this->editRow['task_category_id'] = '';
        $this->editRow['zone_id'] = '';
        $this->editRow['zone_block_id'] = '';
        $this->editRow['zone_block_select'] = '';

        if (!$planId) {
            return;
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])->find($planId);

        if (!$plan) {
            return;
        }

        $taskCategories = $plan->activities
            ->map(fn ($activity) => $activity->taskCategory)
            ->filter()
            ->unique('id')
            ->values();

        if ($taskCategories->isEmpty() && $plan->taskCategory) {
            $taskCategories = collect([$plan->taskCategory]);
        }

        $firstTaskCategory = $taskCategories->first();

        $this->editRow['task_category_group_name'] =
            $firstTaskCategory?->group?->name
            ?? $plan->title
            ?? '';

        if ($firstTaskCategory) {
            $this->editRow['task_category_id'] =
                (string) $firstTaskCategory->id;
        }

        $this->editRow['work_date'] =
            optional($plan->plan_date)->format('Y-m-d')
            ?: now()->format('Y-m-d');

        $blocks = $this->getWorkPlanZoneBlocks($planId);

        if ($blocks->isNotEmpty()) {
            $block = $blocks->first();

            $this->editRow['zone_id'] = $block->zone_id;
            $this->editRow['zone_block_id'] = $block->id;
            $this->editRow['zone_block_select'] = (string) $block->id;
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

        $literPerHa = $workingArea > 0
            ? $dieselUsed / $workingArea
            : 0;

        $haPerHr = $workingDuration > 0
            ? $workingArea / $workingDuration
            : 0;

        return [
            'diesel_consumed' => $dieselUsed,
            'diesel_per_hectare' => $literPerHa,
            'hectare_per_hour' => $haPerHr,
        ];
    }

    private function deductFuelStock(
        $amount,
        $tractorId = null,
        $workLogId = null,
        $date = null
    ) {
        $amount = (float) $amount;

        if ($amount <= 0) {
            return;
        }

        $totalAvailable = FuelStock::where('status', 'active')
            ->where('current_stock', '>', 0)
            ->sum('current_stock');

        if ((float) $totalAvailable < $amount) {
            throw new \Exception(
                'Not enough fuel stock. Current stock: '
                . number_format((float) $totalAvailable, 2)
                . ' L'
            );
        }

        $remainingAmount = $amount;

        $fuelStocks = FuelStock::where('status', 'active')
            ->where('current_stock', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($fuelStocks as $fuelStock) {
            if ($remainingAmount <= 0) {
                break;
            }

            $currentStock = (float) $fuelStock->current_stock;
            $deductAmount = min($currentStock, $remainingAmount);
            $newBalance = $currentStock - $deductAmount;

            $fuelStock->update([
                'current_stock' => $newBalance,
                'total_stock_out' =>
                    (float) $fuelStock->total_stock_out + $deductAmount,
                'updated_by' => Auth::id(),
            ]);

            FuelTransaction::create([
                'fuel_stock_id' => $fuelStock->id,
                'tractor_id' => $tractorId,
                'farm_work_log_id' => $workLogId,
                'type' => 'refill_to_tractor',
                'quantity' => $deductAmount,
                'balance_after' => $newBalance,
                'reference_no' =>
                    'WORKLOG-'
                    . $workLogId
                    . '-STOCK-'
                    . $fuelStock->id
                    . '-'
                    . now()->format('YmdHis'),
                'transaction_date' => $date ?: now()->toDateString(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'note' =>
                    'Fuel deducted by FIFO from work log #'
                    . $workLogId,
            ]);

            $remainingAmount -= $deductAmount;
        }
    }

    private function returnFuelStock(
        $amount,
        $tractorId = null,
        $workLogId = null,
        $date = null
    ) {
        $amount = (float) $amount;

        if ($amount <= 0) {
            return;
        }

        $remainingAmount = $amount;

        $returnedByStock = FuelTransaction::where(
                'farm_work_log_id',
                $workLogId
            )
            ->where('type', 'adjustment')
            ->where(
                'reference_no',
                'like',
                'RETURN-WORKLOG-' . $workLogId . '%'
            )
            ->selectRaw(
                'fuel_stock_id, SUM(quantity) as total_returned'
            )
            ->groupBy('fuel_stock_id')
            ->pluck('total_returned', 'fuel_stock_id')
            ->map(fn ($value) => (float) $value)
            ->toArray();

        $deductTransactions = FuelTransaction::where(
                'farm_work_log_id',
                $workLogId
            )
            ->where('type', 'refill_to_tractor')
            ->orderByDesc('id')
            ->get();

        foreach ($deductTransactions as $transaction) {
            if ($remainingAmount <= 0) {
                break;
            }

            $stockId = $transaction->fuel_stock_id;
            $deductedQty = (float) $transaction->quantity;
            $alreadyReturned =
                (float) ($returnedByStock[$stockId] ?? 0);

            if ($alreadyReturned >= $deductedQty) {
                $returnedByStock[$stockId] =
                    $alreadyReturned - $deductedQty;

                continue;
            }

            $availableToReturn = $deductedQty - $alreadyReturned;
            $returnAmount = min(
                $availableToReturn,
                $remainingAmount
            );

            $fuelStock = FuelStock::where('id', $stockId)
                ->lockForUpdate()
                ->first();

            if (!$fuelStock) {
                continue;
            }

            $newBalance =
                (float) $fuelStock->current_stock + $returnAmount;

            $fuelStock->update([
                'current_stock' => $newBalance,
                'total_stock_out' => max(
                    (float) $fuelStock->total_stock_out
                    - $returnAmount,
                    0
                ),
                'updated_by' => Auth::id(),
            ]);

            FuelTransaction::create([
                'fuel_stock_id' => $fuelStock->id,
                'tractor_id' => $tractorId,
                'farm_work_log_id' => $workLogId,
                'type' => 'adjustment',
                'quantity' => $returnAmount,
                'balance_after' => $newBalance,
                'reference_no' =>
                    'RETURN-WORKLOG-'
                    . $workLogId
                    . '-STOCK-'
                    . $fuelStock->id
                    . '-'
                    . now()->format('YmdHis'),
                'transaction_date' => $date ?: now()->toDateString(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'note' =>
                    'Fuel returned to FIFO stock from work log #'
                    . $workLogId,
            ]);

            $remainingAmount -= $returnAmount;
        }

        if ($remainingAmount > 0) {
            $fuelStock = FuelStock::where('status', 'active')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$fuelStock) {
                throw new \Exception(
                    'No active fuel stock found. Please create fuel stock first.'
                );
            }

            $newBalance =
                (float) $fuelStock->current_stock + $remainingAmount;

            $fuelStock->update([
                'current_stock' => $newBalance,
                'total_stock_out' => max(
                    (float) $fuelStock->total_stock_out
                    - $remainingAmount,
                    0
                ),
                'updated_by' => Auth::id(),
            ]);

            FuelTransaction::create([
                'fuel_stock_id' => $fuelStock->id,
                'tractor_id' => $tractorId,
                'farm_work_log_id' => $workLogId,
                'type' => 'adjustment',
                'quantity' => $remainingAmount,
                'balance_after' => $newBalance,
                'reference_no' =>
                    'RETURN-WORKLOG-'
                    . $workLogId
                    . '-FALLBACK-'
                    . now()->format('YmdHis'),
                'transaction_date' => $date ?: now()->toDateString(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'note' =>
                    'Fuel returned from old work log history #'
                    . $workLogId,
            ]);
        }
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
            "rows.$index.farm_work_plan_id" =>
                'required|exists:farm_work_plans,id',
            "rows.$index.work_date" => 'required|date',
            "rows.$index.tractor_id" =>
                'required|exists:tractors,id',
            "rows.$index.driver_id" =>
                'required|exists:drivers,id',
            "rows.$index.zone_id" =>
                'required|exists:zones,id',
            "rows.$index.zone_block_id" =>
                'required|exists:zone_blocks,id',
            "rows.$index.task_category_id" =>
                'required|exists:task_categories,id',
            "rows.$index.working_duration" =>
                'nullable|numeric|min:0',
            "rows.$index.working_area" =>
                'nullable|numeric|min:0',
            "rows.$index.diesel_start" =>
                'nullable|numeric|min:0',
            "rows.$index.diesel_refill" =>
                'nullable|numeric|min:0',
            "rows.$index.diesel_end" =>
                'nullable|numeric|min:0',
            "rows.$index.note" =>
                'nullable|string|max:2000',
        ]);

        $row = $this->rows[$index];
        $calculated = $this->calculateRow($row);

        try {
            DB::transaction(function () use ($row, $calculated) {
                $workLog = FarmWorkLog::create([
                    'farm_work_plan_id' =>
                        $row['farm_work_plan_id'],
                    'work_date' => $row['work_date'],
                    'work_status' => 'pending',
                    'tractor_id' => $row['tractor_id'],
                    'driver_id' => $row['driver_id'],
                    'zone_id' => $row['zone_id'],
                    'zone_block_id' => $row['zone_block_id'],
                    'task_category_id' =>
                        $row['task_category_id'],
                    'working_duration' =>
                        $row['working_duration'] ?: 0,
                    'working_area' =>
                        $row['working_area'] ?: 0,
                    'diesel_start' =>
                        $row['diesel_start'] ?: 0,
                    'diesel_refill' =>
                        $row['diesel_refill'] ?: 0,
                    'diesel_end' =>
                        $row['diesel_end'] ?: 0,
                    'diesel_consumed' =>
                        $calculated['diesel_consumed'],
                    'diesel_per_hectare' =>
                        $calculated['diesel_per_hectare'],
                    'hectare_per_hour' =>
                        $calculated['hectare_per_hour'],
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

            $this->dispatch('work-log-saved');

            session()->flash(
                'success',
                'Work log saved successfully, linked with work plan, fuel stock deducted, and history created.'
            );
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
            : '';

        $taskGroupName = $this->getWorkPlanTaskGroupName(
            $log->farm_work_plan_id
        );

        $this->editRow = [
            'farm_work_plan_id' => $log->farm_work_plan_id,
            'task_category_group_name' => $taskGroupName,
            'work_date' =>
                optional($log->work_date)->format('Y-m-d')
                ?: $log->work_date,
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
            'farm_work_plan_id' => '',
            'task_category_group_name' => '',
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
            'editRow.farm_work_plan_id' =>
                'required|exists:farm_work_plans,id',
            'editRow.work_date' => 'required|date',
            'editRow.tractor_id' =>
                'required|exists:tractors,id',
            'editRow.driver_id' =>
                'required|exists:drivers,id',
            'editRow.zone_id' =>
                'required|exists:zones,id',
            'editRow.zone_block_id' =>
                'required|exists:zone_blocks,id',
            'editRow.task_category_id' =>
                'required|exists:task_categories,id',
            'editRow.working_duration' =>
                'nullable|numeric|min:0',
            'editRow.working_area' =>
                'nullable|numeric|min:0',
            'editRow.diesel_start' =>
                'nullable|numeric|min:0',
            'editRow.diesel_refill' =>
                'nullable|numeric|min:0',
            'editRow.diesel_end' =>
                'nullable|numeric|min:0',
            'editRow.note' =>
                'nullable|string|max:2000',
        ]);

        $calculated = $this->calculateRow($this->editRow);
        $oldDieselUsed = (float) $log->diesel_consumed;

        try {
            DB::transaction(
                function () use (
                    $log,
                    $calculated,
                    $oldDieselUsed
                ) {
                    $newDieselUsed =
                        (float) $calculated['diesel_consumed'];

                    $difference =
                        $newDieselUsed - $oldDieselUsed;

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
                        'farm_work_plan_id' =>
                            $this->editRow['farm_work_plan_id'],
                        'work_date' =>
                            $this->editRow['work_date'],
                        'work_status' =>
                            $log->work_status ?: 'pending',
                        'tractor_id' =>
                            $this->editRow['tractor_id'],
                        'driver_id' =>
                            $this->editRow['driver_id'],
                        'zone_id' =>
                            $this->editRow['zone_id'],
                        'zone_block_id' =>
                            $this->editRow['zone_block_id'],
                        'task_category_id' =>
                            $this->editRow['task_category_id'],
                        'working_duration' =>
                            $this->editRow['working_duration'] ?: 0,
                        'working_area' =>
                            $this->editRow['working_area'] ?: 0,
                        'diesel_start' =>
                            $this->editRow['diesel_start'] ?: 0,
                        'diesel_refill' =>
                            $this->editRow['diesel_refill'] ?: 0,
                        'diesel_end' =>
                            $this->editRow['diesel_end'] ?: 0,
                        'diesel_consumed' => $newDieselUsed,
                        'diesel_per_hectare' =>
                            $calculated['diesel_per_hectare'],
                        'hectare_per_hour' =>
                            $calculated['hectare_per_hour'],
                        'gps_distance_meters' =>
                            $log->gps_distance_meters ?? 0,
                        'estimated_plowed_area' =>
                            $log->estimated_plowed_area ?? 0,
                        'gps_progress_percent' =>
                            $log->gps_progress_percent ?? 0,
                        'note' =>
                            $this->editRow['note'] ?: null,
                        'updated_by' => Auth::id(),
                    ]);
                }
            );

            $this->cancelEdit();

            session()->flash(
                'success',
                'Work log updated, linked with work plan, fuel stock adjusted, and history created.'
            );
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

            session()->flash(
                'success',
                'Work log deleted, fuel returned, and history created.'
            );
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
        $this->workPlanId = '';
        $this->perPage = 15;
    }

    private function logsQuery()
    {
        return FarmWorkLog::query()
            ->with([
                'workPlan.taskCategory.group',
                'workPlan.activities.taskCategory.group',
                'tractor',
                'driver',
                'zone',
                'zoneBlock.zone',
                'taskCategory',
            ])
            ->when(
                trim((string) $this->search) !== '',
                function ($query) {
                    $search = trim((string) $this->search);

                    $query->where(
                        function ($subQuery) use ($search) {
                            $subQuery
                                ->where(
                                    'id',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'work_status',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhereHas(
                                    'workPlan',
                                    function ($workPlanQuery) use ($search) {
                                        $workPlanQuery
                                            ->where(
                                                'title',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'status',
                                                'like',
                                                '%' . $search . '%'
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'tractor',
                                    function ($tractorQuery) use ($search) {
                                        $tractorQuery
                                            ->where(
                                                'tractor_no',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                '%' . $search . '%'
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'driver',
                                    function ($driverQuery) use ($search) {
                                        $driverQuery->where(
                                            'name',
                                            'like',
                                            '%' . $search . '%'
                                        );
                                    }
                                )
                                ->orWhereHas(
                                    'zone',
                                    function ($zoneQuery) use ($search) {
                                        $zoneQuery
                                            ->where(
                                                'zone_code',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                '%' . $search . '%'
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'zoneBlock',
                                    function ($blockQuery) use ($search) {
                                        $blockQuery
                                            ->where(
                                                'block_code',
                                                'like',
                                                '%' . $search . '%'
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                '%' . $search . '%'
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'taskCategory',
                                    function ($taskQuery) use ($search) {
                                        $taskQuery->where(
                                            'name',
                                            'like',
                                            '%' . $search . '%'
                                        );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                filled($this->dateFrom),
                fn ($query) => $query->whereDate(
                    'work_date',
                    '>=',
                    $this->dateFrom
                )
            )
            ->when(
                filled($this->dateTo),
                fn ($query) => $query->whereDate(
                    'work_date',
                    '<=',
                    $this->dateTo
                )
            )
            ->when(
                filled($this->tractorId),
                fn ($query) => $query->where(
                    'tractor_id',
                    $this->tractorId
                )
            )
            ->when(
                filled($this->driverId),
                fn ($query) => $query->where(
                    'driver_id',
                    $this->driverId
                )
            )
            ->when(
                filled($this->zoneId),
                fn ($query) => $query->where(
                    'zone_id',
                    $this->zoneId
                )
            )
            ->when(
                filled($this->taskCategoryId),
                fn ($query) => $query->where(
                    'task_category_id',
                    $this->taskCategoryId
                )
            )
            ->when(
                filled($this->workPlanId),
                fn ($query) => $query->where(
                    'farm_work_plan_id',
                    $this->workPlanId
                )
            );
    }

    public function getTotalHoursProperty()
    {
        return (clone $this->logsQuery())
            ->sum('working_duration');
    }

    public function getTotalAreaProperty()
    {
        return (clone $this->logsQuery())
            ->sum('working_area');
    }

    public function getTotalDieselRefillProperty()
    {
        return (clone $this->logsQuery())
            ->sum('diesel_refill');
    }

    public function getTotalDieselUsedProperty()
    {
        return (clone $this->logsQuery())
            ->sum('diesel_consumed');
    }

    public function getAvgLiterPerHaProperty()
    {
        return $this->totalArea > 0
            ? $this->totalDieselUsed / $this->totalArea
            : 0;
    }

    public function getAvgHaPerHrProperty()
    {
        return $this->totalHours > 0
            ? $this->totalArea / $this->totalHours
            : 0;
    }

    public function exportWorkLogsExcel()
    {
        if (!auth()->user()->hasPermission('work_logs.view')) {
            abort(403, 'Permission denied.');
        }

        $logs = $this->logsQuery()
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(__('pages.farm_work_logs'));

        $headers = [
            'A1' => '#',
            'B1' => 'Work Plan',
            'C1' => 'Task Group',
            'D1' => 'Activity',
            'E1' => 'Zone / Sub Zone',
            'F1' => 'Date',
            'G1' => 'Tractor',
            'H1' => 'Driver',
            'I1' => 'Hour',
            'J1' => 'Total Area',
            'K1' => 'Diesel Start',
            'L1' => 'Diesel Refill',
            'M1' => 'Diesel End',
            'N1' => 'Diesel Used',
            'O1' => 'L/Ha',
            'P1' => 'Ha/Hr',
            'Q1' => 'Note',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowNumber = 2;

        foreach ($logs as $index => $log) {
            $zoneLabel = '-';

            if ($log->zone) {
                $zoneLabel = $log->zone->zone_code;

                if ($log->zone->name) {
                    $zoneLabel .= ' - ' . $log->zone->name;
                }
            }

            if ($log->zoneBlock) {
                $zoneLabel = $this->formatZoneBlockLabel(
                    $log->zoneBlock
                );

                if ($log->zoneBlock->name) {
                    $zoneLabel .=
                        ' - ' . $log->zoneBlock->name;
                }
            } else {
                $zoneLabel =
                    ($log->zone->zone_code ?? '-')
                    . ' - No sub zone';
            }

            $workPlanLabel = $log->workPlan
                ? ($log->workPlan->title ?: '-')
                : 'No Plan';

            $planTaskCategory =
                $log->workPlan?->activities->first()?->taskCategory
                ?? $log->workPlan?->taskCategory;

            $taskGroupName =
                $planTaskCategory?->group?->name
                ?? $log->workPlan?->title
                ?? '-';

            $sheet->setCellValue(
                'A' . $rowNumber,
                $index + 1
            );

            $sheet->setCellValue(
                'B' . $rowNumber,
                $workPlanLabel
            );

            $sheet->setCellValue(
                'C' . $rowNumber,
                $taskGroupName
            );

            $sheet->setCellValue(
                'D' . $rowNumber,
                $log->taskCategory->name ?? '-'
            );

            $sheet->setCellValue(
                'E' . $rowNumber,
                $zoneLabel
            );

            $sheet->setCellValue(
                'F' . $rowNumber,
                optional($log->work_date)->format('Y-m-d')
                    ?: $log->work_date
            );

            $sheet->setCellValue(
                'G' . $rowNumber,
                $log->tractor->tractor_no ?? '-'
            );

            $sheet->setCellValue(
                'H' . $rowNumber,
                $log->driver->name ?? '-'
            );

            $sheet->setCellValue(
                'I' . $rowNumber,
                (float) ($log->working_duration ?? 0)
            );

            $sheet->setCellValue(
                'J' . $rowNumber,
                (float) ($log->working_area ?? 0)
            );

            $sheet->setCellValue(
                'K' . $rowNumber,
                (float) ($log->diesel_start ?? 0)
            );

            $sheet->setCellValue(
                'L' . $rowNumber,
                (float) ($log->diesel_refill ?? 0)
            );

            $sheet->setCellValue(
                'M' . $rowNumber,
                (float) ($log->diesel_end ?? 0)
            );

            $sheet->setCellValue(
                'N' . $rowNumber,
                '=MAX((K'
                . $rowNumber
                . '+L'
                . $rowNumber
                . ')-M'
                . $rowNumber
                . ',0)'
            );

            $sheet->setCellValue(
                'O' . $rowNumber,
                '=IF(J'
                . $rowNumber
                . '>0,N'
                . $rowNumber
                . '/J'
                . $rowNumber
                . ',0)'
            );

            $sheet->setCellValue(
                'P' . $rowNumber,
                '=IF(I'
                . $rowNumber
                . '>0,J'
                . $rowNumber
                . '/I'
                . $rowNumber
                . ',0)'
            );

            $sheet->setCellValue(
                'Q' . $rowNumber,
                $log->note ?? ''
            );

            $rowNumber++;
        }

        $lastDataRow = $rowNumber - 1;

        if ($lastDataRow >= 2) {
            $sheet->setCellValue(
                'H' . $rowNumber,
                'Total'
            );

            $sheet->setCellValue(
                'I' . $rowNumber,
                '=SUM(I2:I' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'J' . $rowNumber,
                '=SUM(J2:J' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'K' . $rowNumber,
                '-'
            );

            $sheet->setCellValue(
                'L' . $rowNumber,
                '=SUM(L2:L' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'M' . $rowNumber,
                '-'
            );

            $sheet->setCellValue(
                'N' . $rowNumber,
                '=SUM(N2:N' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'O' . $rowNumber,
                '=IF(J'
                . $rowNumber
                . '>0,N'
                . $rowNumber
                . '/J'
                . $rowNumber
                . ',0)'
            );

            $sheet->setCellValue(
                'P' . $rowNumber,
                '=IF(I'
                . $rowNumber
                . '>0,J'
                . $rowNumber
                . '/I'
                . $rowNumber
                . ',0)'
            );

            $sheet->setCellValue(
                'Q' . $rowNumber,
                '-'
            );
        }

        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:Q1')
            ->getFont()
            ->setBold(true);

        if ($highestRow >= 2) {
            $sheet->getStyle(
                'A' . $highestRow . ':Q' . $highestRow
            )
                ->getFont()
                ->setBold(true);

            $sheet->getStyle('I2:P' . $highestRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (range('A', 'Q') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:Q1');

        $filename =
            'farm-work-logs-'
            . now()->format('Y-m-d-His')
            . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);

                $writer->setPreCalculateFormulas(false);
                $writer->save('php://output');
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function with()
    {
        $allowedPerPage = [10, 15, 25, 50, 100];
        $perPage = (int) $this->perPage;

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        return [
            'logs' => $this->logsQuery()
                ->orderByDesc('work_date')
                ->orderByDesc('id')
                ->limit($perPage)
                ->get(),

            'workPlans' => FarmWorkPlan::with([
                    'taskCategory.group',
                    'activities.taskCategory.group',
                ])
                ->whereIn(
                    'status',
                    ['in_progress', 'complete']
                )
                ->latest('plan_date')
                ->latest('id')
                ->get(),

            'filterWorkPlans' => FarmWorkPlan::with([
                    'taskCategory.group',
                    'activities.taskCategory.group',
                ])
                ->latest('plan_date')
                ->latest('id')
                ->get(),

            'tractors' => Tractor::where('status', 'active')
                ->orderBy('tractor_no')
                ->get(),

            'drivers' => Driver::where('status', 'active')
                ->orderBy('name')
                ->get(),

            'zones' => Zone::where('status', 'active')
                ->orderBy('zone_code')
                ->get(),

            'zoneBlocks' => ZoneBlock::with('zone')
                ->where('status', 'active')
                ->orderBy('block_code')
                ->get(),

            'taskCategories' => TaskCategory::where(
                    'status',
                    'active'
                )
                ->orderBy('name')
                ->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .filter-panel {
            margin-bottom: 18px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .filter-grid label {
            display: block;
            font-weight: 900;
            font-size: 13px;
            margin-bottom: 6px;
            color: #334155;
        }

        .filter-grid input,
        .filter-grid select {
            width: 100%;
            height: 46px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 700;
            background: #ffffff;
        }

        .list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .rows-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rows-control label {
            font-size: 13px;
            font-weight: 900;
            color: #334155;
            margin: 0;
        }

        .rows-control select {
            width: 130px;
            height: 40px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 8px 10px;
            font-weight: 800;
            background: #ffffff;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .work-table {
            width: 100%;
            min-width: 2050px;
            border-collapse: collapse;
            background: #ffffff;
        }

        .work-table th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .work-table td {
            padding: 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            white-space: nowrap;
        }

        .work-table input,
        .work-table select {
            width: 100%;
            min-width: 125px;
            height: 42px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 13px;
            background: #ffffff;
            font-weight: 700;
        }

        .work-plan-select {
            min-width: 300px !important;
        }

        .task-group-readonly {
            min-width: 180px !important;
            background: #f8fafc !important;
            color: #0f172a !important;
            font-weight: 900 !important;
        }

        .zone-combo {
            min-width: 300px;
        }

        .zone-block-select {
            min-width: 280px !important;
            width: 100% !important;
            border-color: #bbf7d0 !important;
            background: #ffffff !important;
            max-height: 240px;
            overflow-y: auto;
        }

        .zone-display {
            font-weight: 900;
            color: #0f172a;
        }

        .sub-zone-display {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            font-weight: 900;
            color: #15803d;
        }

        .row-no {
            width: 45px;
            min-width: 45px;
            text-align: center;
            font-weight: 900;
            color: #64748b;
        }

        .new-row {
            background: #f0fdf4;
        }

        .new-row td {
            border-bottom: 1px solid #bbf7d0;
        }

        .table-actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 900;
            border-top: 2px solid #d1d5db;
        }

        .total-row td {
            border-bottom: 0;
            padding: 14px 10px;
            color: #0f172a;
        }

        .plus-cell {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 10px;
            background: #16a34a;
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .plus-cell:hover {
            background: #15803d;
        }

        .danger-plus {
            background: #dc2626;
        }

        .danger-plus:hover {
            background: #b91c1c;
        }

        .error {
            display: block;
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 700;
        }

        .work-plan-label {
            display: inline-flex;
            flex-direction: column;
            gap: 2px;
            font-size: 12px;
            font-weight: 900;
            color: #0f172a;
        }

        .work-plan-label small {
            color: #64748b;
            font-weight: 800;
        }

        .no-plan {
            color: #dc2626;
            font-weight: 900;
        }

        .empty {
            padding: 30px !important;
            text-align: center;
            color: #64748b;
            font-weight: 800;
        }

        @media (max-width: 1200px) {
            .filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">
                {{ __('pages.farm_work_logs') }}
            </h1>

            <p class="page-subtitle">
                Daily tractor work, area, fuel, and productivity records.
            </p>
        </div>

        <div class="page-actions">
            <a
                href="{{ route('farm-work-logs.export.csv') }}"
                class="btn gray"
            >
                Export CSV
            </a>

            <button
                type="button"
                wire:click="exportWorkLogsExcel"
                class="btn gray"
            >
                Export Excel
            </button>

            <a
                href="{{ route('dashboard') }}"
                class="btn gray"
            >
                Dashboard
            </a>
        </div>
    </div>

    <div class="panel filter-panel">
        <h2 class="panel-title">Filter</h2>

        <div class="filter-grid">
            <div>
                <label>Search</label>

                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="Search plan, tractor, driver, zone, sub zone, task"
                >
            </div>

            <div>
                <label>Date From</label>

                <input
                    type="date"
                    wire:model.live="dateFrom"
                >
            </div>

            <div>
                <label>Date To</label>

                <input
                    type="date"
                    wire:model.live="dateTo"
                >
            </div>

            <div>
                <label>Work Plan</label>

                <select wire:model.live="workPlanId">
                    <option value="">
                        All Work Plans
                    </option>

                    @foreach($filterWorkPlans as $plan)
                        <option value="{{ $plan->id }}">
                            {{ $plan->title ?: '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Tractor</label>

                <select wire:model.live="tractorId">
                    <option value="">
                        {{ __('pages.all_tractors') }}
                    </option>

                    @foreach($tractors as $tractor)
                        <option value="{{ $tractor->id }}">
                            {{ $tractor->tractor_no }}
                            {{ $tractor->name ? '- ' . $tractor->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Driver</label>

                <select wire:model.live="driverId">
                    <option value="">
                        {{ __('pages.all_drivers') }}
                    </option>

                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">
                            {{ $driver->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Zone</label>

                <select wire:model.live="zoneId">
                    <option value="">
                        {{ __('pages.all_zones') }}
                    </option>

                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">
                            {{ $zone->zone_code }}
                            {{ $zone->name ? '- ' . $zone->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Task Category</label>

                <select wire:model.live="taskCategoryId">
                    <option value="">
                        All Tasks
                    </option>

                    @foreach($taskCategories as $taskCategory)
                        <option value="{{ $taskCategory->id }}">
                            {{ $taskCategory->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-top: 14px;">
            <button
                type="button"
                wire:click="resetFilter"
                class="btn gray"
            >
                Reset Filter
            </button>
        </div>
    </div>

    <div class="panel">
        <div class="list-header">
            <h2
                class="panel-title"
                style="margin: 0;"
            >
                Work Log List
            </h2>

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

        <div
            class="table-wrap"
            id="workLogTableWrap"
        >
            <table class="work-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Work Plan</th>
                        <th>Task Group</th>
                        <th>Activity</th>
                        <th>Zone / Sub Zone</th>
                        <th>Date</th>
                        <th>Tractor</th>
                        <th>Driver</th>
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
                    @forelse($logs as $log)
                        @if((int) $editingId === (int) $log->id)
                            <tr
                                class="new-row"
                                wire:key="edit-work-log-{{ $log->id }}"
                            >
                                <td class="row-no">
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    <select
                                        wire:model.live="editRow.farm_work_plan_id"
                                        wire:change="applyWorkPlanToEdit"
                                        class="work-plan-select"
                                    >
                                        <option value="">
                                            Select Work Plan
                                        </option>

                                        @foreach($workPlans as $plan)
                                            <option value="{{ $plan->id }}">
                                                {{ $plan->title ?: '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="task-group-readonly"
                                        value="{{ $editRow['task_category_group_name'] ?: 'Select Work Plan first' }}"
                                        readonly
                                    >
                                </td>

                                <td>
                                    <select
                                        wire:model.live="editRow.task_category_id"
                                        @disabled(empty($editRow['farm_work_plan_id']))
                                    >
                                        <option value="">
                                            {{ empty($editRow['farm_work_plan_id'])
                                                ? 'Select Work Plan first'
                                                : 'Select Activity' }}
                                        </option>

                                        @foreach(
                                            $this->getWorkPlanTaskCategories(
                                                $editRow['farm_work_plan_id'] ?? null
                                            ) as $taskCategory
                                        )
                                            <option value="{{ $taskCategory->id }}">
                                                {{ $taskCategory->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="zone-combo">
                                    <select
                                        wire:model.live="editRow.zone_block_select"
                                        wire:change="syncEditZoneSelection"
                                        class="zone-block-select"
                                        @disabled(empty($editRow['farm_work_plan_id']))
                                    >
                                        <option value="">
                                            {{ empty($editRow['farm_work_plan_id'])
                                                ? 'Select Work Plan first'
                                                : 'Select Zone / Sub Zone' }}
                                        </option>

                                        @foreach(
                                            $this->getWorkPlanZoneBlocks(
                                                $editRow['farm_work_plan_id'] ?? null
                                            ) as $block
                                        )
                                            <option value="{{ $block->id }}">
                                                {{ $this->formatZoneBlockLabel($block) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="date"
                                        wire:model.live="editRow.work_date"
                                    >
                                </td>

                                <td>
                                    <select wire:model.live="editRow.tractor_id">
                                        <option value="">
                                            Select Tractor
                                        </option>

                                        @foreach($tractors as $tractor)
                                            <option value="{{ $tractor->id }}">
                                                {{ $tractor->tractor_no }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select wire:model.live="editRow.driver_id">
                                        <option value="">
                                            Select Driver
                                        </option>

                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}">
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="editRow.working_duration"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="editRow.working_area"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="editRow.diesel_start"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="editRow.diesel_refill"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="editRow.diesel_end"
                                    >
                                </td>

                                @php
                                    $calc = $this->calculateRow($editRow);
                                @endphp

                                <td>
                                    <strong>
                                        {{ number_format(
                                            (float) $calc['diesel_consumed'],
                                            2
                                        ) }}
                                    </strong>
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $calc['diesel_per_hectare'],
                                        2
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $calc['hectare_per_hour'],
                                        2
                                    ) }}
                                </td>

                                <td>
                                    <div class="table-actions">
                                        <button
                                            type="button"
                                            wire:click="updateRow"
                                            class="mini"
                                        >
                                            Save
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="cancelEdit"
                                            class="mini danger"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @php
                                $savedPlanTaskCategory =
                                    $log->workPlan?->activities->first()?->taskCategory
                                    ?? $log->workPlan?->taskCategory;

                                $savedTaskGroupName =
                                    $savedPlanTaskCategory?->group?->name
                                    ?? $log->workPlan?->title
                                    ?? '-';
                            @endphp

                            <tr wire:key="work-log-{{ $log->id }}">
                                <td class="row-no">
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    @if($log->workPlan)
                                        <span class="work-plan-label">
                                            {{ $log->workPlan->title ?: '-' }}
                                        </span>
                                    @else
                                        <span class="no-plan">
                                            No Plan
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <strong>
                                        {{ $savedTaskGroupName }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $log->taskCategory->name ?? '-' }}
                                </td>

                                <td>
                                    @if($log->zoneBlock)
                                        <span class="zone-display">
                                            {{ $this->formatZoneBlockLabel(
                                                $log->zoneBlock
                                            ) }}
                                        </span>

                                        @if($log->zoneBlock->name)
                                            <span class="sub-zone-display">
                                                {{ $log->zoneBlock->name }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="zone-display">
                                            {{ $log->zone->zone_code ?? '-' }}
                                        </span>

                                        <span
                                            class="sub-zone-display"
                                            style="color: #94a3b8;"
                                        >
                                            No sub zone
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ optional($log->work_date)->format('d M Y')
                                        ?: $log->work_date }}
                                </td>

                                <td>
                                    {{ $log->tractor->tractor_no ?? '-' }}
                                </td>

                                <td>
                                    {{ $log->driver->name ?? '-' }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->working_duration,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->working_area,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->diesel_start,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->diesel_refill,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->diesel_end,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format(
                                            (float) $log->diesel_consumed,
                                            2
                                        ) }}
                                    </strong>
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->diesel_per_hectare,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    {{ number_format(
                                        (float) $log->hectare_per_hour,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    <div class="table-actions">
                                        @if(
                                            auth()
                                                ->user()
                                                ->hasPermission('work_logs.edit')
                                        )
                                            <button
                                                type="button"
                                                wire:click="edit({{ $log->id }})"
                                                class="mini"
                                            >
                                                Edit
                                            </button>
                                        @endif

                                        @if(
                                            auth()
                                                ->user()
                                                ->hasPermission('work_logs.delete')
                                        )
                                            <button
                                                type="button"
                                                wire:click="delete({{ $log->id }})"
                                                wire:confirm="Delete this work log?"
                                                class="mini danger"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td
                                colspan="17"
                                class="empty"
                            >
                                No work log found.
                            </td>
                        </tr>
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr
                            class="new-row"
                            wire:key="new-work-log-{{ $index }}"
                        >
                            <td class="row-no">
                                <button
                                    type="button"
                                    wire:click="removeRow({{ $index }})"
                                    class="plus-cell danger-plus"
                                >
                                    ×
                                </button>
                            </td>

                            <td>
                                <select
                                    wire:model.live="rows.{{ $index }}.farm_work_plan_id"
                                    wire:change="applyWorkPlanToRow({{ $index }})"
                                    class="work-plan-select"
                                >
                                    <option value="">
                                        Select Work Plan
                                    </option>

                                    @foreach($workPlans as $plan)
                                        <option value="{{ $plan->id }}">
                                            {{ $plan->title ?: '-' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input
                                    type="text"
                                    class="task-group-readonly"
                                    value="{{ $row['task_category_group_name'] ?: 'Select Work Plan first' }}"
                                    readonly
                                >
                            </td>

                            <td>
                                <select
                                    wire:model.live="rows.{{ $index }}.task_category_id"
                                    @disabled(empty($row['farm_work_plan_id']))
                                >
                                    <option value="">
                                        {{ empty($row['farm_work_plan_id'])
                                            ? 'Select Work Plan first'
                                            : 'Select Activity' }}
                                    </option>

                                    @foreach(
                                        $this->getWorkPlanTaskCategories(
                                            $row['farm_work_plan_id'] ?? null
                                        ) as $taskCategory
                                    )
                                        <option value="{{ $taskCategory->id }}">
                                            {{ $taskCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="zone-combo">
                                <select
                                    wire:model.live="rows.{{ $index }}.zone_block_select"
                                    wire:change="syncZoneSelection({{ $index }})"
                                    class="zone-block-select"
                                    @disabled(empty($row['farm_work_plan_id']))
                                >
                                    <option value="">
                                        {{ empty($row['farm_work_plan_id'])
                                            ? 'Select Work Plan first'
                                            : 'Select Zone / Sub Zone' }}
                                    </option>

                                    @foreach(
                                        $this->getWorkPlanZoneBlocks(
                                            $row['farm_work_plan_id'] ?? null
                                        ) as $block
                                    )
                                        <option value="{{ $block->id }}">
                                            {{ $this->formatZoneBlockLabel($block) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input
                                    type="date"
                                    wire:model.live="rows.{{ $index }}.work_date"
                                >
                            </td>

                            <td>
                                <select
                                    wire:model.live="rows.{{ $index }}.tractor_id"
                                >
                                    <option value="">
                                        Select Tractor
                                    </option>

                                    @foreach($tractors as $tractor)
                                        <option value="{{ $tractor->id }}">
                                            {{ $tractor->tractor_no }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <select
                                    wire:model.live="rows.{{ $index }}.driver_id"
                                >
                                    <option value="">
                                        Select Driver
                                    </option>

                                    @foreach($drivers as $driver)
                                        <option value="{{ $driver->id }}">
                                            {{ $driver->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.live="rows.{{ $index }}.working_duration"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.live="rows.{{ $index }}.working_area"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.live="rows.{{ $index }}.diesel_start"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.live="rows.{{ $index }}.diesel_refill"
                                >
                            </td>

                            <td>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model.live="rows.{{ $index }}.diesel_end"
                                >
                            </td>

                            @php
                                $calc = $this->calculateRow($row);
                            @endphp

                            <td>
                                <strong>
                                    {{ number_format(
                                        (float) $calc['diesel_consumed'],
                                        2
                                    ) }}
                                </strong>
                            </td>

                            <td>
                                {{ number_format(
                                    (float) $calc['diesel_per_hectare'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    (float) $calc['hectare_per_hour'],
                                    2
                                ) }}
                            </td>

                            <td>
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        wire:click="saveRow({{ $index }})"
                                        class="mini"
                                    >
                                        Save
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="removeRow({{ $index }})"
                                        class="mini danger"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td>
                            @if(
                                auth()
                                    ->user()
                                    ->hasPermission('work_logs.create')
                            )
                                <button
                                    type="button"
                                    wire:click="addRow"
                                    class="plus-cell"
                                >
                                    +
                                </button>
                            @else
                                -
                            @endif
                        </td>

                        <td
                            colspan="7"
                            style="text-align: right;"
                        >
                            Total
                        </td>

                        <td>
                            {{ number_format(
                                (float) $this->totalHours,
                                2
                            ) }}
                        </td>

                        <td>
                            {{ number_format(
                                (float) $this->totalArea,
                                2
                            ) }}
                        </td>

                        <td>-</td>

                        <td>
                            {{ number_format(
                                (float) $this->totalDieselRefill,
                                2
                            ) }}
                        </td>

                        <td>-</td>

                        <td>
                            {{ number_format(
                                (float) $this->totalDieselUsed,
                                2
                            ) }}
                        </td>

                        <td>
                            {{ number_format(
                                (float) $this->avgLiterPerHa,
                                2
                            ) }}
                        </td>

                        <td>
                            {{ number_format(
                                (float) $this->avgHaPerHr,
                                2
                            ) }}
                        </td>

                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @script
        <script>
            $wire.on('work-log-saved', () => {
                requestAnimationFrame(() => {
                    const tableWrap =
                        document.getElementById('workLogTableWrap');

                    if (tableWrap) {
                        tableWrap.scrollLeft = 0;
                    }
                });
            });
        </script>
    @endscript
</div>