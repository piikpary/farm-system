<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FarmWorkLog;
use App\Models\FarmWorkPlan;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\ZoneBlock;
use App\Models\TaskCategory;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use App\Models\Machine;
use App\Models\Location;
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
    public $machineId = '';
    public $locationId = '';
    public $perPage = 15;
    public $workLogType = 'planning';
    public $filterOpen = false;
    use WithPagination;

    protected $queryString = [
        'workLogType' => ['except' => 'planning'],
    ];

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
        'machine_id' => '',
        'location_id' => '',
        'task_category_id' => '',
        'working_duration' => '',
        'working_area' => '',
        'consume_l_per_hour' => '',
        'total_consume_liter' => '',
        'diesel_start' => '',
        'diesel_refill' => '',
        'diesel_end' => '',
        'note' => '',
    ];

    public function mount()
    {
        if (!in_array($this->workLogType, ['planning', 'harvesting', 'facility'], true)) {
            $this->workLogType = 'planning';
        }
    }
    public function toggleFilter()
{
    $this->filterOpen = !$this->filterOpen;
}

    public function updatedWorkLogType()
    {
        if (!in_array($this->workLogType, ['planning', 'harvesting', 'facility'], true)) {
            $this->workLogType = 'planning';
        }

        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->tractorId = '';
        $this->driverId = '';
        $this->zoneId = '';
        $this->taskCategoryId = '';
        $this->workPlanId = '';
        $this->machineId = '';
        $this->locationId = '';
        $this->rows = [];
        $this->cancelEdit();
    }

    public function workLogTypeLabel(): string
    {
        return [
            'planning' => 'Planting',
            'harvesting' => 'Harvesting',
            'facility' => 'Facility',
        ][$this->workLogType] ?? 'Planting';
    }

    public function isFacility(): bool
    {
        return $this->workLogType === 'facility';
    }

    public function workLogQtyLabel(): string
    {
        return match ($this->workLogType) {
            'harvesting' => 'Total Tons',
            'facility' => 'Total Hour',
            default => 'Total Area',
        };
    }

    public function workLogDieselRateLabel(): string
    {
        return match ($this->workLogType) {
            'harvesting' => 'L/T',
            'facility' => 'Consume L/H',
            default => 'L/Ha',
        };
    }

    public function workLogProductivityLabel(): string
    {
        return match ($this->workLogType) {
            'harvesting' => 'T/Hr',
            'facility' => 'Total Consume (L)',
            default => 'Ha/Hr',
        };
    }

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
            'machine_id' => '',
            'location_id' => '',
            'consume_l_per_hour' => '',
            'total_consume_liter' => '',
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
    public function updatedRows($value, $key)
{
    if (!$this->isFacility()) {
        return;
    }

    $parts = explode('.', $key);
    $rowIndex = isset($parts[0]) ? (int) $parts[0] : null;
    $field = $parts[1] ?? null;

    if ($rowIndex === null || !isset($this->rows[$rowIndex])) {
        return;
    }

    if (in_array($field, ['consume_l_per_hour', 'working_duration'], true)) {
        $consumePerHour = (float) ($this->rows[$rowIndex]['consume_l_per_hour'] ?? 0);
        $workingDuration = (float) ($this->rows[$rowIndex]['working_duration'] ?? 0);

        $this->rows[$rowIndex]['total_consume_liter'] = round(
            $consumePerHour * $workingDuration,
            2
        );
    }
}

