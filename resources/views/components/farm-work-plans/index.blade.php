<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FarmWorkPlan;
use App\Models\TaskCategory;
use App\Models\TaskCategoryGroup;
use App\Models\ZoneBlock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public $viewActivitiesOpen = false;
    public $viewActivitiesPlan = null;

    public $editRow = [
        'task_category_group_id' => '',
        'title' => '',
        'plan_date' => '',
        'plan_start' => '',
        'plan_end' => '',
        'zone_block_ids' => [],
        'plan_area' => '',
        'request_l_per_hectare' => '',
        'activities' => [],
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
            'task_category_group_id' => '',
            'title' => '',
            'plan_date' => now()->format('Y-m-d'),
            'plan_start' => '',
            'plan_end' => '',
            'zone_block_ids' => [],
            'plan_area' => '',
            'request_l_per_hectare' => '',
            'activities' => [
                [
                    'task_category_id' => '',
                    'fuel_per_hectare' => '',
                ],
            ],
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

    public function calculateActivityFuelPerHectare($activities): float
    {
        return round(
            collect($activities ?? [])->sum(
                fn ($activity) => (float) ($activity['fuel_per_hectare'] ?? 0)
            ),
            2
        );
    }

    public function calculateRequestLiters($area, $literPerHa): float
    {
        return round((float) $area * (float) $literPerHa, 2);
    }

    public function calculateZoneBlockArea($ids): float
    {
        $ids = collect(is_array($ids) ? $ids : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $ids->isEmpty()
            ? 0
            : round((float) ZoneBlock::whereIn('id', $ids)->sum('area'), 2);
    }

    public function calculateActivityTotalFuel($area, $fuelPerHectare): float
    {
        return round((float) $area * (float) $fuelPerHectare, 2);
    }

    public function updatedRows($value, $key)
    {
        $parts = explode('.', $key);
        $rowIndex = isset($parts[0]) ? (int) $parts[0] : null;

        if (
            $rowIndex === null ||
            !isset($this->rows[$rowIndex])
        ) {
            return;
        }

        $field = $parts[1] ?? null;

        if ($field === 'zone_block_ids') {
            $this->rows[$rowIndex]['plan_area'] =
                $this->calculateZoneBlockArea(
                    $this->rows[$rowIndex]['zone_block_ids'] ?? []
                );

            return;
        }

        if ($field === 'task_category_group_id') {
            $this->rows[$rowIndex]['activities'] = [
                [
                    'task_category_id' => '',
                    'fuel_per_hectare' => '',
                ],
            ];

            return;
        }

        if (
            $field === 'activities' &&
            ($parts[3] ?? null) === 'task_category_id'
        ) {
            $activityIndex = isset($parts[2])
                ? (int) $parts[2]
                : 0;

            $taskCategory = TaskCategory::query()
                ->whereKey($value)
                ->where(
                    'task_category_group_id',
                    $this->rows[$rowIndex]['task_category_group_id'] ?? null
                )
                ->first();

            $this->rows[$rowIndex]['activities'][$activityIndex]['fuel_per_hectare'] =
                $taskCategory
                    ? (float) $taskCategory->standard_fuel_per_hectare
                    : '';
        }
    }

    public function updatedEditRowZoneBlockIds()
    {
        $this->editRow['plan_area'] = $this->calculateZoneBlockArea(
            $this->editRow['zone_block_ids'] ?? []
        );
    }

    public function updatedEditRow($value, $key)
    {
        $parts = explode('.', $key);
        $field = $parts[0] ?? null;

        if ($field === 'task_category_group_id') {
            $this->editRow['activities'] = [
                [
                    'task_category_id' => '',
                    'fuel_per_hectare' => '',
                ],
            ];

            return;
        }

        if (
            $field === 'activities' &&
            ($parts[2] ?? null) === 'task_category_id'
        ) {
            $activityIndex = isset($parts[1])
                ? (int) $parts[1]
                : 0;

            $taskCategory = TaskCategory::query()
                ->whereKey($value)
                ->where(
                    'task_category_group_id',
                    $this->editRow['task_category_group_id'] ?? null
                )
                ->first();

            $this->editRow['activities'][$activityIndex]['fuel_per_hectare'] =
                $taskCategory
                    ? (float) $taskCategory->standard_fuel_per_hectare
                    : '';
        }
    }

    public function addRowActivity($rowIndex)
    {
        if (!isset($this->rows[$rowIndex])) {
            return;
        }

        $this->rows[$rowIndex]['activities'][] = [
            'task_category_id' => '',
            'fuel_per_hectare' => '',
        ];
    }

    public function removeRowActivity($rowIndex, $activityIndex)
    {
        if (!isset($this->rows[$rowIndex]['activities'][$activityIndex])) {
            return;
        }

        unset($this->rows[$rowIndex]['activities'][$activityIndex]);

        $this->rows[$rowIndex]['activities'] = array_values(
            $this->rows[$rowIndex]['activities']
        );
    }

    public function addEditActivity()
    {
        $this->editRow['activities'][] = [
            'task_category_id' => '',
            'fuel_per_hectare' => '',
        ];
    }

    public function removeEditActivity($activityIndex)
    {
        if (!isset($this->editRow['activities'][$activityIndex])) {
            return;
        }

        unset($this->editRow['activities'][$activityIndex]);

        $this->editRow['activities'] = array_values(
            $this->editRow['activities']
        );
    }

    public function viewActivities($planId)
    {
        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])->findOrFail($planId);

        $activities = $plan->activities
            ->map(function ($activity) use ($plan) {
                return [
                    'id' => $activity->id,
                    'name' => optional($activity->taskCategory)->name ?? '-',
                    'fuel_per_hectare' => (float) $activity->fuel_per_hectare,
                    'total_fuel' => $this->calculateActivityTotalFuel(
                        $plan->plan_area,
                        $activity->fuel_per_hectare
                    ),
                ];
            })
            ->values()
            ->toArray();

        // Compatibility for older Work Plans.
        if (empty($activities) && $plan->task_category_id) {
            $activities[] = [
                'id' => 'old-' . $plan->id,
                'name' => optional($plan->taskCategory)->name ?? '-',
                'fuel_per_hectare' => (float) $plan->request_l_per_hectare,
                'total_fuel' => (float) $plan->request_liters,
            ];
        }

        $firstTaskCategory = $plan->activities->first()?->taskCategory
            ?? $plan->taskCategory;

        $this->viewActivitiesPlan = [
            'id' => $plan->id,
            'title' => $firstTaskCategory?->group?->name
                ?? $plan->title
                ?? 'Work Plan',
            'plan_area' => (float) $plan->plan_area,
            'request_l_per_hectare' => (float) $plan->request_l_per_hectare,
            'request_liters' => (float) $plan->request_liters,
            'activities' => $activities,
        ];

        $this->viewActivitiesOpen = true;
    }

    public function closeViewActivities()
    {
        $this->viewActivitiesOpen = false;
        $this->viewActivitiesPlan = null;
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
            $this->editRow['plan_area'] = 0;
            return;
        }

        if (
            $this->zonePickerMode === 'create' &&
            $this->zonePickerIndex !== null &&
            isset($this->rows[$this->zonePickerIndex])
        ) {
            $this->rows[$this->zonePickerIndex]['zone_block_ids'] = [];
            $this->rows[$this->zonePickerIndex]['plan_area'] = 0;
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
            $this->editRow['plan_area'] = $this->calculateZoneBlockArea($ids);
            return;
        }

        if (
            $this->zonePickerMode === 'create' &&
            $this->zonePickerIndex !== null &&
            isset($this->rows[$this->zonePickerIndex])
        ) {
            $this->rows[$this->zonePickerIndex]['zone_block_ids'] = $ids;
            $this->rows[$this->zonePickerIndex]['plan_area'] = $this->calculateZoneBlockArea($ids);
        }
    }

    public function saveRow($index)
    {
        if (!auth()->user()->hasPermission('work_plans.create')) {
            abort(
                403,
                trans()->has('pages.permission_denied')
                    ? __('pages.permission_denied')
                    : 'Permission denied.'
            );
        }

        if (!isset($this->rows[$index])) {
            return;
        }

        $this->validate([
            "rows.$index.task_category_group_id" => [
                'required',
                'exists:task_category_groups,id',
            ],
            "rows.$index.title" => 'nullable|string|max:255',
            "rows.$index.plan_date" => 'required|date',
            "rows.$index.plan_start" => 'nullable|date',
            "rows.$index.plan_end" => 'nullable|date|after_or_equal:rows.' . $index . '.plan_start',
            "rows.$index.zone_block_ids" => 'required|array|min:1',
            "rows.$index.zone_block_ids.*" => 'exists:zone_blocks,id',
            "rows.$index.request_l_per_hectare" => 'required|numeric|min:0',
            "rows.$index.activities" => 'required|array|min:1',
            "rows.$index.activities.*.task_category_id" => [
                'required',
                'distinct',
                Rule::exists('task_categories', 'id')->where(
                    fn ($query) => $query->where(
                        'task_category_group_id',
                        $this->rows[$index]['task_category_group_id'] ?? null
                    )
                ),
            ],
            "rows.$index.activities.*.fuel_per_hectare" => 'required|numeric|min:0',
            "rows.$index.status" => 'required|in:in_progress,complete,cancelled',
            "rows.$index.note" => 'nullable|string|max:2000',
        ]);

        $row = $this->rows[$index];

        $zoneBlockIds = collect($row['zone_block_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $planArea = $this->calculateZoneBlockArea($zoneBlockIds);

        $totalFuelPerHectare = round(
            (float) ($row['request_l_per_hectare'] ?? 0),
            2
        );

        $requestLiters = $this->calculateRequestLiters(
            $planArea,
            $totalFuelPerHectare
        );

        $taskGroupName = TaskCategoryGroup::whereKey(
            $row['task_category_group_id']
        )->value('name');

        DB::transaction(function () use (
            $row,
            $zoneBlockIds,
            $planArea,
            $totalFuelPerHectare,
            $requestLiters,
            $taskGroupName
        ) {
            $plan = FarmWorkPlan::create([
                'title' => $taskGroupName,
                'plan_date' => $row['plan_date'],
                'task_category_id' => null,
                'plan_start' => filled($row['plan_start'] ?? null) ? $row['plan_start'] : null,
                'plan_end' => filled($row['plan_end'] ?? null) ? $row['plan_end'] : null,
                'zone_block_ids' => $zoneBlockIds,
                'plan_area' => $planArea,
                'request_l_per_hectare' => $totalFuelPerHectare,
                'request_liters' => $requestLiters,
                'status' => $row['status'],
                'note' => filled($row['note'] ?? null) ? $row['note'] : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach (($row['activities'] ?? []) as $activity) {
                $plan->activities()->create([
                    'task_category_id' => (int) $activity['task_category_id'],
                    'fuel_per_hectare' => (float) $activity['fuel_per_hectare'],
                ]);
            }
        });

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        session()->flash(
            'success',
            trans()->has('pages.work_plan_saved')
                ? __('pages.work_plan_saved')
                : 'Work plan saved successfully.'
        );
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('work_plans.edit')) {
            abort(
                403,
                trans()->has('pages.permission_denied')
                    ? __('pages.permission_denied')
                    : 'Permission denied.'
            );
        }

        $plan = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])->findOrFail($id);

        $activities = $plan->activities
            ->map(fn ($activity) => [
                'task_category_id' => (string) $activity->task_category_id,
                'fuel_per_hectare' => (float) $activity->fuel_per_hectare,
            ])
            ->values()
            ->toArray();

        // Keep old records compatible.
        if (empty($activities) && $plan->task_category_id) {
            $activities[] = [
                'task_category_id' => (string) $plan->task_category_id,
                'fuel_per_hectare' => (float) $plan->request_l_per_hectare,
            ];
        }

        if (empty($activities)) {
            $activities[] = [
                'task_category_id' => '',
                'fuel_per_hectare' => '',
            ];
        }

        $firstTaskCategory = $plan->activities->first()?->taskCategory
            ?? $plan->taskCategory;

        $taskCategoryGroupId =
            $firstTaskCategory?->task_category_group_id ?? '';

        $this->editingId = $plan->id;

        $this->editRow = [
            'task_category_group_id' => $taskCategoryGroupId,
            'title' => $plan->title,
            'plan_date' => optional($plan->plan_date)->format('Y-m-d') ?: $plan->plan_date,
            'plan_start' => optional($plan->plan_start)->format('Y-m-d') ?: $plan->plan_start,
            'plan_end' => optional($plan->plan_end)->format('Y-m-d') ?: $plan->plan_end,
            'zone_block_ids' => $plan->zone_block_ids ?: [],
            'plan_area' => $plan->plan_area,
            'request_l_per_hectare' => $plan->request_l_per_hectare,
            'activities' => $activities,
            'status' => $plan->status,
            'note' => $plan->note,
        ];
    }

    public function cancelEdit()
    {
        $this->editingId = null;

        $this->editRow = [
            'task_category_group_id' => '',
            'title' => '',
            'plan_date' => '',
            'plan_start' => '',
            'plan_end' => '',
            'zone_block_ids' => [],
            'plan_area' => '',
            'request_l_per_hectare' => '',
            'activities' => [],
            'status' => 'in_progress',
            'note' => '',
        ];
    }

    public function updateRow()
    {
        if (!auth()->user()->hasPermission('work_plans.edit')) {
            abort(
                403,
                trans()->has('pages.permission_denied')
                    ? __('pages.permission_denied')
                    : 'Permission denied.'
            );
        }

        $plan = FarmWorkPlan::findOrFail($this->editingId);

        $this->validate([
            'editRow.task_category_group_id' => [
                'required',
                'exists:task_category_groups,id',
            ],
            'editRow.title' => 'nullable|string|max:255',
            'editRow.plan_date' => 'required|date',
            'editRow.plan_start' => 'nullable|date',
            'editRow.plan_end' => 'nullable|date|after_or_equal:editRow.plan_start',
            'editRow.zone_block_ids' => 'required|array|min:1',
            'editRow.zone_block_ids.*' => 'exists:zone_blocks,id',
            'editRow.request_l_per_hectare' => 'required|numeric|min:0',
            'editRow.activities' => 'required|array|min:1',
            'editRow.activities.*.task_category_id' => [
                'required',
                'distinct',
                Rule::exists('task_categories', 'id')->where(
                    fn ($query) => $query->where(
                        'task_category_group_id',
                        $this->editRow['task_category_group_id'] ?? null
                    )
                ),
            ],
            'editRow.activities.*.fuel_per_hectare' => 'required|numeric|min:0',
            'editRow.status' => 'required|in:in_progress,complete,cancelled',
            'editRow.note' => 'nullable|string|max:2000',
        ]);

        $zoneBlockIds = collect($this->editRow['zone_block_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $planArea = $this->calculateZoneBlockArea($zoneBlockIds);

        $totalFuelPerHectare = round(
            (float) ($this->editRow['request_l_per_hectare'] ?? 0),
            2
        );

        $requestLiters = $this->calculateRequestLiters(
            $planArea,
            $totalFuelPerHectare
        );

        $taskGroupName = TaskCategoryGroup::whereKey(
            $this->editRow['task_category_group_id']
        )->value('name');

        DB::transaction(function () use (
            $plan,
            $zoneBlockIds,
            $planArea,
            $totalFuelPerHectare,
            $requestLiters,
            $taskGroupName
        ) {
            $plan->update([
                'title' => $taskGroupName,
                'plan_date' => $this->editRow['plan_date'],
                'task_category_id' => null,
                'plan_start' => filled($this->editRow['plan_start'] ?? null)
                    ? $this->editRow['plan_start']
                    : null,
                'plan_end' => filled($this->editRow['plan_end'] ?? null)
                    ? $this->editRow['plan_end']
                    : null,
                'zone_block_ids' => $zoneBlockIds,
                'plan_area' => $planArea,
                'request_l_per_hectare' => $totalFuelPerHectare,
                'request_liters' => $requestLiters,
                'status' => $this->editRow['status'],
                'note' => filled($this->editRow['note'] ?? null)
                    ? $this->editRow['note']
                    : null,
                'updated_by' => Auth::id(),
            ]);

            $plan->activities()->delete();

            foreach ($this->editRow['activities'] as $activity) {
                $plan->activities()->create([
                    'task_category_id' => (int) $activity['task_category_id'],
                    'fuel_per_hectare' => (float) $activity['fuel_per_hectare'],
                ]);
            }
        });

        $this->cancelEdit();

        session()->flash(
            'success',
            trans()->has('pages.work_plan_updated')
                ? __('pages.work_plan_updated')
                : 'Work plan updated successfully.'
        );
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('work_plans.delete')) {
            abort(
                403,
                trans()->has('pages.permission_denied')
                    ? __('pages.permission_denied')
                    : 'Permission denied.'
            );
        }

        FarmWorkPlan::findOrFail($id)->delete();

        session()->flash(
            'success',
            trans()->has('pages.work_plan_deleted')
                ? __('pages.work_plan_deleted')
                : 'Work plan deleted successfully.'
        );
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
        return FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])
            ->withCount('workLogs')
            ->when($this->search, function ($q) {
                $search = trim($this->search);

                $matchingBlockIds = ZoneBlock::with('zone')
                    ->where(function ($blockQuery) use ($search) {
                        $blockQuery
                            ->where('block_code', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhereHas('zone', function ($zoneQuery) use ($search) {
                                $zoneQuery
                                    ->where('zone_code', 'like', '%' . $search . '%')
                                    ->orWhere('name', 'like', '%' . $search . '%');
                            });
                    })
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                $q->where(function ($query) use ($search, $matchingBlockIds) {
                    $query
                        ->where('title', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('note', 'like', '%' . $search . '%')
                        ->orWhere('plan_area', 'like', '%' . $search . '%')
                        ->orWhere('request_l_per_hectare', 'like', '%' . $search . '%')
                        ->orWhere('request_liters', 'like', '%' . $search . '%')
                        ->orWhereHas('taskCategory', function ($taskQuery) use ($search) {
                            $taskQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('activities.taskCategory', function ($taskQuery) use ($search) {
                            $taskQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhereHas('group', function ($groupQuery) use ($search) {
                                    $groupQuery->where('name', 'like', '%' . $search . '%');
                                });
                        });

                    foreach ($matchingBlockIds as $blockId) {
                        $query
                            ->orWhereJsonContains('zone_block_ids', $blockId)
                            ->orWhereJsonContains('zone_block_ids', (string) $blockId);
                    }
                });
            })
            ->when(
                $this->statusFilter,
                fn ($q) => $q->where('status', $this->statusFilter)
            )
            ->when($this->taskCategoryFilter, function ($q) {
                $q->where(function ($query) {
                    $query
                        ->where('task_category_id', $this->taskCategoryFilter)
                        ->orWhereHas('activities', function ($activityQuery) {
                            $activityQuery->where(
                                'task_category_id',
                                $this->taskCategoryFilter
                            );
                        });
                });
            })
            ->when($this->zoneBlockFilter, function ($q) {
                $q->where(function ($query) {
                    $query
                        ->whereJsonContains(
                            'zone_block_ids',
                            (int) $this->zoneBlockFilter
                        )
                        ->orWhereJsonContains(
                            'zone_block_ids',
                            (string) $this->zoneBlockFilter
                        );
                });
            })
            ->when(
                $this->dateFrom,
                fn ($q) => $q->whereDate('plan_date', '>=', $this->dateFrom)
            )
            ->when(
                $this->dateTo,
                fn ($q) => $q->whereDate('plan_date', '<=', $this->dateTo)
            );
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
            abort(
                403,
                trans()->has('pages.permission_denied')
                    ? __('pages.permission_denied')
                    : 'Permission denied.'
            );
        }

        $plans = $this->plansQuery()
            ->latest('plan_date')
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(__('pages.farm_work_plans'));

        $headers = [
            'A1' => __('pages.plan_date'),
            'B1' => trans()->has('pages.task_group')
                ? __('pages.task_group')
                : 'Task Group',
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
            $activityNames = $plan->activities
                ->pluck('taskCategory.name')
                ->filter()
                ->implode(', ');

            if (!$activityNames && $plan->taskCategory) {
                $activityNames = $plan->taskCategory->name;
            }

            $firstTaskCategory = $plan->activities->first()?->taskCategory
                ?? $plan->taskCategory;

            $sheet->setCellValue(
                'A' . $rowNumber,
                optional($plan->plan_date)->format('Y-m-d')
            );
            $sheet->setCellValue(
                'B' . $rowNumber,
                $firstTaskCategory?->group?->name
                    ?? $plan->title
                    ?? '-'
            );
            $sheet->setCellValue('C' . $rowNumber, $activityNames ?: '-');
            $sheet->setCellValue('D' . $rowNumber, optional($plan->plan_start)->format('Y-m-d'));
            $sheet->setCellValue('E' . $rowNumber, optional($plan->plan_end)->format('Y-m-d'));
            $sheet->setCellValue('F' . $rowNumber, $this->getZoneBlockLabel($plan->zone_block_ids));
            $sheet->setCellValue('G' . $rowNumber, (float) $plan->plan_area);
            $sheet->setCellValue('H' . $rowNumber, (float) $plan->request_l_per_hectare);
            $sheet->setCellValue('I' . $rowNumber, (float) $plan->request_liters);
            $sheet->setCellValue('J' . $rowNumber, __('pages.' . $plan->status));
            $sheet->setCellValue('K' . $rowNumber, $plan->note);

            $rowNumber++;
        }

        $lastDataRow = $rowNumber - 1;

        if ($lastDataRow >= 2) {
            $sheet->setCellValue(
                'F' . $rowNumber,
                __('pages.total_plans') . ': ' . $plans->count()
            );
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

        $sheet
            ->getStyle('G2:I' . max($rowNumber, 2))
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $filename = 'farm-work-plans-' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()
            ->download(
                $tempPath,
                $filename,
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
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

        return ZoneBlock::with('zone')
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($block) {
                $zoneCode = optional($block->zone)->zone_code;

                return ($zoneCode ? $zoneCode . '.' : '') . $block->block_code;
            })
            ->implode(', ');
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
            'taskCategoryGroups' => TaskCategoryGroup::query()
                ->where('status', true)
                ->orderBy('name')
                ->get(),
            'taskCategories' => TaskCategory::with('group')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'zoneBlocks' => $zoneBlocks,
            'zoneBlockGroups' => $zoneBlocks->groupBy(
                fn ($block) => $block->zone_id ?: 'no_zone'
            ),
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
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .filter-grid label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 13px;
            font-weight: 900;
        }

        .filter-grid input,
        .filter-grid select {
            width: 100%;
            height: 46px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            font-weight: 700;
        }

        .list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .list-tools,
        .rows-control,
        .table-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .rows-control label {
            margin: 0;
            color: #334155;
            font-size: 13px;
            font-weight: 900;
        }

        .rows-control select {
            width: 130px;
            height: 40px;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            font-weight: 800;
        }

        .export-btn {
            height: 40px;
            padding: 0 16px;
            border: 0;
            border-radius: 10px;
            background: #1f2937;
            color: #ffffff;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
        }

        .export-btn:hover {
            background: #111827;
        }

        .inline-error {
            margin-bottom: 12px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 12px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 14px;
            font-weight: 900;
        }
        /* Keep Work Plan table compact before and after clicking Add New */
.plan-table {
    height: auto !important;
    min-height: 0 !important;
}

.plan-table thead,
.plan-table tbody,
.plan-table tfoot {
    height: auto !important;
}

.plan-table tbody > tr {
    height: 52px !important;
    min-height: 52px !important;
}

.plan-table tbody > tr > td {
    height: 52px !important;
    min-height: 52px !important;
    padding-top: 7px !important;
    padding-bottom: 7px !important;
    vertical-align: middle !important;
    line-height: 1.2 !important;
}

/* Keep the Add New row compact */
.plan-table tbody > tr.new-row {
    height: 58px !important;
    min-height: 58px !important;
}

.plan-table tbody > tr.new-row > td {
    height: 58px !important;
    min-height: 58px !important;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
}

/* Keep all form controls inside the same row height */
.plan-table .new-row input,
.plan-table .new-row select,
.plan-table .new-row .activity-select-btn,
.plan-table .new-row .zone-select-btn {
    height: 40px !important;
    min-height: 40px !important;
    max-height: 40px !important;
    margin: 0 !important;
}

/* Do not allow saved rows to stretch */
.plan-table tbody > tr:not(.new-row) input,
.plan-table tbody > tr:not(.new-row) select,
.plan-table tbody > tr:not(.new-row) button {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}

/* Keep footer compact */
.plan-table tfoot tr,
.plan-table tfoot td {
    height: 58px !important;
    min-height: 58px !important;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
}

        /*
         * Keep the complete Work Plan on one compact table.
         * Horizontal scrolling is only used on smaller screens.
         */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
        }

        .plan-table {
            width: 100%;
            min-width: 1180px;
            border-collapse: collapse;
            background: #ffffff;
            table-layout: auto;
        }

        .plan-table th {
            padding: 11px 8px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #0f172a;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .saved-activity-cell {
    min-width: 145px;
    max-width: 230px;
}

.saved-activity-name {
    display: block;
    overflow: hidden;
    color: #0f172a;
    font-size: 13px;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

        .plan-table td {
            padding: 9px 8px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
            white-space: nowrap;
            font-size: 13px;
        }

        .plan-table input,
        .plan-table select {
            width: 100%;
            min-width: 92px;
            height: 40px;
            padding: 7px 9px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            font-size: 12px;
            font-weight: 700;
        }

        .col-title {
            min-width: 120px;
        }

        .col-date {
            min-width: 120px;
        }

        .col-activity {
            min-width: 145px;
        }

        .col-zone {
            min-width: 190px;
        }

        .col-number {
            min-width: 90px;
        }

        .col-status {
            min-width: 105px;
        }

        .col-action {
            min-width: 120px;
        }

        .row-no {
            width: 42px;
            min-width: 42px;
            text-align: center;
            color: #64748b;
            font-weight: 900;
        }

        .new-row {
            background: #f0fdf4;
        }

        .new-row td {
            border-bottom-color: #bbf7d0;
        }

        .readonly-calc {
            background: #f8fafc !important;
            color: #0f172a;
            font-weight: 900 !important;
        }

        .zone-select-btn,
        .activity-select-btn {
            width: 100%;
            min-width: 135px;
            height: 40px;
            padding: 7px 9px;
            border: 1px solid #86efac;
            border-radius: 9px;
            background: #ffffff;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 7px;
            cursor: pointer;
        }

        .zone-select-btn:hover,
        .activity-select-btn:hover {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .zone-select-count,
        .activity-count {
            color: #15803d;
            font-size: 11px;
            font-weight: 900;
        }

        .activity-add-icon {
            width: 24px;
            height: 24px;
            flex: 0 0 24px;
            border-radius: 7px;
            background: #16a34a;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: 900;
        }

        .view-activity-btn {
            min-width: 116px;
            height: 35px;
            padding: 0 10px;
            border: 0;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }

        .view-activity-btn:hover {
            background: #1d4ed8;
        }

        .view-activity-btn span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 21px;
            height: 21px;
            margin-left: 4px;
            padding: 0 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.25);
        }

        .plus-cell {
            width: 34px;
            height: 34px;
            border: 0;
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

        .danger-plus {
            background: #dc2626;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
        }

        .status-pill.in_progress {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-pill.complete {
            background: #dcfce7;
            color: #15803d;
        }

        .status-pill.cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .total-row {
            border-top: 2px solid #d1d5db;
            background: #f8fafc;
            font-weight: 900;
        }

        .total-row td {
            padding: 13px 8px;
            border-bottom: 0;
            color: #0f172a;
        }

        .pagination-wrap {
            padding: 14px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }

        /*
         * Shared modal styles
         */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9998;
            padding: 20px;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-card {
            width: min(900px, 96vw);
            max-height: 90vh;
            overflow: hidden;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
            display: flex;
            flex-direction: column;
        }

        .modal-card.wide {
            width: min(1050px, 96vw);
        }

        .modal-card.medium {
            width: min(740px, 96vw);
        }

        .modal-header {
            padding: 17px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
        }

        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .modal-title {
            color: #0f172a;
            font-size: 21px;
            font-weight: 950;
            line-height: 1.2;
        }

        .modal-subtitle {
            margin-top: 5px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .modal-close-btn {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border: 0;
            border-radius: 11px;
            background: #ef4444;
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            background: #dc2626;
        }

        .modal-body {
            padding: 16px;
            overflow-y: auto;
            overflow-x: hidden;
            background: #f8fafc;
        }

        .modal-footer {
            padding: 13px 18px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        /*
         * Improved inline activity editor used while creating or editing.
         */
        .activity-detail-row td {
            padding: 8px 10px 14px;
            background: #f0fdf4;
            border-bottom: 1px solid #bbf7d0;
        }

        .activity-editor-card {
            margin-left: 42px;
            border: 1px solid #bbf7d0;
            border-radius: 16px;
            background: #ffffff;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .activity-editor-header {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(180deg, #fafffc 0%, #f0fdf4 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .activity-editor-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 950;
        }

        .activity-editor-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .activity-editor-add-btn {
            height: 38px;
            padding: 0 14px;
            border: 0;
            border-radius: 10px;
            background: #16a34a;
            color: #ffffff;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
        }

        .activity-editor-add-btn:hover {
            background: #15803d;
        }

        .activity-editor-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .activity-editor-item {
            display: grid;
            grid-template-columns: 44px minmax(220px, 1fr) 125px 125px 145px 44px;
            gap: 10px;
            align-items: end;
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 13px;
            background: #f8fafc;
        }

        .activity-editor-no {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 950;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .activity-editor-label {
            display: block;
            margin-bottom: 5px;
            color: #475569;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .activity-editor-item select,
        .activity-editor-item input {
            width: 100%;
            height: 40px;
            padding: 7px 9px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            background: #ffffff;
            font-size: 12px;
            font-weight: 700;
        }

        .activity-editor-remove-wrap {
            display: flex;
            align-items: end;
            justify-content: center;
        }

        .activity-editor-remove-btn {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 9px;
            background: #dc2626;
            color: #ffffff;
            font-size: 19px;
            font-weight: 900;
            cursor: pointer;
        }

        .activity-editor-remove-btn:hover {
            background: #b91c1c;
        }

        .activity-editor-empty {
            padding: 15px;
            border: 1px dashed #cbd5e1;
            border-radius: 11px;
            background: #f8fafc;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .activity-editor-footer {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            flex-wrap: wrap;
            color: #166534;
            font-size: 13px;
            font-weight: 900;
        }

        .activity-editor-footer strong {
            font-size: 15px;
            font-weight: 950;
        }

        /*
         * View activities
         */
        .activity-view-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
        }

        .activity-view-table th,
        .activity-view-table td {
            padding: 11px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .activity-view-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 900;
        }

        .activity-view-table td {
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
        }

        .activity-summary-grid {
            margin-top: 15px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 11px;
        }

        .activity-summary-card {
            padding: 13px;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            background: #ecfdf5;
        }

        .activity-summary-card span {
            display: block;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .activity-summary-card strong {
            display: block;
            margin-top: 5px;
            color: #166534;
            font-size: 16px;
            font-weight: 950;
        }

        /*
         * Zone picker
         */
        .zone-table-selector {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .zone-row-group {
            overflow: hidden;
            border: 1px solid #dbe4ef;
            border-radius: 16px;
            background: #ffffff;
        }

        .zone-top {
            padding: 13px 15px;
            border-bottom: 1px solid #bbf7d0;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .zone-top-title {
            color: #14532d;
            font-size: 18px;
            font-weight: 950;
        }

        .zone-top-sub {
            margin-top: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
        }

        .zone-top-summary {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .zone-top-count {
            padding: 5px 10px;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #ffffff;
            color: #15803d;
            font-size: 11px;
            font-weight: 950;
            white-space: nowrap;
        }

        .subzone-bottom {
            padding: 13px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .subzone-option {
            position: relative;
            min-height: 68px;
            padding: 11px 12px 11px 46px;
            border: 1px solid #dbe4ef;
            border-radius: 14px;
            background: #ffffff;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .subzone-option:hover,
        .subzone-option:has(input:checked) {
            border-color: #16a34a;
            background: #ecfdf5;
        }

        .subzone-option input {
            position: absolute;
            top: 50%;
            left: 14px;
            width: 19px !important;
            min-width: 19px !important;
            height: 19px !important;
            margin: 0;
            transform: translateY(-50%);
            accent-color: #16a34a;
        }

        .subzone-code {
            color: #0f172a;
            font-size: 14px;
            font-weight: 950;
        }

        .subzone-name {
            margin-top: 4px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .zone-selected-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .zone-badge {
            padding: 5px 9px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 900;
        }

        .zone-empty-preview {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
        }


        .zone-select-btn > span:first-child { display:flex; flex-direction:column; align-items:flex-start; gap:2px; min-width:0; }
        .zone-select-btn small { color:#15803d; font-size:10px; font-weight:900; }
        .subzone-area { margin-top:5px; color:#15803d; font-size:12px; font-weight:950; }
        .zone-footer-summary { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .activity-editor-item input[readonly] { background:#f1f5f9; color:#0f172a; font-weight:900; }

        @media (max-width: 1200px) {
            .filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .subzone-bottom {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .filter-grid,
            .subzone-bottom,
            .activity-summary-grid {
                grid-template-columns: 1fr;
            }

            .modal-header,
            .modal-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .activity-picker-labels {
                display: none;
            }

            .activity-picker-row {
                grid-template-columns: 1fr;
                padding: 12px;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                background: #ffffff;
            }

            .activity-editor-card {
                margin-left: 0;
            }

            .activity-editor-header {
                align-items: stretch;
                flex-direction: column;
            }

            .activity-editor-add-btn {
                width: 100%;
            }

            .activity-editor-item {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .activity-editor-remove-wrap {
                justify-content: flex-start;
            }
        }

        /* FIX ONLY: prevent saved rows from stretching after Add New */
        .table-wrap {
            height: auto !important;
            min-height: 0 !important;
            align-items: flex-start !important;
        }

        .table-wrap > table.plan-table {
            display: table !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
        }

        .plan-table thead {
            display: table-header-group !important;
            height: auto !important;
        }

        .plan-table tbody {
            display: table-row-group !important;
            height: auto !important;
            min-height: 0 !important;
        }

        .plan-table tfoot {
            display: table-footer-group !important;
            height: auto !important;
        }

        .plan-table tbody > tr:not(.activity-detail-row) {
            display: table-row !important;
            height: 52px !important;
            min-height: 52px !important;
            max-height: 52px !important;
        }

        .plan-table tbody > tr.new-row {
            height: 58px !important;
            min-height: 58px !important;
            max-height: 58px !important;
        }

        .plan-table tbody > tr:not(.activity-detail-row) > td {
            height: 52px !important;
            min-height: 52px !important;
            max-height: 52px !important;
            padding-top: 7px !important;
            padding-bottom: 7px !important;
            vertical-align: middle !important;
        }

        .plan-table tbody > tr.new-row > td {
            height: 58px !important;
            min-height: 58px !important;
            max-height: 58px !important;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }

        .plan-table tbody > tr.activity-detail-row {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
        }

        .plan-table tbody > tr.activity-detail-row > td {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
        }


        /*
         * FINAL ROW-SIZE FIX ONLY:
         * Keep saved Work Plan rows compact after Add New is clicked.
         */
        .table-wrap {
            height: auto !important;
            min-height: 0 !important;
        }

        table.plan-table {
            height: 1px !important;
            min-height: 0 !important;
            max-height: none !important;
        }

        table.plan-table > thead,
        table.plan-table > tbody,
        table.plan-table > tfoot {
            height: auto !important;
            min-height: 0 !important;
        }

        table.plan-table > tbody > tr:not(.activity-detail-row) {
            height: 1px !important;
            min-height: 0 !important;
            max-height: none !important;
        }

        table.plan-table > tbody > tr:not(.activity-detail-row) > td {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            padding-top: 7px !important;
            padding-bottom: 7px !important;
            vertical-align: middle !important;
        }

        table.plan-table > tbody > tr.new-row > td {
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }

        table.plan-table > tbody > tr.activity-detail-row,
        table.plan-table > tbody > tr.activity-detail-row > td {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
        }

        table.plan-table > tfoot > tr,
        table.plan-table > tfoot > tr > td {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
        }


        /* View Activities modal stability and layout fix only */
        .view-activities-body {
            overflow-x: hidden;
        }

        .activity-view-scroll {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .activity-view-scroll .activity-view-table {
            min-width: 720px;
            border-radius: 0;
        }

        .activity-view-table th,
        .activity-view-table td {
            white-space: nowrap;
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
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="{{ __('pages.search_work_plan_placeholder') }}"
                >
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
                        <option value="{{ $task->id }}">
                            {{ $task->name }}
                        </option>
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

        <div style="margin-top: 14px;">
            <button type="button" wire:click="resetFilter" class="btn gray">
                {{ __('pages.reset_filter') }}
            </button>
        </div>
    </div>

    <div class="panel">
        <div class="list-header">
            <h2 class="panel-title" style="margin: 0;">
                {{ __('pages.work_plan_list') }}
            </h2>

            <div class="list-tools">
                <button
                    type="button"
                    wire:click="exportWorkPlansExcel"
                    class="export-btn"
                >
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

        @if($errors->any())
            <div class="inline-error">
                <div style="margin-bottom: 5px;">Please check the following fields:</div>

                @foreach($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="table-wrap">
            <table class="plan-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('pages.plan_date') }}</th>
                        <th>
                            {{ trans()->has('pages.task_group')
                                ? __('pages.task_group')
                                : 'Task Group' }}
                        </th>
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
                                $editZoneArea = $this->calculateZoneBlockArea(
                                    $editRow['zone_block_ids'] ?? []
                                );

                                $editFuelPerHa = (float) (
                                    $editRow['request_l_per_hectare'] ?? 0
                                );

                                $editRequestLiters = $this->calculateRequestLiters(
                                    $editZoneArea,
                                    $editFuelPerHa
                                );

                                $editTaskCategories = $taskCategories->filter(
                                    fn ($task) =>
                                        (string) $task->task_category_group_id ===
                                        (string) ($editRow['task_category_group_id'] ?? '')
                                );
                            @endphp

                            <tr class="new-row" wire:key="edit-work-plan-{{ $plan->id }}">
                                <td class="row-no">
                                    {{ ($this->plans->firstItem() ?? 1) + $loop->index }}
                                </td>

                                <td class="col-date">
                                    <input type="date" wire:model.live="editRow.plan_date">
                                </td>

                                <td class="col-title">
                                    <select wire:model.live="editRow.task_category_group_id">
                                        <option value="">Select Task Group</option>

                                        @foreach($taskCategoryGroups as $group)
                                            <option value="{{ $group->id }}">
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="col-activity">
                                    <button
                                        type="button"
                                        class="activity-select-btn"
                                        wire:click="addEditActivity"
                                    >
                                        <span>
                                            {{ count($editRow['activities'] ?? []) > 0
                                                ? count($editRow['activities']) . ' Activities'
                                                : 'Choose Activities' }}
                                        </span>

                                        <span class="activity-add-icon">+</span>
                                    </button>
                                </td>

                                <td class="col-date">
                                    <input type="date" wire:model.live="editRow.plan_start">
                                </td>

                                <td class="col-date">
                                    <input type="date" wire:model.live="editRow.plan_end">
                                </td>

                                <td class="col-zone">
                                    <button
                                        type="button"
                                        class="zone-select-btn"
                                        wire:click="openEditZonePicker"
                                    >
                                        <span>
                                            {{ $this->getZoneBlockSummary($editRow['zone_block_ids'] ?? []) }}
                                            <small>{{ number_format($editZoneArea, 2) }} Ha</small>
                                        </span>

                                        <span class="zone-select-count">
                                            {{ __('pages.choose') }}
                                        </span>
                                    </button>
                                </td>

                                <td class="col-number">
                                    <input
                                        type="text"
                                        class="readonly-calc"
                                        value="{{ number_format($editZoneArea, 2) }}"
                                        readonly
                                    >
                                </td>

                                <td class="col-number">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        wire:model.live="editRow.request_l_per_hectare"
                                        placeholder="0.00"
                                    >
                                </td>

                                <td class="col-number">
                                    <input
                                        type="text"
                                        class="readonly-calc"
                                        value="{{ number_format($editRequestLiters, 2) }}"
                                        readonly
                                    >
                                </td>

                                <td class="col-status">
                                    <select wire:model.live="editRow.status">
                                        <option value="in_progress">{{ __('pages.in_progress') }}</option>
                                        <option value="complete">{{ __('pages.complete') }}</option>
                                        <option value="cancelled">{{ __('pages.cancelled') }}</option>
                                    </select>
                                </td>

                                <td>-</td>

                                <td class="col-action">
                                    <div class="table-actions">
                                        <button
                                            type="button"
                                            wire:click="updateRow"
                                            class="mini"
                                        >
                                            {{ __('pages.save') }}
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="cancelEdit"
                                            class="mini danger"
                                        >
                                            {{ __('pages.cancel') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>

                           <tr class="activity-detail-row">
    <td colspan="13">
        <div class="activity-editor-card">
            <div class="activity-editor-header">
                <div>
                    <div class="activity-editor-title">
                        Activities for this Work Plan
                    </div>
                    <div class="activity-editor-subtitle">
                        Update activities and fuel per hectare.
                    </div>
                </div>

                <button
                    type="button"
                    class="activity-editor-add-btn"
                    wire:click="addEditActivity"
                >
                    + Add Activity
                </button>
            </div>

            <div class="activity-editor-body">
                @forelse($editRow['activities'] ?? [] as $activityIndex => $activity)
                    <div
                        class="activity-editor-item"
                        wire:key="edit-inline-activity-{{ $activityIndex }}"
                    >
                        <div class="activity-editor-no">
                            {{ $activityIndex + 1 }}
                        </div>

                        <div>
                            <label class="activity-editor-label">Activity</label>
                            <select
                                wire:model.live="editRow.activities.{{ $activityIndex }}.task_category_id"
                            >
                                <option value="">Select Activity</option>

                                @foreach($editTaskCategories as $task)
                                    <option value="{{ $task->id }}">
                                        {{ $task->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="activity-editor-label">Zone Area (Ha)</label>
                            <input
                                type="text"
                                value="{{ number_format($editZoneArea, 2) }}"
                                readonly
                            >
                        </div>

                        <div>
                            <label class="activity-editor-label">Fuel L/Ha</label>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                wire:model.live="editRow.activities.{{ $activityIndex }}.fuel_per_hectare"
                            >
                        </div>

                        <div>
                            <label class="activity-editor-label">Total Fuel (L)</label>
                            <input
                                type="text"
                                value="{{ number_format($this->calculateActivityTotalFuel($editZoneArea, $activity['fuel_per_hectare'] ?? 0), 2) }}"
                                readonly
                            >
                        </div>

                        <div class="activity-editor-remove-wrap">
                            <button
                                type="button"
                                class="activity-editor-remove-btn"
                                wire:click="removeEditActivity({{ $activityIndex }})"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="activity-editor-empty">
                        No activity added yet. Click
                        <strong>+ Add Activity</strong>.
                    </div>
                @endforelse
            </div>

            <div class="activity-editor-footer">
                <span>Zone Area: <strong>{{ number_format($editZoneArea, 2) }} Ha</strong></span>
                <span>Total Fuel/Ha: <strong>{{ number_format($editFuelPerHa, 2) }} L/Ha</strong></span>
                <span>Total Fuel: <strong>{{ number_format($editRequestLiters, 2) }} L</strong></span>
            </div>
        </div>
    </td>
</tr>
                        @else
                            <tr wire:key="work-plan-{{ $plan->id }}">
                                <td class="row-no">
                                    {{ ($this->plans->firstItem() ?? 1) + $loop->index }}
                                </td>

                                <td>
                                    {{ optional($plan->plan_date)->format('d M Y') ?: '-' }}
                                </td>

                                <td>
                                    {{
                                        $plan->activities
                                            ->first()
                                            ?->taskCategory
                                            ?->group
                                            ?->name
                                        ?? $plan->taskCategory
                                            ?->group
                                            ?->name
                                        ?? $plan->title
                                        ?? '-'
                                    }}
                                </td>

                                <td class="saved-activity-cell">
    @php
        $savedActivityNames = $plan->activities
            ->map(
                fn ($activity) =>
                    optional($activity->taskCategory)->name
            )
            ->filter()
            ->values();

        if (
            $savedActivityNames->isEmpty() &&
            $plan->taskCategory
        ) {
            $savedActivityNames = collect([
                $plan->taskCategory->name,
            ]);
        }
    @endphp

    @if($savedActivityNames->isNotEmpty())
        <span
            class="saved-activity-name"
            title="{{ $savedActivityNames->implode(', ') }}"
        >
            {{ $savedActivityNames->implode(', ') }}
        </span>
    @else
        -
    @endif
</td>

                                <td>
                                    {{ optional($plan->plan_start)->format('d M Y') ?: '-' }}
                                </td>

                                <td>
                                    {{ optional($plan->plan_end)->format('d M Y') ?: '-' }}
                                </td>

                                <td>
                                    {{ $this->getZoneBlockLabel($plan->zone_block_ids) }}
                                </td>

                                <td>
                                    {{ number_format((float) $plan->plan_area, 2) }}
                                </td>

                                <td>
                                    {{ number_format((float) $plan->request_l_per_hectare, 2) }}
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format((float) $plan->request_liters, 2) }}
                                    </strong>
                                </td>

                                <td>
                                    <span class="status-pill {{ $plan->status }}">
                                        {{ __('pages.' . $plan->status) }}
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        {{ number_format((int) $plan->work_logs_count) }}
                                    </strong>
                                </td>

                                <td>
                                    <div class="table-actions">
                                        @if(auth()->user()->hasPermission('work_plans.edit'))
                                            <button
                                                type="button"
                                                wire:click="edit({{ $plan->id }})"
                                                class="mini"
                                            >
                                                {{ __('pages.edit') }}
                                            </button>
                                        @endif

                                        @if(auth()->user()->hasPermission('work_plans.delete'))
                                            <button
                                                type="button"
                                                wire:click="delete({{ $plan->id }})"
                                                wire:confirm="{{ trans()->has('pages.confirm_delete_work_plan') ? __('pages.confirm_delete_work_plan') : 'Delete this work plan?' }}"
                                                class="mini danger"
                                            >
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
                                <td colspan="13" class="empty">
                                    {{ __('pages.no_work_plan_found') }}
                                </td>
                            </tr>
                        @endif
                    @endforelse

                    @foreach($rows as $index => $row)
                        @php
                            $rowZoneArea = $this->calculateZoneBlockArea(
                                $row['zone_block_ids'] ?? []
                            );

                            $rowFuelPerHa = (float) (
                                $row['request_l_per_hectare'] ?? 0
                            );

                            $rowRequestLiters = $this->calculateRequestLiters(
                                $rowZoneArea,
                                $rowFuelPerHa
                            );

                            $rowTaskCategories = $taskCategories->filter(
                                fn ($task) =>
                                    (string) $task->task_category_group_id ===
                                    (string) ($row['task_category_group_id'] ?? '')
                            );
                        @endphp

                        <tr
                            class="new-row"
                            wire:key="new-work-plan-{{ $index }}"
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

                            <td class="col-date">
                                <input
                                    type="date"
                                    wire:model.live="rows.{{ $index }}.plan_date"
                                >
                            </td>

                            <td class="col-title">
                                <select wire:model.live="rows.{{ $index }}.task_category_group_id">
                                    <option value="">Select Task Group</option>

                                    @foreach($taskCategoryGroups as $group)
                                        <option value="{{ $group->id }}">
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="col-activity">
                                <select
                                    wire:model.live="rows.{{ $index }}.activities.0.task_category_id"
                                    @disabled(empty($row['task_category_group_id']))
                                >
                                    <option value="">
                                        {{ empty($row['task_category_group_id'])
                                            ? 'Select Task Group First'
                                            : 'Select Activity' }}
                                    </option>

                                    @foreach($rowTaskCategories as $task)
                                        <option value="{{ $task->id }}">
                                            {{ $task->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="col-date">
                                <input
                                    type="date"
                                    wire:model.live="rows.{{ $index }}.plan_start"
                                >
                            </td>

                            <td class="col-date">
                                <input
                                    type="date"
                                    wire:model.live="rows.{{ $index }}.plan_end"
                                >
                            </td>

                            <td class="col-zone">
                                <button
                                    type="button"
                                    class="zone-select-btn"
                                    wire:click="openRowZonePicker({{ $index }})"
                                >
                                    <span>
                                        {{ $this->getZoneBlockSummary(
                                            $row['zone_block_ids'] ?? []
                                        ) }}

                                        <small>
                                            {{ number_format($rowZoneArea, 2) }} Ha
                                        </small>
                                    </span>

                                    <span class="zone-select-count">
                                        {{ __('pages.choose') }}
                                    </span>
                                </button>
                            </td>

                            <td class="col-number">
                                <input
                                    type="text"
                                    class="readonly-calc"
                                    value="{{ number_format($rowZoneArea, 2) }}"
                                    readonly
                                >
                            </td>

                            <td class="col-number">
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    wire:model.live="rows.{{ $index }}.request_l_per_hectare"
                                    placeholder="0.00"
                                >
                            </td>

                            <td class="col-number">
                                <input
                                    type="text"
                                    class="readonly-calc"
                                    value="{{ number_format($rowRequestLiters, 2) }}"
                                    readonly
                                >
                            </td>

                            <td class="col-status">
                                <select wire:model.live="rows.{{ $index }}.status">
                                    <option value="in_progress">
                                        {{ __('pages.in_progress') }}
                                    </option>

                                    <option value="complete">
                                        {{ __('pages.complete') }}
                                    </option>

                                    <option value="cancelled">
                                        {{ __('pages.cancelled') }}
                                    </option>
                                </select>
                            </td>

                            <td>-</td>

                            <td class="col-action">
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        wire:click="saveRow({{ $index }})"
                                        class="mini"
                                    >
                                        {{ __('pages.save') }}
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="removeRow({{ $index }})"
                                        class="mini danger"
                                    >
                                        {{ __('pages.remove') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="total-row">
                        <td>
                            @if(auth()->user()->hasPermission('work_plans.create'))
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

                        <td colspan="6" style="text-align: right;">
                            {{ __('pages.total_plans') }}:
                            {{ number_format((int) $this->totalPlans) }}
                        </td>

                        <td>
                            {{ number_format((float) $this->totalPlanArea, 2) }}
                        </td>

                        <td>
                            {{ number_format((float) $this->totalRequestLiterPerHectare, 2) }}
                        </td>

                        <td>
                            {{ number_format((float) $this->totalRequestLiters, 2) }}
                        </td>

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

    {{-- View Saved Activities Modal --}}
    @if($viewActivitiesOpen && is_array($viewActivitiesPlan))
        <div
            class="modal-backdrop"
            wire:key="view-activities-modal-{{ $viewActivitiesPlan['id'] }}"
            wire:click.self="closeViewActivities"
        >
            <div class="modal-card wide">
                <div class="modal-header">
                    <div>
                        <div class="modal-title">
                            View Activities
                        </div>

                        <div class="modal-subtitle">
                            {{ $viewActivitiesPlan['title'] }}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="modal-close-btn"
                        wire:click="closeViewActivities"
                    >
                        ×
                    </button>
                </div>

                <div class="modal-body view-activities-body">
                    <div class="activity-view-scroll">
                        <table class="activity-view-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Activity</th>
                                    <th>Zone Area (Ha)</th>
                                    <th>Fuel L/Ha</th>
                                    <th>Total Fuel (L)</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($viewActivitiesPlan['activities'] as $activity)
                                    <tr wire:key="view-activity-{{ $activity['id'] }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $activity['name'] }}</td>
                                        <td>
                                            {{ number_format($viewActivitiesPlan['plan_area'], 2) }}
                                        </td>
                                        <td>
                                            {{ number_format($activity['fuel_per_hectare'], 2) }}
                                        </td>
                                        <td>
                                            {{ number_format($activity['total_fuel'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty">
                                            No activities found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="activity-summary-grid">
                        <div class="activity-summary-card">
                            <span>Plan Area</span>
                            <strong>
                                {{ number_format($viewActivitiesPlan['plan_area'], 2) }}
                                Ha
                            </strong>
                        </div>

                        <div class="activity-summary-card">
                            <span>Total Fuel/Ha</span>
                            <strong>
                                {{ number_format($viewActivitiesPlan['request_l_per_hectare'], 2) }}
                                L/Ha
                            </strong>
                        </div>

                        <div class="activity-summary-card">
                            <span>Requested Fuel</span>
                            <strong>
                                {{ number_format($viewActivitiesPlan['request_liters'], 2) }}
                                L
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span></span>

                    <button
                        type="button"
                        class="btn"
                        wire:click="closeViewActivities"
                    >
                        {{ trans()->has('pages.done') ? __('pages.done') : 'Done' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Zone Block Picker Modal --}}
    @if($zonePickerOpen)
        @php
            $activeSelectedIds = [];

            if ($zonePickerMode === 'edit') {
                $activeSelectedIds = $editRow['zone_block_ids'] ?? [];
            }

            if (
                $zonePickerMode === 'create' &&
                $zonePickerIndex !== null &&
                isset($rows[$zonePickerIndex])
            ) {
                $activeSelectedIds =
                    $rows[$zonePickerIndex]['zone_block_ids'] ?? [];
            }
            $activeSelectedArea = $this->calculateZoneBlockArea($activeSelectedIds);
        @endphp

        <div
            class="modal-backdrop"
            wire:click.self="closeZonePicker"
        >
            <div class="modal-card wide">
                <div class="modal-header">
                    <div>
                        <div class="modal-title">
                            {{ __('pages.select_zone_blocks') }}
                        </div>

                        <div class="modal-subtitle">
                            {{ __('pages.select_zone_blocks_subtitle') }}
                        </div>
                    </div>

                    <div class="modal-header-actions">
                        <button
                            type="button"
                            class="btn gray"
                            wire:click="selectAllActiveZoneBlocks"
                        >
                            {{ __('pages.select_all') }}
                        </button>

                        <button
                            type="button"
                            class="btn gray"
                            wire:click="clearActiveZoneBlocks"
                        >
                            {{ __('pages.clear') }}
                        </button>

                        <button
                            type="button"
                            class="modal-close-btn"
                            wire:click="closeZonePicker"
                        >
                            ×
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="zone-table-selector">
                        @forelse($zoneBlockGroups as $zoneId => $blocks)
                            @php
                                $firstBlock = $blocks->first();
                                $zone = optional($firstBlock)->zone;
                                $zoneTitle = optional($zone)->zone_code ?: 'No Zone';
                                $zoneName = optional($zone)->name;
                                $zoneTotalArea = (float) $blocks->sum('area');
                            @endphp

                            <div class="zone-row-group">
                                <div class="zone-top">
                                    <div>
                                        <div class="zone-top-title">
                                            {{ $zoneTitle }}
                                        </div>

                                        <div class="zone-top-sub">
                                            {{ $zoneName ?: __('pages.zone') }}
                                        </div>
                                    </div>

                                    <div class="zone-top-summary">
                                        <div class="zone-top-count">
                                            {{ $blocks->count() }}
                                            {{ trans()->has('pages.zone_blocks') ? __('pages.zone_blocks') : 'Zone Blocks' }}
                                        </div>

                                        <div class="zone-top-count">
                                            Total Area: {{ number_format($zoneTotalArea, 2) }} Ha
                                        </div>
                                    </div>
                                </div>

                                <div class="subzone-bottom">
                                    @foreach($blocks as $block)
                                        <label class="subzone-option">
                                            @if($zonePickerMode === 'edit')
                                                <input
                                                    type="checkbox"
                                                    value="{{ $block->id }}"
                                                    wire:model.live="editRow.zone_block_ids"
                                                >
                                            @else
                                                <input
                                                    type="checkbox"
                                                    value="{{ $block->id }}"
                                                    wire:model.live="rows.{{ $zonePickerIndex }}.zone_block_ids"
                                                >
                                            @endif

                                            <span class="subzone-code">
                                                {{ $zoneTitle }}.{{ $block->block_code }}
                                            </span>

                                            <span class="subzone-name">
                                                {{ $block->name ?: __('pages.zone_block') }}
                                            </span>
                                            <span class="subzone-area">{{ number_format((float) $block->area, 2) }} Ha</span>
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

                <div class="modal-footer">
                    <div class="zone-footer-summary">
                        <span><strong>{{ count($activeSelectedIds) }}</strong> {{ __('pages.selected') }}</span>
                        <span>Total Area: <strong>{{ number_format($activeSelectedArea, 2) }} Ha</strong></span>
                    </div>

                    <div class="zone-selected-preview">
                        @forelse($this->getSelectedZoneBlockBadges($activeSelectedIds) as $badge)
                            <span class="zone-badge">
                                {{ $badge['label'] }}
                            </span>
                        @empty
                            <span class="zone-empty-preview">
                                {{ __('pages.no_selection') }}
                            </span>
                        @endforelse
                    </div>

                    <button
                        type="button"
                        class="btn"
                        wire:click="closeZonePicker"
                    >
                        {{ __('pages.done') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
