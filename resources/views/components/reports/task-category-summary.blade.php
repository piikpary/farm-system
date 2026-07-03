<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\FarmWorkPlan;
use App\Models\TaskCategory;
use App\Models\Zone;
use App\Models\ZoneBlock;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

new class extends Component
{
    public $from_date;
    public $to_date;
    public $zone_id;
    public $zone_block_id;
    public $activity_id;
    public $report_type = 'planning';

    protected $queryString = [
        'report_type' => ['except' => 'planning'],
    ];

    public function mount()
    {
        if (!in_array($this->report_type, ['planning', 'harvesting'], true)) {
            $this->report_type = 'planning';
        }
    }

    public function updatedReportType($value)
    {
        if (!in_array($value, ['planning', 'harvesting'], true)) {
            $this->report_type = 'planning';
        }

        $this->activity_id = null;
    }

    public function reportTypeLabel(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Harvesting'
            : 'Planting';
    }

    public function totalQtyHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Total Tons'
            : __('pages.total_area_hec');
    }

    public function finishQtyHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Finish Tons'
            : __('pages.finish_area_hec');
    }

    public function remainingQtyHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Remaining Tons'
            : __('pages.remaining_area_hec');
    }

    public function requestFuelRateHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Request Fuel (L/T)'
            : __('pages.request_fuel_l_hec');
    }

    public function consumedFuelRateHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Consumed Fuel (L/T)'
            : __('pages.consumed_fuel_l_hec');
    }

    public function remainingFuelRateHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Remaining Fuel (L/T)'
            : __('pages.remaining_fuel_l_hec');
    }

    public function varianceFuelRateHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Variance Fuel (L/T)'
            : __('pages.variance_fuel_l_hec');
    }

    public function workingEfficiencyHeader(): string
    {
        return $this->report_type === 'harvesting'
            ? 'Total Working Hour (T/Hr)'
            : __('pages.total_working_hour_hec_hr');
    }

    public function updatedZoneId()
    {
        $this->zone_block_id = null;
    }

    public function resetFilter()
    {
        $this->reset([
            'from_date',
            'to_date',
            'zone_id',
            'zone_block_id',
            'activity_id',
        ]);
    }

    private function selectedZoneBlockIds(): ?array
    {
        if ($this->zone_block_id) {
            return [(int) $this->zone_block_id];
        }

        if ($this->zone_id) {
            return ZoneBlock::query()
                ->where('zone_id', $this->zone_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return null;
    }

    private function applyPlanZoneBlockFilter($query, ?array $zoneBlockIds)
    {
        if ($zoneBlockIds === null) {
            return $query;
        }

        if (empty($zoneBlockIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($outerQuery) use ($zoneBlockIds) {
            foreach ($zoneBlockIds as $zoneBlockId) {
                $outerQuery->orWhere(function ($innerQuery) use ($zoneBlockId) {
                    $innerQuery
                        ->whereJsonContains(
                            'zone_block_ids',
                            (int) $zoneBlockId
                        )
                        ->orWhereJsonContains(
                            'zone_block_ids',
                            (string) $zoneBlockId
                        );
                });
            }
        });
    }

    private function firstNumber($model, array $fields): float
    {
        if (!$model) {
            return 0;
        }

        foreach ($fields as $field) {
            $value = null;

            if (is_array($model)) {
                $value = $model[$field] ?? null;
            } elseif (method_exists($model, 'getAttribute')) {
                $value = $model->getAttribute($field);
            } else {
                $value = $model->{$field} ?? null;
            }

            if ($value !== null && $value !== '') {
                $number = (float) $value;

                if ($number != 0.0) {
                    return $number;
                }
            }
        }

        return 0;
    }

    private function planQtyValue($plan): float
    {
        return $this->firstNumber(
            $plan,
            [
                'plan_area',
                'plan_tons',
                'planned_tons',
                'total_tons',
                'total_area',
                'area',
            ]
        );
    }

    private function planRequestFuelValue($plan): float
    {
        return $this->firstNumber(
            $plan,
            [
                'request_l',
                'request_liters',
                'request_liter',
                'request_litre',
                'request_fuel',
                'request_fuel_l',
                'request_fuel_liters',
                'requested_fuel',
                'requested_liters',
            ]
        );
    }

    private function planFuelRateForWorkPlan($workPlan, int $taskCategoryId): float
    {
        if (!$workPlan || !$taskCategoryId) {
            return 0;
        }

        /*
         * Use the rate shown on the Work Plan row first.
         * This fixes grouped report rows where the same task category
         * has multiple work plans with different Request L/HA.
         */
        $planRate = $this->firstNumber(
            $workPlan,
            [
                'request_l_per_ha',
                'request_l_per_hectare',
                'request_liters_per_ha',
                'request_liters_per_hectare',
                'request_litre_per_ha',
                'request_litre_per_hectare',
                'request_l_ha',
                'request_l_per_t',
                'request_l_per_ton',
                'request_liters_per_t',
                'request_liters_per_ton',
                'request_litre_per_t',
                'request_litre_per_ton',
                'fuel_per_ha',
                'fuel_per_hectare',
                'fuel_per_t',
                'fuel_per_ton',
                'request_fuel_rate',
            ]
        );

        if ($planRate > 0) {
            return $planRate;
        }

        if ($workPlan->activities && $workPlan->activities->isNotEmpty()) {
            $activity = $workPlan->activities->first(
                fn ($activity) =>
                    (int) $activity->task_category_id === $taskCategoryId
            );

            if ($activity) {
                $activityRate = $this->firstNumber(
                    $activity,
                    [
                        'fuel_per_hectare',
                        'fuel_per_ha',
                        'request_l_per_ha',
                        'request_l_per_hectare',
                        'request_liters_per_ha',
                        'request_liters_per_hectare',
                        'request_litre_per_ha',
                        'request_litre_per_hectare',
                        'request_l_ha',
                        'request_l_per_t',
                        'request_l_per_ton',
                        'request_liters_per_t',
                        'request_liters_per_ton',
                        'request_litre_per_t',
                        'request_litre_per_ton',
                        'fuel_per_ton',
                    ]
                );

                if ($activityRate > 0) {
                    return $activityRate;
                }
            }
        }

        $planQty = $this->planQtyValue($workPlan);
        $requestFuel = $this->planRequestFuelValue($workPlan);

        if ($planQty > 0) {
            return $requestFuel / $planQty;
        }

        return 0;
    }

    private function buildRows()
    {
        /*
         * Formula concept used by both the page and exports:
         *
         * Total Area              = Sum of plan area for this activity
         * Finish Area             = Sum of working area from work logs
         * Remaining Area          = MAX(Total Area - Finish Area, 0)
         *
         * Request Fuel L/Ha       = Request Fuel / Total Area
         * Request Fuel            = Sum(Plan Area Ã— Activity Fuel L/Ha)
         * Plan Use Fuel           = Sum(each Work Log area Ã— that Work Plan L/Ha)
         *
         * Consumed Fuel L/Ha      = Consumed Fuel / Finish Area
         * Remaining Fuel          = Request Fuel - Consumed Fuel
         * Remaining Fuel L/Ha     = Remaining Fuel / Remaining Area
         *
         * Variance Fuel           = Plan Use Fuel - Consumed Fuel
         * Variance Fuel L/Ha      = Plan Use Fuel L/Ha - Consumed Fuel L/Ha
         *
         * Hectare/Hour            = Finish Area / Total Working Hour
         */

        $summary = collect();
        $selectedZoneBlockIds = $this->selectedZoneBlockIds();

        /*
         * New Work Plan concept:
         * one plan can contain multiple activities.
         *
         * Each activity receives the plan's full planned area and its own
         * fuel-per-hectare value.
         */
        $plansQuery = FarmWorkPlan::with([
            'taskCategory.group',
            'activities.taskCategory.group',
        ])
            ->when(
                $this->from_date,
                fn ($q) => $q->whereDate(
                    'plan_date',
                    '>=',
                    $this->from_date
                )
            )
            ->when(
                $this->to_date,
                fn ($q) => $q->whereDate(
                    'plan_date',
                    '<=',
                    $this->to_date
                )
            )
            ->when($this->activity_id, function ($q) {
                $activityId = (int) $this->activity_id;

                $q->where(function ($activityQuery) use ($activityId) {
                    $activityQuery
                        ->where('task_category_id', $activityId)
                        ->orWhereHas(
                            'activities',
                            function ($relationQuery) use ($activityId) {
                                $relationQuery->where(
                                    'task_category_id',
                                    $activityId
                                );
                            }
                        );
                });
            })
            ->where(function ($typeQuery) {
                $typeQuery
                    ->whereHas(
                        'activities.taskCategory.group',
                        fn ($groupQuery) => $groupQuery->where(
                            'group_type',
                            $this->report_type
                        )
                    )
                    ->orWhereHas(
                        'taskCategory.group',
                        fn ($groupQuery) => $groupQuery->where(
                            'group_type',
                            $this->report_type
                        )
                    );
            });

        $this->applyPlanZoneBlockFilter(
            $plansQuery,
            $selectedZoneBlockIds
        );

        $plans = $plansQuery->get();

        foreach ($plans as $plan) {
            $planArea = $this->planQtyValue($plan);

            if ($plan->activities->isNotEmpty()) {
                foreach ($plan->activities as $activity) {
                    $taskCategoryId =
                        (int) $activity->task_category_id;

                    if (!$taskCategoryId) {
                        continue;
                    }

                    if (
                        ($activity->taskCategory?->group?->group_type ?? 'planning')
                        !== $this->report_type
                    ) {
                        continue;
                    }

                    if (
                        $this->activity_id &&
                        $taskCategoryId !== (int) $this->activity_id
                    ) {
                        continue;
                    }

                    $fuelPerHectare = $this->planFuelRateForWorkPlan(
                        $plan,
                        $taskCategoryId
                    );

                    $requestFuel =
                        $planArea * $fuelPerHectare;

                    $current = $summary->get(
                        $taskCategoryId,
                        [
                            'task_category_id' => $taskCategoryId,
                            'task_category' =>
                                optional(
                                    $activity->taskCategory
                                )->name ?? '-',
                            'total_area' => 0,
                            'request_fuel' => 0,
                            'finish_area' => 0,
                            'consumed_fuel' => 0,
                            'plan_use_fuel' => 0,
                            'total_working_hour' => 0,
                        ]
                    );

                    $current['total_area'] += $planArea;
                    $current['request_fuel'] += $requestFuel;

                    $summary->put($taskCategoryId, $current);
                }

                continue;
            }

            /*
             * Compatibility for old work plans created before the
             * farm_work_plan_activities table was introduced.
             */
            if ($plan->task_category_id) {
                $taskCategoryId =
                    (int) $plan->task_category_id;

                if (
                    ($plan->taskCategory?->group?->group_type ?? 'planning')
                    !== $this->report_type
                ) {
                    continue;
                }

                if (
                    $this->activity_id &&
                    $taskCategoryId !== (int) $this->activity_id
                ) {
                    continue;
                }

                $requestFuel = $this->planRequestFuelValue($plan);

                if ($requestFuel <= 0) {
                    $requestFuel =
                        $planArea *
                        $this->planFuelRateForWorkPlan(
                            $plan,
                            $taskCategoryId
                        );
                }

                $current = $summary->get(
                    $taskCategoryId,
                    [
                        'task_category_id' => $taskCategoryId,
                        'task_category' =>
                            optional(
                                $plan->taskCategory
                            )->name ?? '-',
                        'total_area' => 0,
                        'request_fuel' => 0,
                        'finish_area' => 0,
                        'consumed_fuel' => 0,
                        'plan_use_fuel' => 0,
                        'total_working_hour' => 0,
                    ]
                );

                $current['total_area'] += $planArea;
                $current['request_fuel'] += $requestFuel;

                $summary->put($taskCategoryId, $current);
            }
        }

        $logs = FarmWorkLog::with('taskCategory.group')
            ->when(
                $this->from_date,
                fn ($q) => $q->whereDate(
                    'work_date',
                    '>=',
                    $this->from_date
                )
            )
            ->when(
                $this->to_date,
                fn ($q) => $q->whereDate(
                    'work_date',
                    '<=',
                    $this->to_date
                )
            )
            ->when(
                $selectedZoneBlockIds !== null,
                function ($q) use ($selectedZoneBlockIds) {
                    if (empty($selectedZoneBlockIds)) {
                        $q->whereRaw('1 = 0');

                        return;
                    }

                    $q->whereIn(
                        'zone_block_id',
                        $selectedZoneBlockIds
                    );
                }
            )
            ->when(
                $this->activity_id,
                fn ($q) => $q->where(
                    'task_category_id',
                    (int) $this->activity_id
                )
            )
            ->whereHas(
                'taskCategory.group',
                fn ($groupQuery) => $groupQuery->where(
                    'group_type',
                    $this->report_type
                )
            )
            ->get();

        $plansForLogs = FarmWorkPlan::with([
            'activities',
            'taskCategory.group',
        ])
            ->whereIn(
                'id',
                $logs
                    ->pluck('farm_work_plan_id')
                    ->filter()
                    ->unique()
                    ->values()
            )
            ->get()
            ->keyBy('id');

        foreach ($logs as $log) {
            $taskCategoryId =
                (int) $log->task_category_id;

            if (!$taskCategoryId) {
                continue;
            }

            $current = $summary->get(
                $taskCategoryId,
                [
                    'task_category_id' => $taskCategoryId,
                    'task_category' =>
                        optional($log->taskCategory)->name ?? '-',
                    'total_area' => 0,
                    'request_fuel' => 0,
                    'finish_area' => 0,
                    'consumed_fuel' => 0,
                    'plan_use_fuel' => 0,
                    'total_working_hour' => 0,
                ]
            );

            if (
                ($current['task_category'] ?? '-') === '-' &&
                optional($log->taskCategory)->name
            ) {
                $current['task_category'] =
                    $log->taskCategory->name;
            }

            $workingArea =
                (float) $log->working_area;

            $current['finish_area'] +=
                $workingArea;

            $workPlan = $plansForLogs->get(
                (int) $log->farm_work_plan_id
            );

            $planFuelRate = $this->planFuelRateForWorkPlan(
                $workPlan,
                $taskCategoryId
            );

            if ($planFuelRate <= 0 && $workingArea > 0) {
                $planFuelRate =
                    (float) $log->diesel_consumed / $workingArea;
            }

            $current['plan_use_fuel'] +=
                $workingArea * $planFuelRate;

            $current['consumed_fuel'] +=
                (float) $log->diesel_consumed;

            $current['total_working_hour'] +=
                (float) $log->working_duration;

            $summary->put($taskCategoryId, $current);
        }

        return $summary
            ->map(function ($item) {
                $totalArea =
                    (float) $item['total_area'];

                $finishArea =
                    (float) $item['finish_area'];

                $requestFuel =
                    (float) $item['request_fuel'];

                $consumedFuel =
                    (float) $item['consumed_fuel'];

                $planUseFuel =
                    (float) ($item['plan_use_fuel'] ?? 0);

                $totalWorkingHour =
                    (float) $item['total_working_hour'];

                $remainingArea = max(
                    $totalArea - $finishArea,
                    0
                );

                // Weighted request rate. Never sum L/Ha rates.
                $requestFuelPerHectare = $totalArea > 0
                    ? $requestFuel / $totalArea
                    : 0;

                // Plan use fuel must follow each Work Log's own Work Plan rate.
                // Do not use Finish Area × average rate, because the same task
                // can have many work plans with different L/Ha or L/T.
                $planUseFuelPerHectare = $finishArea > 0
                    ? $planUseFuel / $finishArea
                    : 0;

                $consumedFuelPerHectare =
                    $finishArea > 0
                        ? $consumedFuel / $finishArea
                        : 0;

                $remainingFuel =
                    $requestFuel - $consumedFuel;

                $remainingFuelPerHectare =
                    $remainingArea > 0
                        ? $remainingFuel / $remainingArea
                        : 0;

                // Positive = used less than the planned fuel for finished area.
                // Negative = over-consumed.
                $varianceFuel =
                    $planUseFuel - $consumedFuel;

                $varianceFuelPerHectare =
                    $planUseFuelPerHectare -
                    $consumedFuelPerHectare;

                $hectarePerHour =
                    $totalWorkingHour > 0
                        ? $finishArea / $totalWorkingHour
                        : 0;

                return [
                    'task_category_id' =>
                        $item['task_category_id'],

                    'task_category' =>
                        $item['task_category'],

                    'total_area' =>
                        round($totalArea, 2),

                    'finish_area' =>
                        round($finishArea, 2),

                    'remaining_area' =>
                        round($remainingArea, 2),

                    'request_fuel_per_hectare' =>
                        round(
                            $requestFuelPerHectare,
                            2
                        ),

                    'request_fuel' =>
                        round($requestFuel, 2),

                    'plan_use_fuel' =>
                        round($planUseFuel, 2),

                    'consumed_fuel' =>
                        round($consumedFuel, 2),

                    'consumed_fuel_per_hectare' =>
                        round(
                            $consumedFuelPerHectare,
                            2
                        ),

                    'remaining_fuel' =>
                        round($remainingFuel, 2),

                    'remaining_fuel_per_hectare' =>
                        round(
                            $remainingFuelPerHectare,
                            2
                        ),

                    'variance_fuel' =>
                        round($varianceFuel, 2),

                    'variance_fuel_per_hectare' =>
                        round(
                            $varianceFuelPerHectare,
                            2
                        ),

                    'total_working_hour' =>
                        round($totalWorkingHour, 2),

                    'hectare_per_hour' =>
                        round($hectarePerHour, 2),
                ];
            })
            ->sortBy('task_category')
            ->values();
    }

    public function exportExcel()
    {
        $rows = $this->buildRows();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Task Summary');

        $headers = [
            'A1' => __('pages.no'),
            'B1' => __('pages.task_category'),
            'C1' => $this->totalQtyHeader(),
            'D1' => $this->finishQtyHeader(),
            'E1' => $this->remainingQtyHeader(),
            'F1' => $this->requestFuelRateHeader(),
            'G1' => __('pages.request_fuel_l'),
            'H1' => trans()->has('pages.plan_use_fuel_l')
                ? __('pages.plan_use_fuel_l')
                : 'Plan Use Fuel (L)',
            'I1' => __('pages.consumed_fuel_l'),
            'J1' => $this->consumedFuelRateHeader(),
            'K1' => __('pages.remaining_fuel_l'),
            'L1' => $this->remainingFuelRateHeader(),
            'M1' => __('pages.variance_fuel_l'),
            'N1' => $this->varianceFuelRateHeader(),
            'O1' => __('pages.total_working_hour_hr'),
            'P1' => $this->workingEfficiencyHeader(),
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $rowNumber = 2;

        foreach ($rows as $index => $row) {
            $sheet->setCellValue(
                'A' . $rowNumber,
                $index + 1
            );

            $sheet->setCellValue(
                'B' . $rowNumber,
                $row['task_category']
            );

            // Raw values from database aggregation.
            $sheet->setCellValue(
                'C' . $rowNumber,
                (float) $row['total_area']
            );

            $sheet->setCellValue(
                'D' . $rowNumber,
                (float) $row['finish_area']
            );

            // E: Remaining Area = MAX(Total Area - Finish Area, 0)
            $sheet->setCellValue(
                'E' . $rowNumber,
                '=MAX(C' .
                $rowNumber .
                '-D' .
                $rowNumber .
                ',0)'
            );

            /*
             * F: Request Fuel L/Ha = Request Fuel / Total Area
             * G: Request Fuel is the aggregated planned fuel.
             */
            $sheet->setCellValue(
                'F' . $rowNumber,
                '=IF(C' .
                $rowNumber .
                '>0,G' .
                $rowNumber .
                '/C' .
                $rowNumber .
                ',0)'
            );

            $sheet->setCellValue(
                'G' . $rowNumber,
                (float) $row['request_fuel']
            );

            // H: Planned fuel for the finished area.
            // This value is calculated from each work log's own work plan rate.
            $sheet->setCellValue(
                'H' . $rowNumber,
                (float) $row['plan_use_fuel']
            );

            // I: Actual consumed fuel from Work Logs.
            $sheet->setCellValue(
                'I' . $rowNumber,
                (float) $row['consumed_fuel']
            );

            // J: Actual consumed fuel rate.
            $sheet->setCellValue(
                'J' . $rowNumber,
                '=IF(D' .
                $rowNumber .
                '>0,I' .
                $rowNumber .
                '/D' .
                $rowNumber .
                ',0)'
            );

            // K: Remaining Fuel = Request Fuel - Consumed Fuel.
            $sheet->setCellValue(
                'K' . $rowNumber,
                '=G' .
                $rowNumber .
                '-I' .
                $rowNumber
            );

            // L: Remaining Fuel per remaining hectare.
            $sheet->setCellValue(
                'L' . $rowNumber,
                '=IF(E' .
                $rowNumber .
                '>0,K' .
                $rowNumber .
                '/E' .
                $rowNumber .
                ',0)'
            );

            // M: Variance = Planned use for finished area - Actual use.
            $sheet->setCellValue(
                'M' . $rowNumber,
                '=H' .
                $rowNumber .
                '-I' .
                $rowNumber
            );

            // N: Rate variance = Plan Use Fuel rate - Actual rate.
            $sheet->setCellValue(
                'N' . $rowNumber,
                '=IF(D' .
                $rowNumber .
                '>0,H' .
                $rowNumber .
                '/D' .
                $rowNumber .
                ',0)-J' .
                $rowNumber
            );

            // O: Total working hour.
            $sheet->setCellValue(
                'O' . $rowNumber,
                (float) $row['total_working_hour']
            );

            // P: Hectare per hour.
            $sheet->setCellValue(
                'P' . $rowNumber,
                '=IF(O' .
                $rowNumber .
                '>0,D' .
                $rowNumber .
                '/O' .
                $rowNumber .
                ',0)'
            );

            $rowNumber++;
        }

        $lastDataRow = $rowNumber - 1;

        if ($lastDataRow >= 2) {
            $sheet->setCellValue(
                'B' . $rowNumber,
                'Total'
            );

            $sheet->setCellValue(
                'C' . $rowNumber,
                '=SUM(C2:C' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'D' . $rowNumber,
                '=SUM(D2:D' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'E' . $rowNumber,
                '=MAX(C' .
                $rowNumber .
                '-D' .
                $rowNumber .
                ',0)'
            );

            // Weighted total request rate, not SUM(F).
            $sheet->setCellValue(
                'F' . $rowNumber,
                '=IF(C' .
                $rowNumber .
                '>0,G' .
                $rowNumber .
                '/C' .
                $rowNumber .
                ',0)'
            );

            $sheet->setCellValue(
                'G' . $rowNumber,
                '=SUM(G2:G' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'H' . $rowNumber,
                '=SUM(H2:H' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'I' . $rowNumber,
                '=SUM(I2:I' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'J' . $rowNumber,
                '=IF(D' .
                $rowNumber .
                '>0,I' .
                $rowNumber .
                '/D' .
                $rowNumber .
                ',0)'
            );

            $sheet->setCellValue(
                'K' . $rowNumber,
                '=G' .
                $rowNumber .
                '-I' .
                $rowNumber
            );

            $sheet->setCellValue(
                'L' . $rowNumber,
                '=IF(E' .
                $rowNumber .
                '>0,K' .
                $rowNumber .
                '/E' .
                $rowNumber .
                ',0)'
            );

            $sheet->setCellValue(
                'M' . $rowNumber,
                '=H' .
                $rowNumber .
                '-I' .
                $rowNumber
            );

            $sheet->setCellValue(
                'N' . $rowNumber,
                '=IF(D' .
                $rowNumber .
                '>0,H' .
                $rowNumber .
                '/D' .
                $rowNumber .
                ',0)-J' .
                $rowNumber
            );

            $sheet->setCellValue(
                'O' . $rowNumber,
                '=SUM(O2:O' . $lastDataRow . ')'
            );

            $sheet->setCellValue(
                'P' . $rowNumber,
                '=IF(O' .
                $rowNumber .
                '>0,D' .
                $rowNumber .
                '/O' .
                $rowNumber .
                ',0)'
            );
        }

        $highestRow = $sheet->getHighestRow();

        $sheet
            ->getStyle('A1:P1')
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                'A' .
                $highestRow .
                ':P' .
                $highestRow
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle('A1:P1')
            ->getFill()
            ->setFillType(
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFDCFCE7');

        $sheet
            ->getStyle(
                'A' .
                $highestRow .
                ':P' .
                $highestRow
            )
            ->getFill()
            ->setFillType(
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFF0FDF4');

        $sheet
            ->getStyle('A1:P' . $highestRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            )
            ->getColor()
            ->setARGB('FFE5E7EB');

        $sheet
            ->getStyle('C2:P' . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range('A', 'P') as $column) {
            $sheet
                ->getColumnDimension($column)
                ->setAutoSize(true);
        }

        $sheet->freezePane('C2');
        $sheet->setAutoFilter('A1:P1');

        $filename =
            'task-category-summary-' .
            now()->format('Y-m-d-His') .
            '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);

                /*
                 * Keep formulas inside the downloaded workbook.
                 * Excel/LibreOffice recalculates them when the file opens.
                 */
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

    public function exportCsv()
    {
        $rows = $this->buildRows();

        $filename =
            'task-category-summary-' .
            now()->format('Y-m-d-His') .
            '.csv';

        return response()->streamDownload(
            function () use ($rows) {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                // UTF-8 BOM for Excel.
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    [
                        'No',
                        'Task Category',
                        $this->totalQtyHeader(),
                        $this->finishQtyHeader(),
                        $this->remainingQtyHeader(),
                        $this->requestFuelRateHeader(),
                        'Request Fuel (L)',
                        'Plan Use Fuel (L)',
                        'Consumed Fuel (L)',
                        $this->consumedFuelRateHeader(),
                        'Remaining Fuel (L)',
                        $this->remainingFuelRateHeader(),
                        'Variance Fuel (L)',
                        $this->varianceFuelRateHeader(),
                        'Total Working Hour (Hr)',
                        $this->workingEfficiencyHeader(),
                    ]
                );

                foreach ($rows as $index => $row) {
                    fputcsv(
                        $handle,
                        [
                            $index + 1,
                            $row['task_category'],
                            $row['total_area'],
                            $row['finish_area'],
                            $row['remaining_area'],
                            $row['request_fuel_per_hectare'],
                            $row['request_fuel'],
                            $row['plan_use_fuel'],
                            $row['consumed_fuel'],
                            $row['consumed_fuel_per_hectare'],
                            $row['remaining_fuel'],
                            $row['remaining_fuel_per_hectare'],
                            $row['variance_fuel'],
                            $row['variance_fuel_per_hectare'],
                            $row['total_working_hour'],
                            $row['hectare_per_hour'],
                        ]
                    );
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

   public function with()
{
    return [
        'rows' => $this->buildRows(),

        'zones' => Zone::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]),

        'zoneBlocks' => $this->zone_id
            ? ZoneBlock::query()
                ->where('zone_id', $this->zone_id)
                ->orderBy('name')
                ->get([
                    'id',
                    'zone_id',
                    'name',
                ])
            : collect(),

        'activities' => TaskCategory::query()
            ->whereHas('group', function ($query) {
                $query->where('group_type', $this->report_type);
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]),
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

        .form-grid input[type="date"],
        .form-grid select {
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
        .form-grid select.zone-block-select {
    color: #0f172a !important;
    background-color: #ffffff !important;
    font-weight: 800;
    color-scheme: light;
}
.form-grid select.zone-block-select option {
    color: #0f172a;
    background-color: #ffffff;
    font-weight: 800;
}

.form-grid select.zone-block-select:disabled {
    color: #94a3b8 !important;
    background-color: #f1f5f9 !important;
    border-color: #e2e8f0;
    cursor: not-allowed;
}

        .form-grid input[type="date"]:focus,
        .form-grid select:focus {
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
            min-width: 2050px;
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
            min-width: 70px;
            text-align: center;
        }

        .table-wrap th:first-child {
            background: #f8fafc;
            z-index: 5;
        }

        /* Fix lost text issue: do not make Task Category sticky */
        .table-wrap th:nth-child(2),
        .table-wrap td:nth-child(2) {
            position: static;
            min-width: 220px;
            background: #ffffff;
            box-shadow: none;
        }

        .table-wrap th:nth-child(2) {
            background: #f8fafc;
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
        .form-grid select:disabled {
    background: #f1f5f9;
    color: #94a3b8;
    border-color: #e2e8f0;
    cursor: not-allowed;
    box-shadow: none;
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
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
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
                min-width: 1700px;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1 class="page-title">
                {{ __('pages.task_category_summary_report') }}
            </h1>

            <p class="page-subtitle">
                {{ __('pages.task_category_summary_report_subtitle') }}
            </p>
        </div>

        <div class="page-actions">
            <a
                href="{{ route('dashboard') }}"
                class="btn gray"
            >
                {{ __('pages.dashboard_button') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <div
            class="form-grid"
            style="align-items: end;"
        >
            <div>
                <label>
                    Report Type
                </label>

                <select wire:model.live="report_type">
                    <option value="planning">
                        Planting
                    </option>

                    <option value="harvesting">
                        Harvesting
                    </option>
                </select>
            </div>

            <div>
                <label>
                    {{ __('pages.from_date') }}
                </label>

                <input
                    type="date"
                    wire:model.live="from_date"
                >
            </div>

            <div>
                <label>
                    {{ __('pages.to_date') }}
                </label>

                <input
                    type="date"
                    wire:model.live="to_date"
                >
            </div>

            <div>
                <label>
                    {{ trans()->has('pages.zone')
                        ? __('pages.zone')
                        : 'Zone' }}
                </label>

                <select wire:model.live="zone_id">
                    <option value="">
                        {{ trans()->has('pages.all_zones')
                            ? __('pages.all_zones')
                            : 'All Zones' }}
                    </option>

                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">
                            {{ filled(trim((string) $zone->zone_code))
                                ? trim($zone->zone_code)
                                : ($zone->name ?: 'Zone #' . $zone->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
    <label>
        {{ trans()->has('pages.zone_block')
            ? __('pages.zone_block')
            : 'Zone Block' }}
    </label>

    <select
    class="zone-block-select"
    wire:model.live="zone_block_id"
    wire:key="zone-block-select-{{ $zone_id ?: 'none' }}"
    @disabled(!$zone_id)
>
    @if(!$zone_id)
        <option value="">
            Select Zone First
        </option>
    @else
        <option value="">
            {{ trans()->has('pages.all_zone_blocks')
                ? __('pages.all_zone_blocks')
                : 'All Zone Blocks' }}
        </option>

        @foreach($zoneBlocks as $zoneBlock)
            <option value="{{ $zoneBlock->id }}">
                {{ filled(trim((string) $zoneBlock->name))
                    ? $zoneBlock->name
                    : 'Zone Block #' . $zoneBlock->id }}
            </option>
        @endforeach
    @endif
</select>
</div>

            <div>
                <label>
                    {{ trans()->has('pages.activity')
                        ? __('pages.activity')
                        : 'Activity' }}
                </label>

                <select wire:model.live="activity_id">
                    <option value="">
                        {{ trans()->has('pages.all_activities')
                            ? __('pages.all_activities')
                            : 'All Activities' }}
                    </option>

                    @foreach($activities as $activity)
                        <option value="{{ $activity->id }}">
                            {{ $activity->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button
                    type="button"
                    wire:click="$refresh"
                    class="btn"
                >
                    {{ __('pages.filter') }}
                </button>
            </div>

            <div>
                <button
                    type="button"
                    wire:click="resetFilter"
                    class="btn light"
                >
                    {{ __('pages.reset') }}
                </button>
            </div>

            <div>
                <button
                    type="button"
                    wire:click="exportExcel"
                    class="btn"
                >
                    {{ __('pages.export_excel') }}
                </button>
            </div>

            <div>
                <button
                    type="button"
                    wire:click="exportCsv"
                    class="btn gray"
                >
                    {{ __('pages.export_csv') }}
                </button>
            </div>
        </div>

        <div
            class="table-wrap"
            style="margin-top: 18px;"
        >
            <table>
                <thead>
                    <tr>
                        <th>
                            {{ __('pages.no') }}
                        </th>

                        <th>
                            {{ __('pages.task_category') }}
                        </th>

                        <th>
                            {{ $this->totalQtyHeader() }}
                        </th>

                        <th>
                            {{ $this->finishQtyHeader() }}
                        </th>

                        <th>
                            {{ $this->remainingQtyHeader() }}
                        </th>

                        <th>
                            {{ $this->requestFuelRateHeader() }}
                        </th>

                        <th>
                            {{ __('pages.request_fuel_l') }}
                        </th>

                        <th>
                            {{ trans()->has('pages.plan_use_fuel_l')
                                ? __('pages.plan_use_fuel_l')
                                : 'Plan Use Fuel (L)' }}
                        </th>

                        <th>
                            {{ __('pages.consumed_fuel_l') }}
                        </th>

                        <th>
                            {{ $this->consumedFuelRateHeader() }}
                        </th>

                        <th>
                            {{ __('pages.remaining_fuel_l') }}
                        </th>

                        <th>
                            {{ $this->remainingFuelRateHeader() }}
                        </th>

                        <th>
                            {{ __('pages.variance_fuel_l') }}
                        </th>

                        <th>
                            {{ $this->varianceFuelRateHeader() }}
                        </th>

                        <th>
                            {{ __('pages.total_working_hour_hr') }}
                        </th>

                        <th>
                            {{ $this->workingEfficiencyHeader() }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td>
                                {{ $index + 1 }}
                            </td>

                            <td>
                                {{ $row['task_category'] }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['total_area'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['finish_area'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['remaining_area'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['request_fuel_per_hectare'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['request_fuel'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['plan_use_fuel'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['consumed_fuel'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['consumed_fuel_per_hectare'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['remaining_fuel'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['remaining_fuel_per_hectare'],
                                    2
                                ) }}
                            </td>

                            <td>
                                <strong
                                    style="color: {{ $row['variance_fuel'] < 0 ? '#dc2626' : '#166534' }}"
                                >
                                    {{ number_format(
                                        $row['variance_fuel'],
                                        2
                                    ) }}
                                </strong>
                            </td>

                            <td>
                                <strong
                                    style="color: {{ $row['variance_fuel_per_hectare'] < 0 ? '#dc2626' : '#166534' }}"
                                >
                                    {{ number_format(
                                        $row['variance_fuel_per_hectare'],
                                        2
                                    ) }}
                                </strong>
                            </td>

                            <td>
                                {{ number_format(
                                    $row['total_working_hour'],
                                    2
                                ) }}
                            </td>

                            <td>
                                {{ number_format(
                                    $row['hectare_per_hour'],
                                    2
                                ) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="16"
                                class="empty"
                            >
                                {{ __('pages.no_report_data_found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>