public function updatedEditRow($value, $key)
{
    if (!$this->isFacility()) {
        return;
    }

    if (in_array($key, ['consume_l_per_hour', 'working_duration'], true)) {
        $consumePerHour = (float) ($this->editRow['consume_l_per_hour'] ?? 0);
        $workingDuration = (float) ($this->editRow['working_duration'] ?? 0);

        $this->editRow['total_consume_liter'] = round(
            $consumePerHour * $workingDuration,
            2
        );
    }
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

    public function formatWorkPlanSelectLabel($plan): string
    {
        if (!$plan) {
            return '-';
        }

        $taskCategories = $plan->activities
            ->map(fn ($activity) => $activity->taskCategory)
            ->filter()
            ->unique('id')
            ->values();

        if ($taskCategories->isEmpty() && $plan->taskCategory) {
            $taskCategories = collect([$plan->taskCategory]);
        }

        $taskGroupNames = $taskCategories
            ->map(fn ($taskCategory) => $taskCategory->group?->name)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $activityNames = $taskCategories
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        if ($this->isFacility()) {
            return collect([
                $taskGroupNames ?: ($plan->title ?: '-'),
                $activityNames ?: '-',
                $plan->machine?->name ?: '-',
                $plan->location?->name ?: '-',
            ])->implode(' | ');
        }

        $zoneBlockNames = $this->getWorkPlanZoneBlocks($plan->id)
            ->map(fn ($block) => $this->formatZoneBlockLabel($block))
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        return collect([
            $taskGroupNames ?: ($plan->title ?: '-'),
            $activityNames ?: '-',
            $zoneBlockNames ?: '-',
        ])->implode(' | ');
    }

    public function getMachineName($machineId): string
    {
        if (!$machineId) {
            return '-';
        }

        return Machine::whereKey($machineId)->value('name') ?: '-';
    }

    public function getLocationName($locationId): string
    {
        if (!$locationId) {
            return '-';
        }

        return Location::whereKey($locationId)->value('name') ?: '-';
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
        $this->rows[$index]['machine_id'] = '';
        $this->rows[$index]['location_id'] = '';
        $this->rows[$index]['consume_l_per_hour'] = '';
        $this->rows[$index]['total_consume_liter'] = '';

        if (!$planId) {
            return;
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
            'machine',
            'location',
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
        if ($this->isFacility()) {
            $this->rows[$index]['machine_id'] = $plan->machine_id;
            $this->rows[$index]['location_id'] = $plan->location_id;
            $this->rows[$index]['consume_l_per_hour'] = $plan->request_l_per_hectare;
            $this->rows[$index]['working_duration'] = '';
            $this->rows[$index]['working_area'] = '';
            $this->rows[$index]['total_consume_liter'] = '';
            return;
        }

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
        $this->editRow['machine_id'] = '';
        $this->editRow['location_id'] = '';
        $this->editRow['consume_l_per_hour'] = '';
        $this->editRow['total_consume_liter'] = '';

        if (!$planId) {
            return;
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
            'machine',
            'location',
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
        if ($this->isFacility()) {
            $this->editRow['machine_id'] = $plan->machine_id;
            $this->editRow['location_id'] = $plan->location_id;
            $this->editRow['consume_l_per_hour'] = $plan->request_l_per_hectare;
            return;
        }

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
        if ($this->isFacility()) {
            $consumePerHour = (float) ($row['consume_l_per_hour'] ?? 0);
            $workingDuration = (float) ($row['working_duration'] ?? 0);

            $totalConsume = filled($row['total_consume_liter'] ?? null)
                ? (float) $row['total_consume_liter']
                : ($consumePerHour * $workingDuration);

            $totalConsume = max($totalConsume, 0);

            return [
                'diesel_consumed' => $totalConsume,
                'diesel_per_hectare' => $consumePerHour,
                'hectare_per_hour' => $totalConsume,
            ];
        }

        $workingDuration = (float) ($row['working_duration'] ?? 0);
        $workingArea = (float) ($row['working_area'] ?? 0);
        $dieselStart = (float) ($row['diesel_start'] ?? 0);
        $dieselRefill = (float) ($row['diesel_refill'] ?? 0);
        $dieselEnd = (float) ($row['diesel_end'] ?? 0);

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
            "rows.$index.tractor_id" => $this->isFacility()
                ? 'nullable'
                : 'required|exists:tractors,id',
            "rows.$index.driver_id" => $this->isFacility()
                ? 'nullable'
                : 'required|exists:drivers,id',
            "rows.$index.zone_id" => $this->isFacility()
                ? 'nullable'
                : 'required|exists:zones,id',
            "rows.$index.zone_block_id" => $this->isFacility()
                ? 'nullable'
                : 'required|exists:zone_blocks,id',
            "rows.$index.machine_id" => $this->isFacility()
                ? 'required|exists:machines,id'
                : 'nullable',
            "rows.$index.location_id" => $this->isFacility()
                ? 'required|exists:locations,id'
                : 'nullable',
            "rows.$index.task_category_id" =>
                'required|exists:task_categories,id',
            "rows.$index.working_duration" =>
                'nullable|numeric|min:0',
            "rows.$index.working_area" =>
                'nullable|numeric|min:0',
            "rows.$index.consume_l_per_hour" => $this->isFacility()
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0',
            "rows.$index.total_consume_liter" => $this->isFacility()
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0',
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
                    'tractor_id' => $this->isFacility() ? null : $row['tractor_id'],
                    'driver_id' => $this->isFacility() ? null : $row['driver_id'],
                    'zone_id' => $this->isFacility() ? null : $row['zone_id'],
                    'zone_block_id' => $this->isFacility() ? null : $row['zone_block_id'],
                    'machine_id' => $this->isFacility() ? ($row['machine_id'] ?? null) : null,
                    'location_id' => $this->isFacility() ? ($row['location_id'] ?? null) : null,
                    'task_category_id' =>
                        $row['task_category_id'],
                    'working_duration' =>
                        $row['working_duration'] ?: 0,
                    'working_area' =>
                        $this->isFacility() ? 0 : ($row['working_area'] ?: 0),
                    'diesel_start' =>
                        $this->isFacility() ? 0 : ($row['diesel_start'] ?: 0),
                    'diesel_refill' =>
                        $this->isFacility() ? $calculated['diesel_consumed'] : ($row['diesel_refill'] ?: 0),
                    'diesel_end' =>
                        $this->isFacility() ? 0 : ($row['diesel_end'] ?: 0),
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
                    $this->isFacility() ? null : $row['tractor_id'],
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
            'machine_id' => $log->machine_id,
            'location_id' => $log->location_id,
            'task_category_id' => $log->task_category_id,
            'working_duration' => $log->working_duration,
            'working_area' => $log->working_area,
            'consume_l_per_hour' => $log->diesel_per_hectare,
            'total_consume_liter' => $log->diesel_consumed,
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
            'machine_id' => '',
            'location_id' => '',
            'consume_l_per_hour' => '',
            'total_consume_liter' => '',
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
            'editRow.tractor_id' => $this->isFacility()
                ? 'nullable'
                : 'required|exists:tractors,id',
            'editRow.driver_id' => $this->isFacility()
                ? 'nullable'
                : 'required|exists:drivers,id',
            'editRow.zone_id' => $this->isFacility()
                ? 'nullable'
                : 'required|exists:zones,id',
            'editRow.zone_block_id' => $this->isFacility()
                ? 'nullable'
                : 'required|exists:zone_blocks,id',
            'editRow.machine_id' => $this->isFacility()
                ? 'required|exists:machines,id'
                : 'nullable',
            'editRow.location_id' => $this->isFacility()
                ? 'required|exists:locations,id'
                : 'nullable',
            'editRow.task_category_id' =>
                'required|exists:task_categories,id',
            'editRow.working_duration' =>
                'nullable|numeric|min:0',
            'editRow.working_area' =>
                'nullable|numeric|min:0',
            'editRow.consume_l_per_hour' => $this->isFacility()
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0',
            'editRow.total_consume_liter' => $this->isFacility()
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0',
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
                            $this->isFacility() ? null : $this->editRow['tractor_id'],
                            $log->id,
                            $this->editRow['work_date']
                        );
                    }

                    if ($difference < 0) {
                        $this->returnFuelStock(
                            abs($difference),
                            $this->isFacility() ? null : $this->editRow['tractor_id'],
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
                            $this->isFacility() ? null : $this->editRow['tractor_id'],
                        'driver_id' =>
                            $this->isFacility() ? null : $this->editRow['driver_id'],
                        'zone_id' =>
                            $this->isFacility() ? null : $this->editRow['zone_id'],
                        'zone_block_id' =>
                            $this->isFacility() ? null : $this->editRow['zone_block_id'],
                        'machine_id' =>
                            $this->isFacility() ? ($this->editRow['machine_id'] ?? null) : null,
                        'location_id' =>
                            $this->isFacility() ? ($this->editRow['location_id'] ?? null) : null,
                        'task_category_id' =>
                            $this->editRow['task_category_id'],
                        'working_duration' =>
                            $this->editRow['working_duration'] ?: 0,
                        'working_area' =>
                            $this->isFacility() ? 0 : ($this->editRow['working_area'] ?: 0),
                        'diesel_start' =>
                            $this->isFacility() ? 0 : ($this->editRow['diesel_start'] ?: 0),
                        'diesel_refill' =>
                            $this->isFacility() ? $newDieselUsed : ($this->editRow['diesel_refill'] ?: 0),
                        'diesel_end' =>
                            $this->isFacility() ? 0 : ($this->editRow['diesel_end'] ?: 0),
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
        $this->machineId = '';
        $this->locationId = '';
        $this->perPage = 15;
    }

    private function logsQuery()
    {
        return FarmWorkLog::query()
            ->with([
                'workPlan.taskCategory.group',
                'workPlan.activities.taskCategory.group',
                'workPlan.machine',
                'workPlan.location',
                'machine',
                'location',
                'tractor',
                'driver',
                'zone',
                'zoneBlock.zone',
                'taskCategory',
            ])
            ->where(function ($query) {
                $query
                    ->whereHas('workPlan.activities.taskCategory.group', function ($groupQuery) {
                        $groupQuery->where('group_type', $this->workLogType);
                    })
                    ->orWhereHas('workPlan.taskCategory.group', function ($groupQuery) {
                        $groupQuery->where('group_type', $this->workLogType);
                    });
            })
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
                                    'machine',
                                    function ($machineQuery) use ($search) {
                                        $machineQuery->where(
                                            'name',
                                            'like',
                                            '%' . $search . '%'
                                        );
                                    }
                                )
                                ->orWhereHas(
                                    'location',
                                    function ($locationQuery) use ($search) {
                                        $locationQuery->where(
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
            )
            ->when(
                $this->isFacility() && filled($this->machineId),
                fn ($query) => $query->where(
                    'machine_id',
                    $this->machineId
                )
            )
            ->when(
                $this->isFacility() && filled($this->locationId),
                fn ($query) => $query->where(
                    'location_id',
                    $this->locationId
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
            'J1' => $this->workLogQtyLabel(),
            'K1' => 'Diesel Start',
            'L1' => 'Diesel Refill',
            'M1' => 'Diesel End',
            'N1' => 'Diesel Used',
            'O1' => $this->workLogDieselRateLabel(),
            'P1' => $this->workLogProductivityLabel(),
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
    public function getTotalConsumePerHourProperty()
{
    return (clone $this->logsQuery())
        ->sum('diesel_per_hectare');
}
    public function getDraftFacilityTotalsProperty()
{
    if (!$this->isFacility()) {
        return [
            'consume_l_per_hour' => 0,
            'total_hour' => 0,
            'total_consume_liter' => 0,
        ];
    }

    $consumePerHour = 0;
    $totalHour = 0;
    $totalConsume = 0;

    foreach ($this->rows as $row) {
        $calc = $this->calculateRow($row);

        $consumePerHour += (float) ($row['consume_l_per_hour'] ?? 0);
        $totalHour += (float) ($row['working_duration'] ?? 0);
        $totalConsume += (float) ($calc['diesel_consumed'] ?? 0);
    }

    return [
        'consume_l_per_hour' => $consumePerHour,
        'total_hour' => $totalHour,
        'total_consume_liter' => $totalConsume,
    ];
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
                ->paginate($perPage),

            'workPlans' => FarmWorkPlan::with([
                    'taskCategory.group',
                    'activities.taskCategory.group',
                    'machine',
                    'location',
                ])
                ->whereIn(
                    'status',
                    ['in_progress', 'complete']
                )
                ->where(function ($query) {
                    $query
                        ->whereHas('activities.taskCategory.group', function ($groupQuery) {
                            $groupQuery->where('group_type', $this->workLogType);
                        })
                        ->orWhereHas('taskCategory.group', function ($groupQuery) {
                            $groupQuery->where('group_type', $this->workLogType);
                        });
                })
                ->latest('plan_date')
                ->latest('id')
                ->get(),

            'filterWorkPlans' => FarmWorkPlan::with([
                    'taskCategory.group',
                    'activities.taskCategory.group',
                    'machine',
                    'location',
                ])
                ->where(function ($query) {
                    $query
                        ->whereHas('activities.taskCategory.group', function ($groupQuery) {
                            $groupQuery->where('group_type', $this->workLogType);
                        })
                        ->orWhereHas('taskCategory.group', function ($groupQuery) {
                            $groupQuery->where('group_type', $this->workLogType);
                        });
                })
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

            'machines' => Machine::query()
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('name')
                ->get(),

            'locations' => Location::query()
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('name')
                ->get(),

            'taskCategories' => TaskCategory::with('group')
                ->where(
                    'status',
                    'active'
                )
                ->whereHas('group', function ($query) {
                    $query->where('group_type', $this->workLogType);
                })
                ->orderBy('name')
                ->get(),
        ];
    }
};

?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')
    @once
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet"
    >
@endonce

    <style>
    .filter-panel {
    margin-bottom: 18px;
    overflow: hidden;
    padding: 0 18px !important;
}

.filter-toggle-header {
    width: 100%;
    min-height: 48px;
    border: 0;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    cursor: pointer;
    user-select: none;
    padding: 0;
}

.filter-toggle-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #2563eb;
    font-size: 18px;
    font-weight: 700;
}

.filter-toggle-title svg {
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    color: #2563eb;
}

.filter-body {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transform: translateY(-6px);
    transition:
        max-height 0.35s ease,
        opacity 0.25s ease,
        transform 0.25s ease,
        padding-top 0.25s ease,
        padding-bottom 0.25s ease,
        margin-top 0.25s ease;
}

.filter-panel.is-open .filter-body {
    max-height: 520px;
    opacity: 1;
    margin-top: 8px;
    padding-top: 14px;
    padding-bottom: 18px;
    border-top: 1px solid #e5e7eb;
    transform: translateY(0);
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

    .work-plan-label {
        display: inline-block;
        min-width: 300px;
        font-size: 13px;
        font-weight: 900;
        color: #0f172a;
        white-space: nowrap;
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

    /*
     * Work Log row-size fix:
     * Existing saved rows stay compact after Add New.
     */
    .work-table tbody > tr:not(.new-row) {
        height: 52px !important;
        min-height: 52px !important;
        max-height: 52px !important;
    }

    .work-table tbody > tr:not(.new-row) > td {
        height: 52px !important;
        min-height: 52px !important;
        max-height: 52px !important;
        padding-top: 7px !important;
        padding-bottom: 7px !important;
        vertical-align: middle !important;
        line-height: 1.2 !important;
    }

    /*
     * Keep saved-row action buttons on one line.
     */
    .work-table tbody > tr:not(.new-row) .table-actions {
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
    }

    .work-table tbody > tr:not(.new-row) .table-actions .mini {
        flex: 0 0 auto !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        white-space: nowrap !important;
    }

    /*
     * Give the Action column enough space.
     */
    .work-table th:last-child,
    .work-table td:last-child {
        width: 150px !important;
        min-width: 150px !important;
    }

    /*
     * Only Add/Edit rows can increase their height.
     */
    .work-table tbody > tr.new-row {
        height: auto !important;
        min-height: 62px !important;
        max-height: none !important;
    }

    .work-table tbody > tr.new-row > td {
        height: auto !important;
        min-height: 62px !important;
        max-height: none !important;
        padding-top: 9px !important;
        padding-bottom: 9px !important;
        vertical-align: middle !important;
    }

    .work-table tbody > tr.new-row .table-actions {
        flex-wrap: wrap !important;
    }

    /*
     * Keep the Work Log footer compact.
     */
    .work-table tfoot > tr,
    .work-table tfoot > tr > td {
        height: 58px !important;
        min-height: 58px !important;
        max-height: 58px !important;
    }
    .total-consume-input {
    font-weight: 900 !important;
    color: #0f172a !important;
    }

    .total-consume-input::placeholder {
        font-weight: 900 !important;
        color: #0f172a !important;
        opacity: 1 !important;
    }
    .work-plan-select-wrap {
    width: 420px;
    min-width: 420px;
    max-width: 420px;
}

.work-plan-select-wrap .select2-container {
    width: 100% !important;
}

.work-plan-select-wrap .select2-selection--single {
    height: auto !important;
    min-height: 42px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 10px !important;
    background: #ffffff !important;
}

.work-plan-select-wrap
.select2-selection--single
.select2-selection__rendered {
    min-height: 42px;
    padding: 8px 34px 8px 10px !important;
    color: #0f172a !important;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.4 !important;
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.work-plan-select-wrap
.select2-selection--single
.select2-selection__arrow {
    top: 8px !important;
    right: 8px !important;
}

.work-plan-wrap-dropdown {
    width: 420px !important;
    max-width: 420px !important;
}

.work-plan-wrap-dropdown .select2-results__option {
    padding: 9px 12px !important;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.saved-work-plan-cell {
    width: 420px;
    min-width: 320px;
    max-width: 420px;
    white-space: normal !important;
    vertical-align: top !important;
}

.saved-work-plan-cell .work-plan-label {
    display: block !important;
    width: 100%;
    min-width: 0 !important;
    max-width: 420px;
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
    line-height: 1.5 !important;
}

.work-table tbody > tr.saved-work-log-row,
.work-table tbody > tr.saved-work-log-row > td {
    height: auto !important;
    min-height: 52px !important;
    max-height: none !important;
}
</style>

    <div class="page-header">
        <div>
            <h1 class="page-title">
                {{ $this->workLogTypeLabel() }} Work Logs
            </h1>
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

    <div class="panel filter-panel {{ $filterOpen ? 'is-open' : '' }}">
    <button
        type="button"
        class="filter-toggle-header"
        wire:click="toggleFilter"
    >
        <span class="filter-toggle-title">
            <svg
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
            >
                <path d="M3 5a1 1 0 0 1 1-1h16a1 1 0 0 1 .8 1.6L14 14.67V20a1 1 0 0 1-1.45.89l-4-2A1 1 0 0 1 8 18v-3.33L3.2 5.6A1 1 0 0 1 3 5Z" />
            </svg>
            <span>Filters</span>
        </span>
    </button>

    <div class="filter-body">
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
                            {{ $this->formatWorkPlanSelectLabel($plan) }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($this->isFacility())
    <div>
        <label>Equipment</label>

        <select wire:model.live="machineId">
            <option value="">All Equipment</option>

            @foreach($machines as $machine)
                <option value="{{ $machine->id }}">
                    {{ $machine->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Location</label>

        <select wire:model.live="locationId">
            <option value="">All Locations</option>

            @foreach($locations as $location)
                <option value="{{ $location->id }}">
                    {{ $location->name }}
                </option>
            @endforeach
        </select>
    </div>
@else
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
@endif

            <div>
                <label>{{ $this->isFacility() ? 'Task' : 'Task Category' }}</label>

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
                        <th>Task</th>

                        @if($this->isFacility())
                            <th>Equipment</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Consume L/H</th>
                            <th>Total Hour</th>
                            <th>Total Consume (L)</th>
                        @else
                            <th>Zone / Sub Zone</th>
                            <th>Date</th>
                            <th>Tractor</th>
                            <th>Driver</th>
                            <th>Hour</th>
                            <th>{{ $this->workLogQtyLabel() }}</th>
                            <th>Diesel Start</th>
                            <th>Diesel Refill</th>
                            <th>Diesel End</th>
                            <th>Diesel Used</th>
                            <th>{{ $this->workLogDieselRateLabel() }}</th>
                            <th>{{ $this->workLogProductivityLabel() }}</th>
                        @endif

                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        @php
                            $savedPlanTaskCategory =
                                $log->workPlan?->activities->first()?->taskCategory
                                ?? $log->workPlan?->taskCategory;

                            $savedTaskGroupName =
                                $savedPlanTaskCategory?->group?->name
                                ?? $log->workPlan?->title
                                ?? '-';

                            $savedWorkPlanLabel = $log->workPlan
                                ? $this->formatWorkPlanSelectLabel($log->workPlan)
                                : 'No Plan';
                        @endphp

                        @if((int) $editingId === (int) $log->id)
                            <tr class="new-row" wire:key="edit-work-log-{{ $log->id }}">
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td>
                                    <div
                                        class="work-plan-select-wrap"
                                        wire:ignore
                                        wire:key="edit-work-plan-select-{{ $log->id }}"
                                    >
                                        <select
                                            id="edit-work-plan-{{ $log->id }}"
                                            class="js-work-plan-wrap-select"
                                            data-mode="edit"
                                            data-model="editRow.farm_work_plan_id"
                                        >
                                            <option value="">Select Work Plan</option>

                                            @foreach($workPlans as $plan)
                                                <option
                                                    value="{{ $plan->id }}"
                                                    @selected(
                                                        (string) ($editRow['farm_work_plan_id'] ?? '') ===
                                                        (string) $plan->id
                                                    )
                                                >
                                                    {{ $this->formatWorkPlanSelectLabel($plan) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
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
                                                : 'Select Task' }}
                                        </option>

                                        @foreach($this->getWorkPlanTaskCategories($editRow['farm_work_plan_id'] ?? null) as $taskCategory)
                                            <option value="{{ $taskCategory->id }}">
                                                {{ $taskCategory->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                @if($this->isFacility())
                                    <td>
                                        <input
                                            type="text"
                                            class="task-group-readonly"
                                            value="{{ $this->getMachineName($editRow['machine_id'] ?? null) }}"
                                            readonly
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            class="task-group-readonly"
                                            value="{{ $this->getLocationName($editRow['location_id'] ?? null) }}"
                                            readonly
                                        >
                                    </td>

                                    <td>
                                        <input type="date" wire:model.live="editRow.work_date">
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            wire:model.live="editRow.consume_l_per_hour"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            wire:model.live="editRow.working_duration"
                                        >
                                    </td>

                                    @php
                                        $calc = $this->calculateRow($editRow);
                                    @endphp

                                    <td>
                                        <input
                                                type="number"
                                                step="0.01"
                                                class="total-consume-input"
                                                wire:model.live="editRow.total_consume_liter"
                                            >
                                    </td>
                                @else
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

                                            @foreach($this->getWorkPlanZoneBlocks($editRow['farm_work_plan_id'] ?? null) as $block)
                                                <option value="{{ $block->id }}">
                                                    {{ $this->formatZoneBlockLabel($block) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <input type="date" wire:model.live="editRow.work_date">
                                    </td>

                                    <td>
                                        <select wire:model.live="editRow.tractor_id">
                                            <option value="">Select Tractor</option>

                                            @foreach($tractors as $tractor)
                                                <option value="{{ $tractor->id }}">
                                                    {{ $tractor->tractor_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <select wire:model.live="editRow.driver_id">
                                            <option value="">Select Driver</option>

                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}">
                                                    {{ $driver->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" wire:model.live="editRow.working_duration">
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" wire:model.live="editRow.working_area">
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" wire:model.live="editRow.diesel_start">
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" wire:model.live="editRow.diesel_refill">
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" wire:model.live="editRow.diesel_end">
                                    </td>

                                    @php
                                        $calc = $this->calculateRow($editRow);
                                    @endphp

                                    <td>
                                        <strong>{{ number_format((float) $calc['diesel_consumed'], 2) }}</strong>
                                    </td>

                                    <td>
                                        {{ number_format((float) $calc['diesel_per_hectare'], 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $calc['hectare_per_hour'], 2) }}
                                    </td>
                                @endif

                                <td>
                                    <div class="table-actions">
                                        <button type="button" wire:click="updateRow" class="mini">Save</button>
                                        <button type="button" wire:click="cancelEdit" class="mini danger">Cancel</button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr
                                    class="saved-work-log-row"
                                    wire:key="work-log-{{ $log->id }}"
                                >
                                <td class="row-no">{{ $loop->iteration }}</td>

                                <td class="saved-work-plan-cell">
                                    @if($log->workPlan)
                                        <span class="work-plan-label">
                                            {{ $savedWorkPlanLabel }}
                                        </span>
                                    @else
                                        <span class="no-plan">No Plan</span>
                                    @endif
                                </td>

                                <td>
                                    <strong>{{ $savedTaskGroupName }}</strong>
                                </td>

                                <td>
                                    {{ $log->taskCategory->name ?? '-' }}
                                </td>

                                @if($this->isFacility())
                                    <td>{{ $log->machine?->name ?? $log->workPlan?->machine?->name ?? '-' }}</td>
                                    <td>{{ $log->location?->name ?? $log->workPlan?->location?->name ?? '-' }}</td>

                                    <td>
                                        {{ optional($log->work_date)->format('d M Y') ?: $log->work_date }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->diesel_per_hectare, 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->working_duration, 2) }}
                                    </td>

                                    <td>
                                        <strong>{{ number_format((float) $log->diesel_consumed, 2) }}</strong>
                                    </td>
                                @else
                                    <td>
                                        @if($log->zoneBlock)
                                            <span class="zone-display">
                                                {{ $this->formatZoneBlockLabel($log->zoneBlock) }}
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

                                            <span class="sub-zone-display" style="color: #94a3b8;">
                                                No sub zone
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ optional($log->work_date)->format('d M Y') ?: $log->work_date }}
                                    </td>

                                    <td>
                                        {{ $log->tractor->tractor_no ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $log->driver->name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->working_duration, 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->working_area, 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->diesel_start, 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->diesel_refill, 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->diesel_end, 2) }}
                                    </td>

                                    <td>
                                        <strong>{{ number_format((float) $log->diesel_consumed, 2) }}</strong>
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->diesel_per_hectare, 2) }}
                                    </td>

                                    <td>
                                        {{ number_format((float) $log->hectare_per_hour, 2) }}
                                    </td>
                                @endif

                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('work_logs.edit'))
                                            <button type="button" wire:click="edit({{ $log->id }})" class="mini">
                                                Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasPermission('work_logs.delete'))
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
                            <td colspan="{{ $this->isFacility() ? 11 : 17 }}" class="empty">
                                No work log found.
                            </td>
                        </tr>
                    @endforelse

                    @foreach($rows as $index => $row)
                        <tr class="new-row" wire:key="new-work-log-{{ $index }}">
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
                                    <div
                                        class="work-plan-select-wrap"
                                        wire:ignore
                                        wire:key="new-work-plan-select-{{ $index }}"
                                    >
                                        <select
                                            id="new-work-plan-{{ $index }}"
                                            class="js-work-plan-wrap-select"
                                            data-mode="create"
                                            data-index="{{ $index }}"
                                            data-model="rows.{{ $index }}.farm_work_plan_id"
                                        >
                                            <option value="">Select Work Plan</option>

                                            @foreach($workPlans as $plan)
                                                <option
                                                    value="{{ $plan->id }}"
                                                    @selected(
                                                        (string) ($row['farm_work_plan_id'] ?? '') ===
                                                        (string) $plan->id
                                                    )
                                                >
                                                    {{ $this->formatWorkPlanSelectLabel($plan) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
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
                                            : 'Select Task' }}
                                    </option>

                                    @foreach($this->getWorkPlanTaskCategories($row['farm_work_plan_id'] ?? null) as $taskCategory)
                                        <option value="{{ $taskCategory->id }}">
                                            {{ $taskCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            @if($this->isFacility())
                                <td>
                                    <input
                                        type="text"
                                        class="task-group-readonly"
                                        value="{{ $this->getMachineName($row['machine_id'] ?? null) }}"
                                        readonly
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        class="task-group-readonly"
                                        value="{{ $this->getLocationName($row['location_id'] ?? null) }}"
                                        readonly
                                    >
                                </td>

                                <td>
                                    <input type="date" wire:model.live="rows.{{ $index }}.work_date">
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="rows.{{ $index }}.consume_l_per_hour"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        wire:model.live="rows.{{ $index }}.working_duration"
                                    >
                                </td>

                                @php
                                    $calc = $this->calculateRow($row);
                                @endphp

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        class="total-consume-input"
                                        wire:model.live="rows.{{ $index }}.total_consume_liter"
                                        placeholder="{{ number_format((float) $calc['diesel_consumed'], 2) }}"
                                    >
                                </td>
                            @else
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

                                        @foreach($this->getWorkPlanZoneBlocks($row['farm_work_plan_id'] ?? null) as $block)
                                            <option value="{{ $block->id }}">
                                                {{ $this->formatZoneBlockLabel($block) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input type="date" wire:model.live="rows.{{ $index }}.work_date">
                                </td>

                                <td>
                                    <select wire:model.live="rows.{{ $index }}.tractor_id">
                                        <option value="">Select Tractor</option>

                                        @foreach($tractors as $tractor)
                                            <option value="{{ $tractor->id }}">
                                                {{ $tractor->tractor_no }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <select wire:model.live="rows.{{ $index }}.driver_id">
                                        <option value="">Select Driver</option>

                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}">
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.working_duration">
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.working_area">
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_start">
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_refill">
                                </td>

                                <td>
                                    <input type="number" step="0.01" wire:model.live="rows.{{ $index }}.diesel_end">
                                </td>

                                @php
                                    $calc = $this->calculateRow($row);
                                @endphp

                                <td>
                                    <strong>{{ number_format((float) $calc['diesel_consumed'], 2) }}</strong>
                                </td>

                                <td>
                                    {{ number_format((float) $calc['diesel_per_hectare'], 2) }}
                                </td>

                                <td>
                                    {{ number_format((float) $calc['hectare_per_hour'], 2) }}
                                </td>
                            @endif

                            <td>
                                <div class="table-actions">
                                    <button type="button" wire:click="saveRow({{ $index }})" class="mini">
                                        Save
                                    </button>

                                    <button type="button" wire:click="removeRow({{ $index }})" class="mini danger">
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
                            @if(auth()->user()->hasPermission('work_logs.create'))
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

                        @if($this->isFacility())
                            <td colspan="6" style="text-align: right;">Total</td>

                            <td>
                                {{ number_format((float) $this->totalConsumePerHour + (float) $this->draftFacilityTotals['consume_l_per_hour'], 2) }}
                            </td>

                            <td>
                                {{ number_format((float) $this->totalHours + (float) $this->draftFacilityTotals['total_hour'], 2) }}
                            </td>

                            <td>
                                {{ number_format((float) $this->totalDieselUsed + (float) $this->draftFacilityTotals['total_consume_liter'], 2) }}
                            </td>

                            <td>-</td>
                        @else
                            <td colspan="7" style="text-align: right;">Total</td>

                            <td>
                                {{ number_format((float) $this->totalHours, 2) }}
                            </td>

                            <td>
                                {{ number_format((float) $this->totalArea, 2) }}
                            </td>

                            <td>-</td>

                            <td>
                                {{ number_format((float) $this->totalDieselRefill, 2) }}
                            </td>

                            <td>-</td>

                            <td>
                                {{ number_format((float) $this->totalDieselUsed, 2) }}
                            </td>

                            <td>
                                {{ number_format((float) $this->avgLiterPerHa, 2) }}
                            </td>

                            <td>
                                {{ number_format((float) $this->avgHaPerHr, 2) }}
                            </td>

                            <td>-</td>
                        @endif
                    </tr>
                </tfoot>
            </table>
             <div style="padding: 14px;">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    @once
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endonce
    @script
<script>
    const initializeWorkPlanWrapSelects = () => {
        document
            .querySelectorAll('.js-work-plan-wrap-select')
            .forEach((element) => {
                const select = $(element);

                if (select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                select.select2({
                    width: '100%',
                    dropdownAutoWidth: false,
                    dropdownCssClass: 'work-plan-wrap-dropdown',
                    placeholder: 'Select Work Plan'
                });

                select.on('change.workPlanWrap', async function () {
                    const model = this.dataset.model;
                    const mode = this.dataset.mode;
                    const index = this.dataset.index;
                    const value = this.value;

                    await $wire.set(model, value);

                    if (mode === 'edit') {
                        await $wire.call('applyWorkPlanToEdit');
                        return;
                    }

                    await $wire.call(
                        'applyWorkPlanToRow',
                        Number(index)
                    );
                });
            });
    };

    initializeWorkPlanWrapSelects();

    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(() => {
            initializeWorkPlanWrapSelects();
        });
    });

    $wire.on('work-log-saved', () => {
        requestAnimationFrame(() => {
            const tableWrap =
                document.getElementById('workLogTableWrap');

            if (tableWrap) {
                tableWrap.scrollLeft = 0;
            }

            initializeWorkPlanWrapSelects();
        });
    });
</script>
@endscript
</div>