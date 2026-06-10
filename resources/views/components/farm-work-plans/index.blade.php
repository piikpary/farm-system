<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FarmWorkPlan;
use App\Models\TaskCategory;
use App\Models\ZoneBlock;
use App\Models\FuelStock;
use App\Models\FuelTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new class extends Component
{
    use WithPagination;

    public $paginationTheme = 'tailwind';

    public $search = '';
    public $statusFilter = '';
    public $taskCategoryFilter = '';
    public $zoneBlockFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;

    public $rows = [];
    public $editingId = null;

    public $zonePickerOpen = false;
    public $zonePickerMode = null;
    public $zonePickerIndex = null;

    public $editRow = [
        'title' => '',
        'plan_date' => '',
        'task_category_id' => '',
        'plan_start' => '',
        'plan_end' => '',
        'zone_block_ids' => [],
        'plan_area' => '',
        'request_l_per_hectare' => '',
        'status' => 'in_progress',
        'note' => '',
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedTaskCategoryFilter() { $this->resetPage(); }
    public function updatedZoneBlockFilter() { $this->resetPage(); }
    public function updatedDateFrom() { $this->resetPage(); }
    public function updatedDateTo() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function addRow()
    {
        $this->rows[] = $this->emptyRow();
    }

    public function emptyRow()
    {
        return [
            'title' => '',
            'plan_date' => now()->format('Y-m-d'),
            'task_category_id' => '',
            'plan_start' => '',
            'plan_end' => '',
            'zone_block_ids' => [],
            'plan_area' => '',
            'request_l_per_hectare' => '',
            'status' => 'in_progress',
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

    public function updatedRows($value, $key)
    {
        $parts = explode('.', $key);
        $index = $parts[0] ?? null;
        $field = $parts[1] ?? null;

        if ($index === null || !isset($this->rows[$index])) {
            return;
        }

        if ($field === 'task_category_id') {
            $task = TaskCategory::find($this->rows[$index]['task_category_id']);

            if ($task && isset($task->standard_fuel_per_hectare)) {
                $this->rows[$index]['request_l_per_hectare'] = (float) $task->standard_fuel_per_hectare;
            }
        }
    }

    public function updatedEditRowTaskCategoryId()
    {
        $task = TaskCategory::find($this->editRow['task_category_id']);

        if ($task && isset($task->standard_fuel_per_hectare)) {
            $this->editRow['request_l_per_hectare'] = (float) $task->standard_fuel_per_hectare;
        }
    }

    public function calculateRequestLiters($area, $literPerHa)
    {
        return round(((float) $area) * ((float) $literPerHa), 2);
    }

    private function deductFuelStock(float $requestLiters, string $note = null): void
    {
        if ($requestLiters <= 0) {
            return;
        }

        $fuelStock = FuelStock::where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (!$fuelStock) {
            throw ValidationException::withMessages([
                'fuel_stock' => 'No active fuel stock found. Please create fuel stock first.',
            ]);
        }

        if ((float) $fuelStock->current_stock < $requestLiters) {
            throw ValidationException::withMessages([
                'fuel_stock' => 'Not enough fuel stock. Current stock is ' . number_format((float) $fuelStock->current_stock, 2) . ' L.',
            ]);
        }

        $beforeStock = (float) $fuelStock->current_stock;
        $afterStock = $beforeStock - $requestLiters;

        $fuelStock->update([
            'current_stock' => $afterStock,
            'total_stock_out' => (float) $fuelStock->total_stock_out + $requestLiters,
            'updated_by' => Auth::id(),
        ]);

        FuelTransaction::create([
            'fuel_stock_id' => $fuelStock->id,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'stock_out',
            'quantity' => $requestLiters,
            'balance_after' => $afterStock,
            'reference_no' => 'WP-OUT-' . now()->format('YmdHis'),
            'note' => $note ?: 'Fuel requested from work plan',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function restoreFuelStock(float $restoreLiters, string $note = null): void
    {
        if ($restoreLiters <= 0) {
            return;
        }

        $fuelStock = FuelStock::where('status', 'active')
            ->lockForUpdate()
            ->first();

        if (!$fuelStock) {
            throw ValidationException::withMessages([
                'fuel_stock' => 'No active fuel stock found. Please create fuel stock first.',
            ]);
        }

        $beforeStock = (float) $fuelStock->current_stock;
        $afterStock = $beforeStock + $restoreLiters;

        $fuelStock->update([
            'current_stock' => $afterStock,
            'total_stock_in' => (float) $fuelStock->total_stock_in + $restoreLiters,
            'updated_by' => Auth::id(),
        ]);

        FuelTransaction::create([
            'fuel_stock_id' => $fuelStock->id,
            'transaction_date' => now()->format('Y-m-d'),
            'type' => 'stock_in',
            'quantity' => $restoreLiters,
            'balance_after' => $afterStock,
            'reference_no' => 'WP-IN-' . now()->format('YmdHis'),
            'note' => $note ?: 'Fuel restored from work plan',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function openRowZonePicker($index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->zonePickerOpen = true;
        $this->zonePickerMode = 'create';
        $this->zonePickerIndex = $index;
    }

    public function openEditZonePicker()
    {
        $this->zonePickerOpen = true;
        $this->zonePickerMode = 'edit';
        $this->zonePickerIndex = null;
    }

    public function closeZonePicker()
    {
        $this->zonePickerOpen = false;
        $this->zonePickerMode = null;
        $this->zonePickerIndex = null;
    }

    public function clearActiveZoneBlocks()
    {
        if ($this->zonePickerMode === 'edit') {
            $this->editRow['zone_block_ids'] = [];
            return;
        }

        if ($this->zonePickerMode === 'create' && $this->zonePickerIndex !== null && isset($this->rows[$this->zonePickerIndex])) {
            $this->rows[$this->zonePickerIndex]['zone_block_ids'] = [];
        }
    }

    public function selectAllActiveZoneBlocks()
    {
        $ids = ZoneBlock::where('status', 'active')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        if ($this->zonePickerMode === 'edit') {
            $this->editRow['zone_block_ids'] = $ids;
            return;
        }

        if ($this->zonePickerMode === 'create' && $this->zonePickerIndex !== null && isset($this->rows[$this->zonePickerIndex])) {
            $this->rows[$this->zonePickerIndex]['zone_block_ids'] = $ids;
        }
    }

    public function saveRow($index)
{
    if (!auth()->user()->hasPermission('work_plans.create')) {
        abort(403, trans()->has('pages.permission_denied') ? __('pages.permission_denied') : 'Permission denied.');
    }

    if (!isset($this->rows[$index])) {
        return;
    }

    $this->validate([
        "rows.$index.title" => 'nullable|string|max:255',
        "rows.$index.plan_date" => 'required|date',
        "rows.$index.task_category_id" => 'nullable|exists:task_categories,id',
        "rows.$index.plan_start" => 'nullable|date',
        "rows.$index.plan_end" => 'nullable|date|after_or_equal:rows.' . $index . '.plan_start',
        "rows.$index.zone_block_ids" => 'nullable|array',
        "rows.$index.zone_block_ids.*" => 'exists:zone_blocks,id',
        "rows.$index.plan_area" => 'nullable|numeric|min:0',
        "rows.$index.request_l_per_hectare" => 'nullable|numeric|min:0',
        "rows.$index.status" => 'required|in:in_progress,complete,cancelled',
        "rows.$index.note" => 'nullable|string|max:2000',
    ]);

    $row = $this->rows[$index];

    $zoneBlockIds = collect($row['zone_block_ids'] ?? [])
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->values()
        ->toArray();

    $requestLiters = $this->calculateRequestLiters(
        $row['plan_area'] ?? 0,
        $row['request_l_per_hectare'] ?? 0
    );

    FarmWorkPlan::create([
        'title' => !empty($row['title']) ? $row['title'] : null,
        'plan_date' => $row['plan_date'],
        'task_category_id' => !empty($row['task_category_id']) ? $row['task_category_id'] : null,
        'plan_start' => !empty($row['plan_start']) ? $row['plan_start'] : null,
        'plan_end' => !empty($row['plan_end']) ? $row['plan_end'] : null,
        'zone_block_ids' => $zoneBlockIds,
        'plan_area' => !empty($row['plan_area']) ? $row['plan_area'] : 0,
        'request_l_per_hectare' => !empty($row['request_l_per_hectare']) ? $row['request_l_per_hectare'] : 0,
        'request_liters' => $requestLiters,
        'status' => $row['status'],
        'note' => !empty($row['note']) ? $row['note'] : null,
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
    ]);

    unset($this->rows[$index]);
    $this->rows = array_values($this->rows);

    session()->flash('success', __('pages.work_plan_saved') ?: 'Work plan saved successfully.');
}

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('work_plans.edit')) {
            abort(403, trans()->has('pages.permission_denied') ? __('pages.permission_denied') : 'Permission denied.');
        }

        $plan = FarmWorkPlan::findOrFail($id);

        $this->editingId = $plan->id;

        $this->editRow = [
            'title' => $plan->title,
            'plan_date' => optional($plan->plan_date)->format('Y-m-d') ?: $plan->plan_date,
            'task_category_id' => $plan->task_category_id,
            'plan_start' => optional($plan->plan_start)->format('Y-m-d') ?: $plan->plan_start,
            'plan_end' => optional($plan->plan_end)->format('Y-m-d') ?: $plan->plan_end,
            'zone_block_ids' => $plan->zone_block_ids ?: [],
            'plan_area' => $plan->plan_area,
            'request_l_per_hectare' => $plan->request_l_per_hectare,
            'status' => $plan->status,
            'note' => $plan->note,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'title' => '',
            'plan_date' => '',
            'task_category_id' => '',
            'plan_start' => '',
            'plan_end' => '',
            'zone_block_ids' => [],
            'plan_area' => '',
            'request_l_per_hectare' => '',
            'status' => 'in_progress',
            'note' => '',
        ];
    }

    public function updateRow()
{
    if (!auth()->user()->hasPermission('work_plans.edit')) {
        abort(403, trans()->has('pages.permission_denied') ? __('pages.permission_denied') : 'Permission denied.');
    }

    $plan = FarmWorkPlan::findOrFail($this->editingId);

    $this->validate([
        'editRow.title' => 'nullable|string|max:255',
        'editRow.plan_date' => 'required|date',
        'editRow.task_category_id' => 'nullable|exists:task_categories,id',
        'editRow.plan_start' => 'nullable|date',
        'editRow.plan_end' => 'nullable|date|after_or_equal:editRow.plan_start',
        'editRow.zone_block_ids' => 'nullable|array',
        'editRow.zone_block_ids.*' => 'exists:zone_blocks,id',
        'editRow.plan_area' => 'nullable|numeric|min:0',
        'editRow.request_l_per_hectare' => 'nullable|numeric|min:0',
        'editRow.status' => 'required|in:in_progress,complete,cancelled',
        'editRow.note' => 'nullable|string|max:2000',
    ]);

    $zoneBlockIds = collect($this->editRow['zone_block_ids'] ?? [])
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->values()
        ->toArray();

    $requestLiters = $this->calculateRequestLiters(
        $this->editRow['plan_area'] ?? 0,
        $this->editRow['request_l_per_hectare'] ?? 0
    );

    $plan->update([
        'title' => !empty($this->editRow['title']) ? $this->editRow['title'] : null,
        'plan_date' => $this->editRow['plan_date'],
        'task_category_id' => !empty($this->editRow['task_category_id']) ? $this->editRow['task_category_id'] : null,
        'plan_start' => !empty($this->editRow['plan_start']) ? $this->editRow['plan_start'] : null,
        'plan_end' => !empty($this->editRow['plan_end']) ? $this->editRow['plan_end'] : null,
        'zone_block_ids' => $zoneBlockIds,
        'plan_area' => !empty($this->editRow['plan_area']) ? $this->editRow['plan_area'] : 0,
        'request_l_per_hectare' => !empty($this->editRow['request_l_per_hectare']) ? $this->editRow['request_l_per_hectare'] : 0,
        'request_liters' => $requestLiters,
        'status' => $this->editRow['status'],
        'note' => !empty($this->editRow['note']) ? $this->editRow['note'] : null,
        'updated_by' => Auth::id(),
    ]);

    $this->cancelEdit();

    session()->flash('success', __('pages.work_plan_updated') ?: 'Work plan updated successfully.');
}

    public function delete($id)
{
    if (!auth()->user()->hasPermission('work_plans.delete')) {
        abort(403, trans()->has('pages.permission_denied') ? __('pages.permission_denied') : 'Permission denied.');
    }

    $plan = FarmWorkPlan::findOrFail($id);
    $plan->delete();

    session()->flash('success', __('pages.work_plan_deleted') ?: 'Work plan deleted successfully.');
}

    public function resetFilter()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->taskCategoryFilter = '';
        $this->zoneBlockFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->perPage = 15;

        $this->resetPage();
    }

    private function plansQuery()
    {
        return FarmWorkPlan::with('taskCategory')->withCount('workLogs')
            ->when($this->search, function ($q) {
                $search = trim($this->search);

                $matchingBlockIds = ZoneBlock::with('zone')
                    ->where(function ($blockQuery) use ($search) {
                        $blockQuery->where('block_code', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhereHas('zone', function ($zoneQuery) use ($search) {
                                $zoneQuery->where('zone_code', 'like', '%' . $search . '%')
                                    ->orWhere('name', 'like', '%' . $search . '%');
                            });
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                $q->where(function ($query) use ($search, $matchingBlockIds) {
                    $query->where('title', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('note', 'like', '%' . $search . '%')
                        ->orWhere('plan_area', 'like', '%' . $search . '%')
                        ->orWhere('request_l_per_hectare', 'like', '%' . $search . '%')
                        ->orWhere('request_liters', 'like', '%' . $search . '%')
                        ->orWhereHas('taskCategory', function ($taskQuery) use ($search) {
                            $taskQuery->where('name', 'like', '%' . $search . '%');
                        });

                    foreach ($matchingBlockIds as $blockId) {
                        $query->orWhereJsonContains('zone_block_ids', $blockId)
                            ->orWhereJsonContains('zone_block_ids', (string) $blockId);
                    }
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->taskCategoryFilter, fn ($q) => $q->where('task_category_id', $this->taskCategoryFilter))
            ->when($this->zoneBlockFilter, function ($q) {
                $q->where(function ($query) {
                    $query->whereJsonContains('zone_block_ids', (int) $this->zoneBlockFilter)
                        ->orWhereJsonContains('zone_block_ids', (string) $this->zoneBlockFilter);
                });
            })
            ->when($this->dateFrom, fn ($q) => $q->whereDate('plan_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('plan_date', '<=', $this->dateTo));
    }

    public function getPlansProperty()
    {
        return $this->plansQuery()
            ->latest('plan_date')
            ->latest('id')
            ->paginate((int) $this->perPage);
    }

    public function exportWorkPlansExcel()
    {
        if (!auth()->user()->hasPermission('work_plans.view')) {
            abort(403, trans()->has('pages.permission_denied') ? __('pages.permission_denied') : 'Permission denied.');
        }

        $plans = $this->plansQuery()
            ->latest('plan_date')
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(__('pages.farm_work_plans'));

        $headers = [
            'A1' => trans()->has('pages.title') ? __('pages.title') : 'Title',
            'B1' => __('pages.plan_date'),
            'C1' => __('pages.activity'),
            'D1' => __('pages.plan_start'),
            'E1' => __('pages.plan_end'),
            'F1' => __('pages.zone_block'),
            'G1' => __('pages.plan_area_ha'),
            'H1' => __('pages.request_l_ha'),
            'I1' => __('pages.request_l'),
            'J1' => __('pages.status'),
            'K1' => trans()->has('pages.note') ? __('pages.note') : 'Note',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowNumber = 2;

        foreach ($plans as $plan) {
            $sheet->setCellValue('A' . $rowNumber, $plan->title ?? '-');
            $sheet->setCellValue('B' . $rowNumber, optional($plan->plan_date)->format('Y-m-d'));
            $sheet->setCellValue('C' . $rowNumber, $plan->taskCategory->name ?? '-');
            $sheet->setCellValue('D' . $rowNumber, optional($plan->plan_start)->format('Y-m-d'));
            $sheet->setCellValue('E' . $rowNumber, optional($plan->plan_end)->format('Y-m-d'));
            $sheet->setCellValue('F' . $rowNumber, $this->getZoneBlockLabel($plan->zone_block_ids));
            $sheet->setCellValue('G' . $rowNumber, (float) $plan->plan_area);
            $sheet->setCellValue('H' . $rowNumber, (float) $plan->request_l_per_hectare);
            $sheet->setCellValue('I' . $rowNumber, '=G' . $rowNumber . '*H' . $rowNumber);
            $sheet->setCellValue('J' . $rowNumber, __('pages.' . $plan->status));
            $sheet->setCellValue('K' . $rowNumber, $plan->note);

            $rowNumber++;
        }

        $lastDataRow = $rowNumber - 1;

        if ($lastDataRow >= 2) {
            $sheet->setCellValue('F' . $rowNumber, __('pages.total_plans') . ': ' . $plans->count());
            $sheet->setCellValue('G' . $rowNumber, '=SUM(G2:G' . $lastDataRow . ')');
            $sheet->setCellValue('H' . $rowNumber, '=SUM(H2:H' . $lastDataRow . ')');
            $sheet->setCellValue('I' . $rowNumber, '=SUM(I2:I' . $lastDataRow . ')');
            $sheet->setCellValue('J' . $rowNumber, '-');
            $sheet->setCellValue('K' . $rowNumber, '-');
        }

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('G2:I' . max($rowNumber, 2))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $filename = 'farm-work-plans-' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()
            ->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function getTotalPlanAreaProperty()
    {
        return (clone $this->plansQuery())->sum('plan_area');
    }

    public function getTotalRequestLiterPerHectareProperty()
    {
        return (clone $this->plansQuery())->sum('request_l_per_hectare');
    }

    public function getTotalRequestLitersProperty()
    {
        return (clone $this->plansQuery())->sum('request_liters');
    }

    public function getTotalPlansProperty()
    {
        return (clone $this->plansQuery())->count();
    }

    public function getZoneBlockLabel($ids)
{
    $ids = is_array($ids) ? $ids : [];

    if (empty($ids)) {
        return '-';
    }

    $blocks = ZoneBlock::with('zone')
        ->whereIn('id', $ids)
        ->get();

    return $blocks->map(function ($block) {
        $zoneCode = optional($block->zone)->zone_code;
        return ($zoneCode ? $zoneCode . '.' : '') . $block->block_code;
    })->implode(', ');
}

    public function getZoneBlockSummary($ids)
    {
        $ids = is_array($ids) ? $ids : [];

        if (empty($ids)) {
            return __('pages.select_zone_blocks');
        }

        if (count($ids) === 1) {
            return $this->getZoneBlockLabel($ids);
        }

        return count($ids) . ' ' . __('pages.blocks_selected');
    }

    public function getSelectedZoneBlockBadges($ids)
{
    $ids = is_array($ids) ? $ids : [];

    if (empty($ids)) {
        return collect();
    }

    return ZoneBlock::with('zone')
        ->whereIn('id', $ids)
        ->get()
        ->map(function ($block) {
            $zoneCode = optional($block->zone)->zone_code;

            return [
                'id' => $block->id,
                'label' => ($zoneCode ? $zoneCode . '.' : '') . $block->block_code,
            ];
        });
}

    public function with()
    {
        $zoneBlocks = ZoneBlock::with('zone')
            ->where('status', 'active')
            ->orderBy('zone_id')
            ->orderBy('block_code')
            ->get();

        return [
            'taskCategories' => TaskCategory::where('status', 'active')->orderBy('name')->get(),
            'zoneBlocks' => $zoneBlocks,
            'zoneBlockGroups' => $zoneBlocks->groupBy(function ($block) {
                return $block->zone_id ?: 'no_zone';
            }),
        ];
    }
};
?>

<div class="page">
    @include('components.shared-style')
    @include('components.toast-alert')

    <style>
        .filter-panel { margin-bottom: 18px; }
        .filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
        .filter-grid label { display: block; font-weight: 900; font-size: 13px; margin-bottom: 6px; color: #334155; }
        .filter-grid input,
        .filter-grid select { width: 100%; height: 46px; border: 1px solid #d1d5db; border-radius: 12px; padding: 10px 12px; font-weight: 700; background: #ffffff; }

        .list-header { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
        .list-tools { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .rows-control { display: flex; align-items: center; gap: 10px; }
        .rows-control label { font-size: 13px; font-weight: 900; color: #334155; margin: 0; }
        .rows-control select { width: 130px; height: 40px; border: 1px solid #d1d5db; border-radius: 10px; padding: 8px 10px; font-weight: 800; background: #fff; }

        .export-btn { height: 40px; border: none; border-radius: 10px; padding: 0 16px; background: #1f2937; color: #ffffff; font-size: 13px; font-weight: 900; cursor: pointer; }
        .export-btn:hover { background: #111827; }

        .inline-error { margin-bottom: 12px; padding: 12px 14px; border-radius: 12px; background: #fee2e2; color: #991b1b; font-size: 14px; font-weight: 900; border: 1px solid #fecaca; }

        .table-wrap { overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 16px; }
        .plan-table { width: 100%; min-width: 1500px; border-collapse: collapse; background: #ffffff; }
        .plan-table th { background: #f8fafc; color: #0f172a; font-size: 12px; font-weight: 900; text-transform: uppercase; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
        .plan-table td { padding: 10px; border-bottom: 1px solid #eef2f7; vertical-align: middle; white-space: nowrap; }
        .plan-table input,
        .plan-table select { width: 100%; min-width: 130px; height: 42px; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 13px; background: #ffffff; font-weight: 700; }

        .zone-block-cell { min-width: 260px; }
        .zone-select-btn { width: 100%; min-width: 230px; height: 42px; border: 1px solid #86efac; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 900; padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; }
        .zone-select-btn:hover { background: #f0fdf4; border-color: #22c55e; }
        .zone-select-count { color: #15803d; font-size: 12px; font-weight: 900; }

        .readonly-calc { background: #f8fafc !important; color: #0f172a; font-weight: 900 !important; }
        .new-row { background: #f0fdf4; }
        .new-row td { border-bottom: 1px solid #bbf7d0; }
        .row-no { width: 45px; min-width: 45px; text-align: center; font-weight: 900; color: #64748b; }

        .table-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .total-row { background: #f8fafc; font-weight: 900; border-top: 2px solid #d1d5db; }
        .total-row td { border-bottom: 0; padding: 14px 10px; color: #0f172a; }

        .plus-cell { width: 34px; height: 34px; border: none; border-radius: 10px; background: #16a34a; color: #ffffff; font-size: 20px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
        .danger-plus { background: #dc2626; }

        .status-pill { display: inline-flex; align-items: center; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: 900; }
        .status-pill.in_progress { background: #dbeafe; color: #1d4ed8; }
        .status-pill.complete { background: #dcfce7; color: #15803d; }
        .status-pill.cancelled { background: #fee2e2; color: #b91c1c; }

        .pagination-wrap { padding: 14px; border-top: 1px solid #e5e7eb; background: #ffffff; }

        .zone-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9998;
            background: rgba(15, 23, 42, 0.48);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .zone-modal {
            width: min(1050px, 96vw);
            max-height: 88vh;
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .zone-modal-body {
    padding: 16px;
    max-height: 60vh;
    overflow-y: auto;
    overflow-x: hidden;
    background: #f8fafc;
    scroll-behavior: smooth;
}

.zone-modal-body::-webkit-scrollbar {
    width: 10px;
}

.zone-modal-body::-webkit-scrollbar-track {
    background: #e5e7eb;
    border-radius: 999px;
}

.zone-modal-body::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 999px;
}

.zone-modal-body::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}
.zone-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9998;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.zone-modal {
    width: min(1100px, 95vw);
    max-height: 88vh;
    background: #ffffff;
    border-radius: 22px;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.zone-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px;
    border-bottom: 1px solid #e5e7eb;
    background: #ffffff;
    position: sticky;
    top: 0;
    z-index: 10;
}

.zone-modal-head-left {
    min-width: 0;
}

.zone-modal-title {
    font-size: 24px;
    font-weight: 950;
    color: #0f172a;
    line-height: 1.2;
}

.zone-modal-subtitle {
    margin-top: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
}

.zone-modal-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.zone-close-btn {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 12px;
    background: #ef4444;
    color: #ffffff;
    font-size: 20px;
    font-weight: 900;
    cursor: pointer;
}

.zone-close-btn:hover {
    background: #dc2626;
}

.zone-modal-body {
    padding: 16px;
    max-height: 58vh;
    overflow-y: auto;
    overflow-x: hidden;
    background: #f8fafc;
}

.zone-modal-body::-webkit-scrollbar {
    width: 10px;
}

.zone-modal-body::-webkit-scrollbar-track {
    background: #e5e7eb;
    border-radius: 999px;
}

.zone-modal-body::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 999px;
}

.zone-modal-body::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

.zone-table-selector {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.zone-row-group {
    background: #ffffff;
    border: 1px solid #dbe4ef;
    border-radius: 18px;
    overflow: hidden;
}

.zone-top {
    padding: 14px 16px;
    background: linear-gradient(180deg, #ecfdf5 0%, #f0fdf4 100%);
    border-bottom: 1px solid #bbf7d0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.zone-top-info {
    min-width: 0;
}

.zone-top-title {
    font-size: 28px;
    font-weight: 950;
    color: #166534;
    line-height: 1.1;
}

.zone-top-sub {
    margin-top: 4px;
    font-size: 13px;
    font-weight: 800;
    color: #64748b;
}

.zone-top-count {
    flex-shrink: 0;
    padding: 6px 12px;
    border-radius: 999px;
    background: #ffffff;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-size: 12px;
    font-weight: 950;
    white-space: nowrap;
}

.subzone-bottom {
    padding: 14px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.subzone-option {
    position: relative;
    min-height: 82px;
    padding: 14px 14px 14px 52px;
    border: 1px solid #dbe4ef;
    border-radius: 16px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.subzone-option:hover {
    border-color: #22c55e;
    background: #f0fdf4;
    transform: translateY(-1px);
}

.subzone-option input {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px !important;
    min-width: 20px !important;
    height: 20px !important;
    margin: 0;
    accent-color: #16a34a;
}

.subzone-option:has(input:checked) {
    border-color: #16a34a;
    background: #ecfdf5;
    box-shadow: inset 0 0 0 1px #16a34a;
}

.subzone-code {
    font-size: 18px;
    font-weight: 950;
    color: #0f172a;
    line-height: 1.2;
}

.subzone-name {
    margin-top: 4px;
    font-size: 12px;
    font-weight: 800;
    color: #64748b;
}

.zone-modal-footer {
    display: grid;
    grid-template-columns: 160px 1fr auto;
    gap: 14px;
    align-items: center;
    padding: 14px 20px;
    border-top: 1px solid #e5e7eb;
    background: #ffffff;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

.zone-footer-count {
    font-size: 15px;
    font-weight: 900;
    color: #0f172a;
}

.zone-selected-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 24px;
}

.zone-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #166534;
    border: 1px solid #bbf7d0;
    font-size: 12px;
    font-weight: 900;
}

.zone-empty-preview {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
}

@media (max-width: 1100px) {
    .subzone-bottom {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .zone-modal {
        width: 96vw;
        max-height: 92vh;
        border-radius: 18px;
    }

    .zone-modal-header {
        flex-direction: column;
        align-items: stretch;
    }

    .zone-modal-actions {
        justify-content: flex-start;
    }

    .subzone-bottom {
        grid-template-columns: 1fr;
    }

    .zone-modal-footer {
        grid-template-columns: 1fr;
        align-items: flex-start;
    }

    .zone-top {
        flex-direction: column;
        align-items: flex-start;
    }
}
        .zone-modal-title { font-size: 18px; font-weight: 950; color: #0f172a; }
        .zone-modal-subtitle { margin-top: 4px; font-size: 13px; font-weight: 700; color: #64748b; }
        .zone-modal-actions { display: flex; gap: 8px; align-items: center; }

        .zone-modal-body {
            padding: 16px;
            max-height: 58vh;
            overflow-y: auto;
            background: #f8fafc;
        }

        .zone-table-selector {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .zone-row-group {
            background: #ffffff;
            border: 1px solid #dbe4ef;
            border-radius: 18px;
            overflow: hidden;
        }

        .zone-top {
            padding: 15px 16px;
            background: #ecfdf5;
            border-bottom: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .zone-top-info { min-width: 0; }

        .zone-top-title {
            font-size: 18px;
            font-weight: 950;
            color: #14532d;
            line-height: 1.2;
        }

        .zone-top-sub {
            margin-top: 5px;
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
        }

        .zone-top-count {
            flex-shrink: 0;
            padding: 5px 10px;
            border-radius: 999px;
            background: #ffffff;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 11px;
            font-weight: 950;
            white-space: nowrap;
        }

        .subzone-bottom {
            padding: 14px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .subzone-option {
            position: relative;
            min-height: 68px;
            padding: 12px 14px 12px 48px;
            border: 1px solid #dbe4ef;
            border-radius: 18px;
            background: #ffffff;
            cursor: pointer;
            transition: 0.15s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .subzone-option:hover {
            border-color: #22c55e;
            background: #f0fdf4;
            transform: translateY(-1px);
        }

        .subzone-option input {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px !important;
            min-width: 20px !important;
            height: 20px !important;
            margin: 0;
            accent-color: #16a34a;
        }

        .subzone-code {
            font-size: 15px;
            font-weight: 950;
            color: #0f172a;
            line-height: 1.2;
        }

        .subzone-name {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
        }

        .zone-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 22px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
            position: sticky;
            bottom: 0;
            z-index: 20;
        }

        .zone-selected-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            max-width: 600px;
        }

        .zone-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 900;
        }

        @media (max-width: 1200px) {
            .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 900px) {
            .filter-grid { grid-template-columns: 1fr; }

            .subzone-bottom {
                grid-template-columns: 1fr;
            }

            .zone-top,
            .zone-modal-header,
            .zone-modal-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .zone-modal-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">{{ __('pages.farm_work_plans') }}</h1>
            <p class="page-subtitle">{{ __('pages.farm_work_plans_subtitle') }}</p>
        </div>

        <div class="page-actions">
            <a href="{{ route('dashboard') }}" class="btn gray">
                {{ __('pages.dashboard') }}
            </a>
        </div>
    </div>

    <div class="panel filter-panel">
        <h2 class="panel-title">{{ __('pages.filter') }}</h2>

        <div class="filter-grid">
            <div>
                <label>{{ __('pages.search') }}</label>
                <input type="text" wire:model.live="search" placeholder="{{ __('pages.search_work_plan_placeholder') }}">
            </div>

            <div>
                <label>{{ __('pages.date_from') }}</label>
                <input type="date" wire:model.live="dateFrom">
            </div>

            <div>
                <label>{{ __('pages.date_to') }}</label>
                <input type="date" wire:model.live="dateTo">
            </div>

            <div>
                <label>{{ __('pages.status') }}</label>
                <select wire:model.live="statusFilter">
                    <option value="">{{ __('pages.all_status') }}</option>
                    <option value="in_progress">{{ __('pages.in_progress') }}</option>
                    <option value="complete">{{ __('pages.complete') }}</option>
                    <option value="cancelled">{{ __('pages.cancelled') }}</option>
                </select>
            </div>

            <div>
                <label>{{ __('pages.activity') }}</label>
                <select wire:model.live="taskCategoryFilter">
                    <option value="">{{ __('pages.all_activities') }}</option>
                    @foreach($taskCategories as $task)
                        <option value="{{ $task->id }}">{{ $task->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>{{ __('pages.zone_block') }}</label>
                <select wire:model.live="zoneBlockFilter">
                    <option value="">{{ __('pages.all_zone_blocks') }}</option>
                    @foreach($zoneBlocks as $block)
                        <option value="{{ $block->id }}">
                            {{ optional($block->zone)->zone_code }} / {{ $block->block_code }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="margin-top:14px;">
            <button type="button" wire:click="resetFilter" class="btn gray">
                {{ __('pages.reset_filter') }}
            </button>
        </div>
    </div>

    <div class="panel">
        <div class="list-header">
            <h2 class="panel-title" style="margin:0;">{{ __('pages.work_plan_list') }}</h2>

            <div class="list-tools">
                <button type="button"
                        wire:click="exportWorkPlansExcel"
                        class="export-btn">
                    {{ __('pages.export_excel') }}
                </button>

                <div class="rows-control">
                    <label>{{ __('pages.rows_per_page') }}</label>
                    <select wire:model.live="perPage">
                        <option value="10">10 {{ trans()->has('pages.rows') ? __('pages.rows') : 'rows' }}</option>
                        <option value="15">15 {{ trans()->has('pages.rows') ? __('pages.rows') : 'rows' }}</option>
                        <option value="25">25 {{ trans()->has('pages.rows') ? __('pages.rows') : 'rows' }}</option>
                        <option value="50">50 {{ trans()->has('pages.rows') ? __('pages.rows') : 'rows' }}</option>
                        <option value="100">100 {{ trans()->has('pages.rows') ? __('pages.rows') : 'rows' }}</option>
                    </select>
                </div>
            </div>
        </div>

        @error('fuel_stock')
            <div class="inline-error">
                {{ $message }}
            </div>
        @enderror

        <div class="table-wrap">
            <table class="plan-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ trans()->has('pages.title') ? __('pages.title') : 'Title' }}</th>
                        <th>{{ __('pages.plan_date') }}</th>
                        <th>{{ __('pages.activity') }}</th>
                        <th>{{ __('pages.plan_start') }}</th>
                        <th>{{ __('pages.plan_end') }}</th>
                        <th>{{ __('pages.zone_block') }}</th>
                        <th>{{ __('pages.plan_area_ha') }}</th>
                        <th>{{ __('pages.request_l_ha') }}</th>
                        <th>{{ __('pages.request_l') }}</th>
                        <th>{{ __('pages.status') }}</th>
                        <th>Work Logs</th>
                        <th>{{ __('pages.action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($this->plans as $plan)
                        @if($editingId === $plan->id)
                            @php
                                $editRequestLiters = $this->calculateRequestLiters($editRow['plan_area'], $editRow['request_l_per_hectare']);
                            @endphp

                            <tr class="new-row">
                                <td class="row-no">{{ ($this->plans->firstItem() ?? 1) + $loop->index }}</td>
                                <td><input type="text" wire:model.live="editRow.title" placeholder="{{ trans()->has('pages.title') ? __('pages.title') : 'Title' }}"></td>
                                <td><input type="date" wire:model.live="editRow.plan_date"></td>

                                <td>
                                    <select wire:model.live="editRow.task_category_id">
                                        <option value="">{{ __('pages.select_activity') }}</option>
                                        @foreach($taskCategories as $task)
                                            <option value="{{ $task->id }}">{{ $task->name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td><input type="date" wire:model.live="editRow.plan_start"></td>
                                <td><input type="date" wire:model.live="editRow.plan_end"></td>

                                <td class="zone-block-cell">
                                    <button type="button" class="zone-select-btn" wire:click="openEditZonePicker">
                                        <span>{{ $this->getZoneBlockSummary($editRow['zone_block_ids']) }}</span>
                                        <span class="zone-select-count">{{ __('pages.choose') }}</span>
                                    </button>
                                </td>

                                <td><input type="number" step="0.01" wire:model.live="editRow.plan_area"></td>
                                <td><input type="number" step="0.01" wire:model.live="editRow.request_l_per_hectare"></td>
                                <td><input type="text" class="readonly-calc" value="{{ number_format((float) $editRequestLiters, 2) }}" readonly></td>

                                <td>
                                    <select wire:model.live="editRow.status">
                                        <option value="in_progress">{{ __('pages.in_progress') }}</option>
                                        <option value="complete">{{ __('pages.complete') }}</option>
                                        <option value="cancelled">{{ __('pages.cancelled') }}</option>
                                    </select>
                                </td>

                                <td>-</td>

                                <td>
                                    <div class="table-actions">
                                        <button type="button" wire:click="updateRow" class="mini">{{ __('pages.save') }}</button>
                                        <button type="button" wire:click="cancelEdit" class="mini danger">{{ __('pages.cancel') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="row-no">{{ ($this->plans->firstItem() ?? 1) + $loop->index }}</td>
                                <td>{{ $plan->title ?: '-' }}</td>
                                <td>{{ optional($plan->plan_date)->format('d M Y') ?: '-' }}</td>
                                <td>{{ $plan->taskCategory->name ?? '-' }}</td>
                                <td>{{ optional($plan->plan_start)->format('d M Y') ?: '-' }}</td>
                                <td>{{ optional($plan->plan_end)->format('d M Y') ?: '-' }}</td>
                                <td>{{ $this->getZoneBlockLabel($plan->zone_block_ids) }}</td>
                                <td>{{ number_format((float) $plan->plan_area, 2) }}</td>
                                <td>{{ number_format((float) $plan->request_l_per_hectare, 2) }}</td>
                                <td><strong>{{ number_format((float) $plan->request_liters, 2) }}</strong></td>
                                <td><span class="status-pill {{ $plan->status }}">{{ __('pages.' . $plan->status) }}</span></td>
                                <td>
                                    <strong>{{ number_format((int) $plan->work_logs_count) }}</strong>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('work_plans.edit'))
                                            <button type="button" wire:click="edit({{ $plan->id }})" class="mini">{{ __('pages.edit') }}</button>
                                        @endif

                                        @if(auth()->user()->hasPermission('work_plans.delete'))
                                            <button type="button" wire:click="delete({{ $plan->id }})" class="mini danger" onclick="return confirm('{{ trans()->has('pages.confirm_delete_work_plan') ? __('pages.confirm_delete_work_plan') : 'Delete this work plan?' }}')">
                                                {{ __('pages.delete') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        @if(count($rows) === 0)
                            <tr>
                                <td colspan="13" class="empty">{{ __('pages.no_work_plan_found') }}</td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        @php
                            $rowRequestLiters = $this->calculateRequestLiters($row['plan_area'], $row['request_l_per_hectare']);
                        @endphp

                        <tr class="new-row">
                            <td class="row-no">
                                <button type="button" wire:click="removeRow({{ $index }})" class="plus-cell danger-plus">×</button>
                            </td>

                            <td><input type="text" wire:model.live="rows.{{ $index }}.title" placeholder="{{ trans()->has('pages.title') ? __('pages.title') : 'Title' }}"></td>
                            <td><input type="date" wire:model.live="rows.{{ $index }}.plan_date"></td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.task_category_id">
                                    <option value="">{{ __('pages.select_activity') }}</option>
                                    @foreach($taskCategories as $task)
                                        <option value="{{ $task->id }}">{{ $task->name }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <td><input type="date" wire:model.live="rows.{{ $index }}.plan_start"></td>
                            <td><input type="date" wire:model.live="rows.{{ $index }}.plan_end"></td>

                            <td class="zone-block-cell">
                                <button type="button" class="zone-select-btn" wire:click="openRowZonePicker({{ $index }})">
                                    <span>{{ $this->getZoneBlockSummary($row['zone_block_ids']) }}</span>
                                    <span class="zone-select-count">{{ __('pages.choose') }}</span>
                                </button>
                            </td>

                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.plan_area"></td>
                            <td><input type="number" step="0.01" wire:model.live="rows.{{ $index }}.request_l_per_hectare"></td>
                            <td><input type="text" class="readonly-calc" value="{{ number_format((float) $rowRequestLiters, 2) }}" readonly></td>

                            <td>
                                <select wire:model.live="rows.{{ $index }}.status">
                                    <option value="in_progress">{{ __('pages.in_progress') }}</option>
                                    <option value="complete">{{ __('pages.complete') }}</option>
                                    <option value="cancelled">{{ __('pages.cancelled') }}</option>
                                </select>
                            </td>

                            <td>-</td>

                            <td>
                                <div class="table-actions">
                                    <button type="button" wire:click="saveRow({{ $index }})" class="mini">{{ __('pages.save') }}</button>
                                    <button type="button" wire:click="removeRow({{ $index }})" class="mini danger">{{ __('pages.remove') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td>
                            @if(auth()->user()->hasPermission('work_plans.create'))
                                <button type="button" wire:click="addRow" class="plus-cell">+</button>
                            @else
                                -
                            @endif
                        </td>

                        <td colspan="6" style="text-align:right;">
                            {{ __('pages.total_plans') }}: {{ number_format((int) $this->totalPlans) }}
                        </td>

                        <td>{{ number_format((float) $this->totalPlanArea, 2) }}</td>
                        <td>{{ number_format((float) $this->totalRequestLiterPerHectare, 2) }}</td>
                        <td>{{ number_format((float) $this->totalRequestLiters, 2) }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>

            <div class="pagination-wrap">
                {{ $this->plans->links() }}
            </div>
        </div>
    </div>

   @if($zonePickerOpen)
    @php
        $activeSelectedIds = [];

        if ($zonePickerMode === 'edit') {
            $activeSelectedIds = $editRow['zone_block_ids'] ?? [];
        }

        if ($zonePickerMode === 'create' && $zonePickerIndex !== null && isset($rows[$zonePickerIndex])) {
            $activeSelectedIds = $rows[$zonePickerIndex]['zone_block_ids'] ?? [];
        }
    @endphp

    <div class="zone-modal-backdrop" wire:click.self="closeZonePicker">
        <div class="zone-modal">
            <div class="zone-modal-header">
                <div class="zone-modal-head-left">
                    <div class="zone-modal-title">{{ __('pages.select_zone_blocks') }}</div>
                    <div class="zone-modal-subtitle">{{ __('pages.select_zone_blocks_subtitle') }}</div>
                </div>

                <div class="zone-modal-actions">
                    <button type="button" class="btn gray" wire:click="selectAllActiveZoneBlocks">
                        {{ __('pages.select_all') }}
                    </button>

                    <button type="button" class="btn gray" wire:click="clearActiveZoneBlocks">
                        {{ __('pages.clear') }}
                    </button>

                    <button type="button" class="zone-close-btn" wire:click="closeZonePicker">
                        ×
                    </button>
                </div>
            </div>

            <div class="zone-modal-body">
                <div class="zone-table-selector">
                    @forelse($zoneBlockGroups as $zoneId => $blocks)
                        @php
                            $firstBlock = $blocks->first();
                            $zone = optional($firstBlock)->zone;
                            $zoneTitle = optional($zone)->zone_code ?: 'No Zone';
                            $zoneName = optional($zone)->name;
                        @endphp

                        <div class="zone-row-group">
                            <div class="zone-top">
                                <div class="zone-top-info">
                                    <div class="zone-top-title">{{ $zoneTitle }}</div>
                                    <div class="zone-top-sub">{{ $zoneName ?: __('pages.zone') }}</div>
                                </div>

                                <div class="zone-top-count">
                                    {{ $blocks->count() }} {{ trans()->has('pages.zone_blocks') ? __('pages.zone_blocks') : 'sub zones' }}
                                </div>
                            </div>

                            <div class="subzone-bottom">
                                @foreach($blocks as $block)
                                    <label class="subzone-option">
                                        @if($zonePickerMode === 'edit')
                                            <input type="checkbox"
                                                   value="{{ $block->id }}"
                                                   wire:model.live="editRow.zone_block_ids">
                                        @else
                                            <input type="checkbox"
                                                   value="{{ $block->id }}"
                                                   wire:model.live="rows.{{ $zonePickerIndex }}.zone_block_ids">
                                        @endif

                                        <span class="subzone-code">
                                            {{ $zoneTitle }}.{{ $block->block_code }}
                                        </span>

                                        <span class="subzone-name">
                                            {{ $block->name ?: __('pages.zone_block') }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="empty">
                            {{ __('pages.no_zone_block_found') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="zone-modal-footer">
                <div class="zone-footer-count">
                    <strong>{{ count($activeSelectedIds) }}</strong> {{ __('pages.selected') }}
                </div>

                <div class="zone-selected-preview">
                    @forelse($this->getSelectedZoneBlockBadges($activeSelectedIds) as $badge)
                        <span class="zone-badge">{{ $badge['label'] }}</span>
                    @empty
                        <span class="zone-empty-preview">{{ __('pages.no_selection') }}</span>
                    @endforelse
                </div>

                <button type="button" class="btn" wire:click="closeZonePicker">
                    {{ __('pages.done') }}
                </button>
            </div>
        </div>
    </div>
@endif
</div>