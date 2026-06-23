<?php

use Livewire\Component;
use App\Models\FarmWorkLog;
use App\Models\FarmWorkPlan;
use App\Models\Tractor;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\ZoneBlock;
use App\Models\TaskCategory;
use Carbon\Carbon;

new class extends Component
{
    public string $dashboardDate = '';

    public string $selectedZone = 'all';

    public string $dashboardType = 'planning';

    protected $queryString = [
        'dashboardType' => ['except' => 'planning'],
    ];

    public function mount(): void
    {
        if (!in_array($this->dashboardType, ['planning', 'harvesting'], true)) {
            $this->dashboardType = 'planning';
        }

        $latestWorkDate = FarmWorkLog::query()
            ->whereNotNull('work_date')
            ->max('work_date');

        $this->dashboardDate = $latestWorkDate
            ? Carbon::parse($latestWorkDate)->format('Y-m-d')
            : now()->format('Y-m-d');
    }

    public function updatedDashboardDate($value): void
    {
        try {
            $this->dashboardDate = Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            $this->dashboardDate = now()->format('Y-m-d');
        }
    }

    public function updatedSelectedZone($value): void
    {
        if ($value === 'all') {
            return;
        }

        if (!Zone::query()->whereKey($value)->exists()) {
            $this->selectedZone = 'all';
        }
    }

    public function updatedDashboardType($value): void
    {
        if (!in_array($value, ['planning', 'harvesting'], true)) {
            $this->dashboardType = 'planning';
        }
    }

    public function dashboardTypeLabel(): string
    {
        return $this->dashboardType === 'harvesting'
            ? 'Harvesting'
            : 'Planning';
    }

    public function dashboardQtyUnitLabel(): string
    {
        return $this->dashboardType === 'harvesting'
            ? 'T'
            : 'ha';
    }

    public function dashboardFuelRateLabel(): string
    {
        return $this->dashboardType === 'harvesting'
            ? 'L/T'
            : 'L/ha';
    }

    public function dashboardActivitySubtitle(): string
    {
        return $this->dashboardType === 'harvesting'
            ? 'Planned tons, completed tons and diesel usage by unit'
            : 'Planned area, completed area and diesel usage by unit';
    }

    private function getModelText(
        object $model,
        array $attributes,
        string $fallback = ''
    ): string {
        foreach ($attributes as $attribute) {
            $value = method_exists($model, 'getAttribute')
                ? $model->getAttribute($attribute)
                : null;

            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return $fallback;
    }

    private function getModelNumber(
        object $model,
        array $attributes
    ): float {
        foreach ($attributes as $attribute) {
            $value = method_exists($model, 'getAttribute')
                ? $model->getAttribute($attribute)
                : null;

            if (is_numeric($value)) {
                return max(0, (float) $value);
            }
        }

        return 0;
    }

    private function getZoneName(Zone $zone, int $index): string
{
    return $this->getModelText(
        $zone,
        [
            'zone_code',
            'name',
            'zone_name',
            'code',
            'title',
        ],
        'Zone ' . ($index + 1)
    );
}

    private function getZoneArea(Zone $zone): float
    {
        return $this->getModelNumber(
            $zone,
            ['area', 'total_area', 'hectares', 'hectare', 'size']
        );
    }

    private function getZoneBlockArea(ZoneBlock $zoneBlock): float
    {
        return round(
            max(0, (float) $zoneBlock->area),
            2
        );
    }

    private function getZoneBlockCategory(
    ZoneBlock $zoneBlock
): ?string {
    $register = $zoneBlock->activeRegister
        ?? $zoneBlock->blockRegister;

    $cycleType = $register?->plantingCycleType;

    if (!$cycleType) {
        return 'cycle_type_not_set';
    }

    $text = mb_strtolower(
        trim(
            implode(' ', array_filter([
                $cycleType->code ?? null,
                $cycleType->name ?? null,
            ]))
        )
    );

    $text = preg_replace('/[_\-]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);

    if (
        preg_match(
            '/land vacancy|vacancy|vacant|empty land|unused land|fallow/u',
            $text
        )
    ) {
        return 'land_vacancy';
    }

    if (
        preg_match(
            '/3rd ratoon|third ratoon|ratoon 3|ratoon3|\br3\b/u',
            $text
        )
    ) {
        return 'third_ratoon';
    }

    if (
        preg_match(
            '/2nd ratoon|second ratoon|ratoon 2|ratoon2|\br2\b/u',
            $text
        )
    ) {
        return 'second_ratoon';
    }

    if (
        preg_match(
            '/1st ratoon|first ratoon|ratoon 1|ratoon1|\br1\b/u',
            $text
        )
    ) {
        return 'first_ratoon';
    }

    if (
        preg_match(
            '/new cane|plant cane|new planting|new crop|\bpc\b|\bnc\b/u',
            $text
        )
    ) {
        return 'new_cane';
    }

    return null;
}
    private function getTaskName(
        TaskCategory $taskCategory
    ): string {
        return $this->getModelText(
            $taskCategory,
            ['name', 'task_name', 'category_name', 'title'],
            'Activity #' . $taskCategory->id
        );
    }

    private function getTaskSubtitle(
        TaskCategory $taskCategory
    ): string {
        return $this->getModelText(
            $taskCategory,
            ['description', 'code', 'short_name'],
            'Farm Activity'
        );
    }

    private function getRequestedLitresPerHectare(
        FarmWorkPlan $workPlan
    ): float {
        $requestPerHectare = $this->getModelNumber(
            $workPlan,
            [
                'request_l_per_hectare',
                'requested_l_per_hectare',
                'litres_per_hectare',
                'liters_per_hectare',
            ]
        );

        if ($requestPerHectare > 0) {
            return $requestPerHectare;
        }

        $requestLitres = $this->getModelNumber(
            $workPlan,
            ['request_liters', 'requested_liters']
        );

        $planArea = $this->getModelNumber(
            $workPlan,
            ['plan_area']
        );

        if ($requestLitres > 0 && $planArea > 0) {
            return $requestLitres / $planArea;
        }

        return 0;
    }

    private function getStatusClass(
        float $percentage,
        float $requested,
        float $used,
        bool $hasData
    ): string {
        if (!$hasData) {
            return 'empty';
        }

        $fuelExceeded = $requested > 0
            && $used > ($requested * 1.05);

        if ($percentage >= 75 && !$fuelExceeded) {
            return 'good';
        }

        if ($percentage >= 50 && !$fuelExceeded) {
            return 'warning';
        }

        return 'danger';
    }

    private function emptyActivityCell(): array
    {
        return [
            'has_data' => false,
            'plan_area' => 0,
            'completed_area' => 0,
            'requested' => 0,
            'used' => 0,
            'diesel' => 0,
            'percentage' => 0,
            'status' => 'empty',
            'log_count' => 0,
        ];
    }
    public function farmOverviewIconSvg($label): string
{
    $label = strtolower((string) $label);

    if (str_contains($label, 'new cane')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 21C12 13 16 7 22 4C21 12 17 18 12 21Z"/>
            <path d="M12 21C12 13 8 7 2 4C3 12 7 18 12 21Z"/>
            <path d="M12 21V9"/>
        </svg>';
    }

    if (str_contains($label, '1st') || str_contains($label, '2nd') || str_contains($label, '3rd')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19H20"/>
            <path d="M12 19V9"/>
            <path d="M12 9C9 9 7 7 6 4C10 4 12 6 12 9Z"/>
            <path d="M12 12C15 12 17 10 18 7C14 7 12 9 12 12Z"/>
        </svg>';
    }

    if (str_contains($label, 'land vacancy')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 19H21"/>
            <path d="M5 15L9 11L13 15L17 11L21 15"/>
            <path d="M7 7H17"/>
        </svg>';
    }

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9"/>
        <path d="M9.8 9C10.3 7.8 11.2 7.2 12.5 7.2C14 7.2 15.2 8.2 15.2 9.6C15.2 11.8 12 11.7 12 14"/>
        <path d="M12 17H12.01"/>
    </svg>';
}

    public function with(): array
    {
        try {
            $selectedDate = Carbon::parse(
                $this->dashboardDate
            )->format('Y-m-d');
        } catch (\Throwable $exception) {
            $selectedDate = now()->format('Y-m-d');
        }

        $activeZoneIds = ZoneBlock::query()
            ->where('status', 'active')
            ->whereNotNull('zone_id')
            ->distinct()
            ->pluck('zone_id');

        $allZones = Zone::query()
            ->whereIn('id', $activeZoneIds)
            ->orderBy('id')
            ->get();

        if ($this->selectedZone === 'all') {
            $displayZones = $allZones
                ->take(4)
                ->values();
        } else {
            $displayZones = $allZones
                ->where('id', (int) $this->selectedZone)
                ->values();
        }

        $zoneOptions = $allZones
            ->values()
            ->map(function (Zone $zone, int $index) {
                return [
                    'id' => (string) $zone->id,
                    'name' => $this->getZoneName($zone, $index),
                ];
            });

        $unitColumns = $displayZones
            ->values()
            ->map(function (Zone $zone, int $index) {
                return [
                    'id' => (int) $zone->id,
                    'name' => $this->getZoneName($zone, $index),
                ];
            });

        $allZoneBlocks = ZoneBlock::query()
            ->with([
                'activeRegister.plantingCycleType',
                'blockRegister.plantingCycleType',
            ])
            ->where('status', 'active')
            ->orderBy('zone_id')
            ->orderBy('block_code')
            ->get();

        $zoneBlocksByZone = $allZoneBlocks
            ->filter(fn ($block) => $block->zone_id !== null)
            ->groupBy(fn ($block) => (int) $block->zone_id);

        $zoneBlockIdsByZone = $zoneBlocksByZone
            ->map(
                fn ($blocks) => $blocks
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
            );
            




        $zoneAreaMap = [];

        foreach ($displayZones as $zone) {
            $zoneId = (int) $zone->id;

            $zoneBlocks = $zoneBlocksByZone->get(
                $zoneId,
                collect()
            );

            $zoneAreaMap[$zoneId] = round(
                (float) $zoneBlocks->sum(
                    fn (ZoneBlock $block) =>
                        $this->getZoneBlockArea($block)
                ),
                2
            );
        }

        $summaryDefinitions = [
            [
                'key' => 'total_area',
                'label' => $this->dashboardType === 'harvesting'
                    ? 'Total Tons'
                    : 'Total Area',
                'icon' => 'â–¦',
            ],
            [
                'key' => 'new_cane',
                'label' => 'New Cane',
                'icon' => 'â™§',
            ],
            [
                'key' => 'first_ratoon',
                'label' => '1st Ratoon',
                'icon' => 'â†»',
            ],
            [
                'key' => 'second_ratoon',
                'label' => '2nd Ratoon',
                'icon' => 'â†»',
            ],
            [
                'key' => 'third_ratoon',
                'label' => '3rd Ratoon',
                'icon' => 'â†º',
            ],
            [
                'key' => 'land_vacancy',
                'label' => 'Land Vacancy',
                'icon' => 'â–³',
            ],
            [
                'key' => 'cycle_type_not_set',
                'label' => 'Not Set',
                'icon' => '?',
            ],

        ];

        $summaryRows = collect(
    $summaryDefinitions
)->map(function (array $definition) use (
    $displayZones,
    $zoneBlocksByZone,
    $zoneAreaMap
) {
    $values = [];
    $blocks = [];

    foreach ($displayZones as $zone) {
        $zoneId = (int) $zone->id;

        if ($definition['key'] === 'total_area') {
            $values[$zoneId] = $zoneAreaMap[$zoneId] ?? 0;
            $blocks[$zoneId] = [];

            continue;
        }

        $zoneBlocks = $zoneBlocksByZone->get(
            $zoneId,
            collect()
        );

        $matchedBlocks = $zoneBlocks
            ->filter(
                fn (ZoneBlock $block) =>
                    $this->getZoneBlockCategory($block)
                    === $definition['key']
            )
            ->values();

        $values[$zoneId] = round(
            (float) $matchedBlocks->sum(
                fn (ZoneBlock $block) =>
                    $this->getZoneBlockArea($block)
            ),
            2
        );

        $blocks[$zoneId] =
            $definition['key'] === 'cycle_type_not_set'
                ? $matchedBlocks
                    ->pluck('block_code')
                    ->filter()
                    ->map(fn ($code) => trim((string) $code))
                    ->values()
                    ->all()
                : [];
    }

    return [
        'key' => $definition['key'],
        'label' => $definition['label'],
        'icon' => $definition['icon'],
        'values' => $values,
        'blocks' => $blocks,
        'total' => round(
            (float) array_sum($values),
            2
        ),
    ];
});

        $displayedZoneBlockIds = $unitColumns
            ->flatMap(function (array $unit) use ($zoneBlockIdsByZone) {
                return $zoneBlockIdsByZone->get(
                    $unit['id'],
                    collect()
                );
            })
            ->filter()
            ->unique()
            ->values();

        if ($displayedZoneBlockIds->isEmpty()) {
            $dailyLogs = collect();
        } else {
            $dailyLogs = FarmWorkLog::query()
                ->whereDate('work_date', $selectedDate)
                ->whereIn(
                    'zone_block_id',
                    $displayedZoneBlockIds->all()
                )
                ->where(function ($query) {
                    $query
                        ->whereHas('workPlan.activities.taskCategory.group', function ($groupQuery) {
                            $groupQuery->where('group_type', $this->dashboardType);
                        })
                        ->orWhereHas('workPlan.taskCategory.group', function ($groupQuery) {
                            $groupQuery->where('group_type', $this->dashboardType);
                        });
                })
                ->get([
                    'id',
                    'farm_work_plan_id',
                    'work_date',
                    'tractor_id',
                    'driver_id',
                    'zone_block_id',
                    'task_category_id',
                    'working_duration',
                    'working_area',
                    'diesel_consumed',
                ]);
        }

        $workPlanIds = $dailyLogs
            ->pluck('farm_work_plan_id')
            ->filter()
            ->unique()
            ->values();

        $workPlans = $workPlanIds->isEmpty()
            ? collect()
            : FarmWorkPlan::query()
                ->whereIn('id', $workPlanIds->all())
                ->get()
                ->keyBy('id');

        $allTaskCategories = TaskCategory::query()
            ->with('group')
            ->whereHas('group', function ($query) {
                $query->where('group_type', $this->dashboardType);
            })
            ->orderBy('id')
            ->get();

        $loggedTaskCategoryIds = $dailyLogs
            ->pluck('task_category_id')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id);

        $categoriesWithLogs = $allTaskCategories
            ->filter(
                fn (TaskCategory $category) =>
                    $loggedTaskCategoryIds->contains(
                        (int) $category->id
                    )
            );

        $categoriesWithoutLogs = $allTaskCategories
            ->reject(
                fn (TaskCategory $category) =>
                    $loggedTaskCategoryIds->contains(
                        (int) $category->id
                    )
            );

        $activityCategories = $categoriesWithLogs
            ->concat($categoriesWithoutLogs)
            ->take(10)
            ->values();

        $activityRows = $activityCategories
            ->map(function (TaskCategory $taskCategory) use (
                $unitColumns,
                $dailyLogs,
                $workPlans,
                $zoneBlockIdsByZone,
                $zoneAreaMap
            ) {
                $categoryId = (int) $taskCategory->id;

                $cells = [];

                foreach ($unitColumns as $unit) {
                    $zoneId = (int) $unit['id'];

                    $zoneBlockIds = $zoneBlockIdsByZone->get(
                        $zoneId,
                        collect()
                    );

                    if ($zoneBlockIds->isEmpty()) {
                        $cells[$zoneId] = $this->emptyActivityCell();

                        continue;
                    }

                    $zoneLogs = $dailyLogs
                        ->where(
                            'task_category_id',
                            $categoryId
                        )
                        ->whereIn(
                            'zone_block_id',
                            $zoneBlockIds->all()
                        )
                        ->values();

                    if ($zoneLogs->isEmpty()) {
                        $cells[$zoneId] = $this->emptyActivityCell();

                        continue;
                    }

                    $planIds = $zoneLogs
                        ->pluck('farm_work_plan_id')
                        ->filter()
                        ->unique()
                        ->values();

                    $planAreaFromPlans = $planIds->sum(
                        function ($planId) use ($workPlans) {
                            $plan = $workPlans->get($planId);

                            if (!$plan) {
                                return 0;
                            }

                            return $this->getModelNumber(
                                $plan,
                                ['plan_area']
                            );
                        }
                    );

                    $zoneArea = $zoneAreaMap[$zoneId] ?? 0;

                    $completedArea = (float) $zoneLogs->sum(
                        'working_area'
                    );

                    $dieselConsumed = (float) $zoneLogs->sum(
                        'diesel_consumed'
                    );

                    if ($planAreaFromPlans > 0 && $zoneArea > 0) {
                        $planArea = min(
                            $planAreaFromPlans,
                            $zoneArea
                        );
                    } elseif ($planAreaFromPlans > 0) {
                        $planArea = $planAreaFromPlans;
                    } elseif ($zoneArea > 0) {
                        $planArea = $zoneArea;
                    } else {
                        $planArea = $completedArea;
                    }

                    if (
                        $completedArea > $planArea
                        && $planArea > 0
                    ) {
                        $planArea = $completedArea;
                    }

                    $requestValues = $planIds
                        ->map(function ($planId) use ($workPlans) {
                            $plan = $workPlans->get($planId);

                            return $plan
                                ? $this->getRequestedLitresPerHectare(
                                    $plan
                                )
                                : 0;
                        })
                        ->filter(fn ($value) => $value > 0)
                        ->values();

                    $requested = $requestValues->isNotEmpty()
                        ? (float) $requestValues->average()
                        : 0;

                    $used = $completedArea > 0
                        ? $dieselConsumed / $completedArea
                        : 0;

                    $percentage = $planArea > 0
                        ? min(
                            100,
                            round(
                                ($completedArea / $planArea) * 100
                            )
                        )
                        : 0;

                    $cells[$zoneId] = [
                        'has_data' => true,
                        'plan_area' => $planArea,
                        'completed_area' => $completedArea,
                        'requested' => $requested,
                        'used' => $used,
                        'diesel' => $dieselConsumed,
                        'percentage' => $percentage,
                        'status' => $this->getStatusClass(
                            $percentage,
                            $requested,
                            $used,
                            true
                        ),
                        'log_count' => $zoneLogs->count(),
                    ];
                }

                $cellCollection = collect($cells);

                $totalHasData = $cellCollection
                    ->contains(
                        fn (array $cell) =>
                            $cell['has_data'] === true
                    );

                if (!$totalHasData) {
                    $totalCell = $this->emptyActivityCell();
                } else {
                    $totalPlanArea = (float) $cellCollection->sum(
                        'plan_area'
                    );

                    $totalCompletedArea = (float) $cellCollection->sum(
                        'completed_area'
                    );

                    $totalDiesel = (float) $cellCollection->sum(
                        'diesel'
                    );

                    $requestedWeight = (float) $cellCollection->sum(
                        function (array $cell) {
                            return $cell['requested']
                                * $cell['plan_area'];
                        }
                    );

                    $requested = $totalPlanArea > 0
                        ? $requestedWeight / $totalPlanArea
                        : 0;

                    $used = $totalCompletedArea > 0
                        ? $totalDiesel / $totalCompletedArea
                        : 0;

                    $percentage = $totalPlanArea > 0
                        ? min(
                            100,
                            round(
                                (
                                    $totalCompletedArea
                                    / $totalPlanArea
                                ) * 100
                            )
                        )
                        : 0;

                    $totalCell = [
                        'has_data' => true,
                        'plan_area' => $totalPlanArea,
                        'completed_area' => $totalCompletedArea,
                        'requested' => $requested,
                        'used' => $used,
                        'diesel' => $totalDiesel,
                        'percentage' => $percentage,
                        'status' => $this->getStatusClass(
                            $percentage,
                            $requested,
                            $used,
                            true
                        ),
                        'log_count' => $cellCollection->sum(
                            'log_count'
                        ),
                    ];
                }

                return [
                    'id' => $categoryId,
                    'name' => $this->getTaskName($taskCategory),
                    'subtitle' => $this->getTaskSubtitle(
                        $taskCategory
                    ),
                    'cells' => $cells,
                    'total' => $totalCell,
                ];
            });

        return [
            'zoneOptions' => $zoneOptions,
            'unitColumns' => $unitColumns,
            'summaryRows' => $summaryRows,
            'activityRows' => $activityRows,

            'selectedDateLabel' => Carbon::parse(
                $selectedDate
            )->format('d M Y'),

            'totalArea' => round(
                (float) array_sum($zoneAreaMap),
                2
            ),

            'dailyLogCount' => $dailyLogs->count(),

            'totalTractors' => Tractor::query()->count(),

            'totalDrivers' => Driver::query()->count(),
        ];
    }
};

?>

<div class="fod-page">
    @include('components.shared-style')

    <style>
        .fod-page {
            --fod-primary: #15803d;
            --fod-primary-dark: #166534;
            --fod-primary-soft: #ecfdf3;
            --fod-text: #0f172a;
            --fod-muted: #64748b;
            --fod-border: #e2e8f0;
            --fod-background: #f7f9fc;
            --fod-danger: #ef4444;
            --fod-warning: #f97316;
            --fod-good: #16a34a;

            min-height: 100%;
            padding: 20px;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(22, 163, 74, 0.06),
                    transparent 340px
                ),
                var(--fod-background);
            color: var(--fod-text);
        }

        .fod-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }
        .farm-overview-icon {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 12px;
    background: #16a34a;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 22px rgba(22, 163, 74, 0.22);
}

.farm-overview-icon svg {
    width: 25px;
    height: 25px;
}

        .fod-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .fod-logo {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            color: #ffffff;
            background:
                linear-gradient(
                    145deg,
                    #15803d,
                    #166534
                );
            box-shadow:
                0 10px 25px rgba(22, 101, 52, 0.2);
        }

        .fod-logo svg {
            width: 31px;
            height: 31px;
        }

        .fod-heading {
            min-width: 0;
        }

        .fod-heading h1 {
            margin: 0;
            color: #111827;
            font-size: clamp(24px, 3vw, 34px);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -0.8px;
        }

        .fod-heading p {
            margin: 5px 0 0;
            color: var(--fod-muted);
            font-size: 14px;
            font-weight: 600;
        }

        .fod-heading-stats {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 9px;
        }

        .fod-heading-stat {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 27px;
            padding: 4px 9px;
            border: 1px solid #dcfce7;
            border-radius: 999px;
            color: #166534;
            background: #f0fdf4;
            font-size: 11px;
            font-weight: 800;
        }

        .fod-header-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
        }

        .fod-control {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 46px;
            border: 1px solid var(--fod-border);
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.03);
        }

        .fod-control-icon {
            position: absolute;
            left: 13px;
            width: 18px;
            height: 18px;
            color: #475569;
            pointer-events: none;
            z-index: 1;
        }

        .fod-control input,
        .fod-control select {
            min-width: 175px;
            height: 44px;
            padding: 0 38px 0 42px;
            border: 0;
            outline: none;
            border-radius: 12px;
            color: #1e293b;
            background: transparent;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
        }

        .fod-control select {
            min-width: 145px;
            cursor: pointer;
        }

        .fod-add-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 17px;
            border: 1px solid var(--fod-primary);
            border-radius: 12px;
            color: #ffffff;
            background:
                linear-gradient(
                    145deg,
                    var(--fod-primary),
                    var(--fod-primary-dark)
                );
            box-shadow:
                0 9px 20px rgba(21, 128, 61, 0.18);
            font-size: 13px;
            font-weight: 850;
            text-decoration: none;
            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .fod-add-button:hover {
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow:
                0 12px 25px rgba(21, 128, 61, 0.24);
        }

        .fod-add-button svg {
            width: 18px;
            height: 18px;
        }

        .fod-matrix-scroll {
            position: relative;
            max-height: calc(100vh - 130px);
            overflow-x: auto;
            overflow-y: auto;
            border: 1px solid var(--fod-border);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow:
                0 16px 45px rgba(15, 23, 42, 0.06);
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .fod-matrix-scroll::-webkit-scrollbar {
            height: 9px;
        }

        .fod-matrix-scroll::-webkit-scrollbar-thumb {
            border: 2px solid transparent;
            border-radius: 999px;
            background: #cbd5e1;
            background-clip: padding-box;
        }

        .fod-matrix-grid {
            display: grid;
            align-items: stretch;
            gap: 8px;
            padding: 16px;
        }

        .fod-corner-card,
        .fod-unit-header {
            min-height: 96px;
            border: 1px solid #e5eaf1;
            border-radius: 14px;
            background:
                linear-gradient(
                    180deg,
                    #ffffff,
                    #fbfcfe
                );
        }
                /* Keep the first dashboard row fixed when scrolling */
        .fod-corner-card,
        .fod-unit-header {
            position: sticky;
            top: 0;
            z-index: 25;
            background: #ffffff;
            box-shadow: 0 5px 12px rgba(15, 23, 42, 0.06);
        }

        .fod-unit-header.is-total {
            background: #f8fafc;
        }

        .fod-corner-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 16px 18px;
        }

        .fod-corner-eyebrow {
            color: var(--fod-primary);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.9px;
            text-transform: uppercase;
        }

        .fod-corner-total {
            margin-top: 5px;
            color: #0f172a;
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
        }

        .fod-corner-date {
            margin-top: 7px;
            color: var(--fod-muted);
            font-size: 12px;
            font-weight: 650;
        }

        .fod-unit-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 13px;
            text-align: center;
        }

        .fod-unit-header.is-total {
            background:
                linear-gradient(
                    180deg,
                    #f8fafc,
                    #f1f5f9
                );
        }

        .fod-unit-name {
            color: #111827;
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
        }

        .fod-unit-header:first-of-type .fod-unit-name {
            color: var(--fod-primary);
        }

        .fod-unit-icon {
            width: 29px;
            height: 29px;
            color: var(--fod-primary);
        }
        .fod-unit-area {
                margin-top: 2px;
                color: var(--fod-primary);
                font-size: 16px;
                font-weight: 900;
                line-height: 1;
            }

            .fod-unit-area.is-total {
                color: #0f172a;
            }

        .fod-unit-header.is-total .fod-unit-icon {
            color: #475569;
        }

        .fod-summary-label,
        .fod-summary-value {
            min-height: 74px;
            border: 1px solid #e8edf3;
            border-radius: 12px;
            background: #ffffff;
        }
        .fod-summary-value-content {
    width: 100%;
    min-width: 0;
    text-align: center;
}

.fod-summary-value-content strong {
    display: block;
    font-size: 17px;
    font-weight: 900;
}

.fod-summary-value-content small {
    display: block;
    margin-top: 5px;
    overflow-wrap: anywhere;
    color: #64748b;
    font-size: 10px;
    font-weight: 750;
    line-height: 1.35;
    white-space: normal;
}

        .fod-summary-label {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px 12px 18px;
            overflow: hidden;
        }

        .fod-summary-label::before,
        .fod-activity-label::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 4px;
            background: var(--fod-primary);
        }

        .fod-summary-icon {
            width: 43px;
            height: 43px;
            flex: 0 0 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: #ffffff;
            background:
                linear-gradient(
                    145deg,
                    #16a34a,
                    #15803d
                );
            font-size: 23px;
            font-weight: 900;
            box-shadow:
                0 7px 16px rgba(22, 163, 74, 0.16);
        }
        .fod-summary-icon svg {
            width: 25px;
            height: 25px;
        }

        .fod-summary-info {
            min-width: 0;
        }

        .fod-summary-name {
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .fod-summary-total {
            margin-top: 3px;
            color: #111827;
            font-size: 21px;
            font-weight: 900;
            line-height: 1;
        }

        .fod-summary-value {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            color: #1e293b;
            font-size: 17px;
            font-weight: 800;
            text-align: center;
            background:
                linear-gradient(
                    180deg,
                    #ffffff,
                    #fcfdff
                );
        }

        .fod-summary-value.is-primary {
            color: var(--fod-primary);
        }

        .fod-summary-value.is-danger {
            color: var(--fod-danger);
        }

        .fod-summary-value.is-total {
            color: #0f172a;
            background: #f8fafc;
            font-weight: 900;
        }

        .fod-section-title {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin: 7px 0 0;
            padding: 14px 3px 7px;
            border-top: 1px solid #e8edf3;
        }

        .fod-section-title h2 {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
            font-weight: 900;
        }

        .fod-section-title p {
            margin: 3px 0 0;
            color: var(--fod-muted);
            font-size: 12px;
            font-weight: 600;
        }

        .fod-section-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 4px 10px;
            border-radius: 999px;
            color: #166534;
            background: #ecfdf3;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .fod-activity-label,
        .fod-activity-card {
            min-height: 122px;
            border: 1px solid #e6ebf1;
            border-radius: 13px;
            background: #ffffff;
        }

        .fod-activity-label {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 15px 15px 18px;
            overflow: hidden;
        }

        .fod-activity-icon {
            width: 45px;
            height: 45px;
            flex: 0 0 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: var(--fod-primary);
            background: var(--fod-primary-soft);
        }

        .fod-activity-icon svg {
            width: 27px;
            height: 27px;
        }

        .fod-activity-info {
            min-width: 0;
        }

        .fod-activity-name {
            color: #1e293b;
            font-size: 14px;
            font-weight: 900;
            line-height: 1.3;
        }

        .fod-activity-subtitle {
            margin-top: 4px;
            overflow: hidden;
            color: #64748b;
            font-size: 11px;
            font-weight: 650;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .fod-activity-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 12px;
            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .fod-activity-card:hover {
            transform: translateY(-1px);
            box-shadow:
                0 9px 20px rgba(15, 23, 42, 0.07);
        }

        .fod-activity-card.is-total {
            background:
                linear-gradient(
                    180deg,
                    #fbfcfe,
                    #f8fafc
                );
        }

        .fod-progress-head {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .fod-percentage {
            min-width: 36px;
            font-size: 15px;
            font-weight: 950;
        }

        .fod-progress {
            position: relative;
            flex: 1;
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #e7ebf0;
        }

        .fod-progress-bar {
            height: 100%;
            border-radius: inherit;
            transition: width 0.3s ease;
        }

        .fod-status-good .fod-percentage {
            color: var(--fod-good);
        }

        .fod-status-good .fod-progress-bar {
            background:
                linear-gradient(
                    90deg,
                    #16a34a,
                    #4ade80
                );
        }

        .fod-status-warning .fod-percentage {
            color: var(--fod-warning);
        }

        .fod-status-warning .fod-progress-bar {
            background:
                linear-gradient(
                    90deg,
                    #f97316,
                    #fb923c
                );
        }

        .fod-status-danger .fod-percentage {
            color: var(--fod-danger);
        }

        .fod-status-danger .fod-progress-bar {
            background:
                linear-gradient(
                    90deg,
                    #ef4444,
                    #fb7185
                );
        }

        .fod-status-empty .fod-percentage {
            color: #94a3b8;
        }

        .fod-status-empty .fod-progress-bar {
            width: 0 !important;
            background: #cbd5e1;
        }

        .fod-activity-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 7px 10px;
            margin-top: 13px;
        }

        .fod-detail {
            min-width: 0;
        }

        .fod-detail-label {
            display: block;
            color: #7c8798;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.15px;
            text-transform: uppercase;
        }

        .fod-detail-value {
            display: block;
            margin-top: 2px;
            overflow: hidden;
            color: #334155;
            font-size: 10.5px;
            font-weight: 850;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .fod-detail-value.is-completed {
            color: var(--fod-good);
        }

        .fod-detail-value.is-over {
            color: var(--fod-danger);
        }

        .fod-detail-value.is-normal {
            color: var(--fod-good);
        }

        .fod-empty-text {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 750;
        }

        .fod-no-activities {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            color: #64748b;
            background: #f8fafc;
            font-size: 13px;
            font-weight: 750;
            text-align: center;
        }

        .fod-loading {
            position: absolute;
            inset: 0;
            z-index: 30;
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            background: rgba(248, 250, 252, 0.74);
            backdrop-filter: blur(2px);
        }

        .fod-spinner {
            width: 36px;
            height: 36px;
            border: 4px solid #dcfce7;
            border-top-color: var(--fod-primary);
            border-radius: 50%;
            animation: fod-spin 0.7s linear infinite;
        }

        @keyframes fod-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1100px) {
            .fod-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .fod-header-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 680px) {
            .fod-page {
                padding: 12px;
            }

            .fod-header-left {
                align-items: flex-start;
            }

            .fod-logo {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
                border-radius: 13px;
            }

            .fod-heading h1 {
                font-size: 23px;
            }

            .fod-header-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .fod-control,
            .fod-add-button {
                width: 100%;
            }

            .fod-control input,
            .fod-control select {
                width: 100%;
                min-width: 0;
            }

            .fod-add-button {
                min-height: 45px;
            }

            .fod-matrix-grid {
                padding: 11px;
            }
        }
    </style>

    <div class="fod-header">
        <div class="fod-header-left">
            

            <div class="fod-heading">
                <h1>{{ $this->dashboardTypeLabel() }} Farm Operations Dashboard</h1>

                <p>
                    Sugarcane farm overview and daily activity performance
                </p>

                <div class="fod-heading-stats">
                    <span class="fod-heading-stat">
                        {{ number_format($dailyLogCount) }} work logs
                    </span>

                    <span class="fod-heading-stat">
                        {{ number_format($totalTractors) }} tractors
                    </span>

                    <span class="fod-heading-stat">
                        {{ number_format($totalDrivers) }} drivers
                    </span>
                </div>
            </div>
        </div>

        <div class="fod-header-actions">
            <label class="fod-control">
                <select
                    wire:model.live="dashboardType"
                    aria-label="Dashboard type"
                >
                    <option value="planning">Planning</option>
                    <option value="harvesting">Harvesting</option>
                </select>
            </label>

            <label class="fod-control">

                <input
                    type="date"
                    wire:model.live="dashboardDate"
                    aria-label="Dashboard date"
                >
            </label>

            <label class="fod-control">

                <select
                    wire:model.live="selectedZone"
                    aria-label="Filter unit"
                >
                    <option value="all">All Units</option>

                    @foreach($zoneOptions as $zoneOption)
                        <option value="{{ $zoneOption['id'] }}">
                            {{ $zoneOption['name'] }}
                        </option>
                    @endforeach
                </select>
            </label>

            <a
                href="{{ route('farm-work-logs.create', ['workLogType' => $this->dashboardType]) }}"
                class="fod-add-button"
            >
               

                Add Work Log
            </a>
        </div>
    </div>

    @php
        $dashboardColumnCount = $unitColumns->count() + 1;
        $dashboardMinimumWidth = 255 + ($dashboardColumnCount * 220);
    @endphp

    <div class="fod-matrix-scroll">
        <div
            class="fod-loading"
            wire:loading.flex
            wire:target="dashboardDate,selectedZone,dashboardType"
        >
            <div class="fod-spinner"></div>
        </div>

        <div
            class="fod-matrix-grid"
            style="
                grid-template-columns:
                    minmax(225px, 255px)
                    repeat(
                        {{ $dashboardColumnCount }},
                        minmax(205px, 1fr)
                    );
                min-width: {{ $dashboardMinimumWidth }}px;
            "
        >
            <div class="fod-corner-card">
                <div class="fod-corner-eyebrow">
                    Farm Overview
                </div>

                <div class="fod-corner-total">
                    {{ number_format($totalArea, 2) }} {{ $this->dashboardQtyUnitLabel() }}
                </div>

                <div class="fod-corner-date">
                    {{ $selectedDateLabel }}
                </div>
            </div>

            @foreach($unitColumns as $unit)
    @php
        $unitArea =
            $summaryRows
                ->firstWhere('key', 'total_area')['values'][$unit['id']]
            ?? 0;
    @endphp

    <div class="fod-unit-header">
        <div class="fod-unit-name">
            {{ $unit['name'] }}
        </div>


        <div class="fod-unit-area">
            {{ number_format($unitArea, 2) }} {{ $this->dashboardQtyUnitLabel() }}
        </div>
    </div>
@endforeach

            <div class="fod-unit-header is-total">
    <div class="fod-unit-name">
        Total
    </div>

    

    <div class="fod-unit-area is-total">
        {{ number_format($totalArea, 2) }} {{ $this->dashboardQtyUnitLabel() }}
    </div>
</div>

           @foreach(
                    $summaryRows->where('key', '!=', 'total_area')
                    as $summaryRow
                )
                <div class="fod-summary-label">
                    <div class="fod-summary-icon">
                        {!! $this->farmOverviewIconSvg($summaryRow['label'] ?? '') !!}
                    </div>

                    <div class="fod-summary-info">
                        <div class="fod-summary-name">
                            {{ $summaryRow['label'] }}
                        </div>

                        <div class="fod-summary-total">
                            {{ number_format($summaryRow['total'], 2) }}
                            {{ $this->dashboardQtyUnitLabel() }}
                        </div>
                    </div>
                </div>

                @foreach($unitColumns as $unitIndex => $unit)
                    @php
                        $summaryValue =
                            $summaryRow['values'][$unit['id']] ?? 0;

                        $summaryBlocks =
                            $summaryRow['blocks'][$unit['id']] ?? [];

                        $summaryValueClass =
                            $summaryRow['key'] === 'land_vacancy'
                                ? 'is-danger'
                                : ($unitIndex === 0 ? 'is-primary' : '');
                    @endphp

                   <div class="fod-summary-value {{ $summaryValueClass }}">
                        <div class="fod-summary-value-content">
                            <strong>
                                {{ number_format($summaryValue, 2) }} {{ $this->dashboardQtyUnitLabel() }}
                            </strong>

                            @if(
                                $summaryRow['key'] === 'cycle_type_not_set'
                                && count($summaryBlocks) > 0
                            )
                                <small>
                                    {{ implode(', ', $summaryBlocks) }}
                                </small>
                            @endif
                        </div>
                    </div>
                @endforeach

                @php
    $totalSummaryBlocks = collect(
        $summaryRow['blocks'] ?? []
    )
        ->flatten()
        ->filter()
        ->unique()
        ->values()
        ->all();
@endphp

<div class="fod-summary-value is-total">
    <div class="fod-summary-value-content">
        <strong>
            {{ number_format($summaryRow['total'], 2) }} {{ $this->dashboardQtyUnitLabel() }}
        </strong>

        @if(
            $summaryRow['key'] === 'cycle_type_not_set'
            && count($totalSummaryBlocks) > 0
        )
            <small>
                {{ implode(', ', $totalSummaryBlocks) }}
            </small>
        @endif
    </div>
</div>
            @endforeach

            <div class="fod-section-title">
                <div>
                    <h2>Activity Performance</h2>

                    <p>
                        {{ $this->dashboardActivitySubtitle() }}
                    </p>
                </div>

                <span class="fod-section-badge">
                    {{ $selectedDateLabel }}
                </span>
            </div>

            @forelse($activityRows as $activityRow)
                <div
                    class="fod-activity-label"
                    wire:key="activity-label-{{ $activityRow['id'] }}"
                >
                    <div class="fod-activity-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M4 15h11l3 3"></path>
                            <path d="M7 15V9h6l3 6"></path>
                            <path d="M13 9V6H9"></path>
                            <circle cx="7" cy="18" r="3"></circle>
                            <circle cx="18" cy="18" r="2"></circle>
                            <path d="M2 12h5"></path>
                        </svg>
                    </div>

                    <div class="fod-activity-info">
                        <div class="fod-activity-name">
                            {{ $activityRow['name'] }}
                        </div>

                        <div
                            class="fod-activity-subtitle"
                            title="{{ $activityRow['subtitle'] }}"
                        >
                            {{ $activityRow['subtitle'] }}
                        </div>
                    </div>
                </div>

                @foreach($unitColumns as $unit)
                    @php
                        $cell =
                            $activityRow['cells'][$unit['id']]
                            ?? [
                                'has_data' => false,
                                'plan_area' => 0,
                                'completed_area' => 0,
                                'requested' => 0,
                                'used' => 0,
                                'percentage' => 0,
                                'status' => 'empty',
                            ];
                    @endphp

                    <div
                        class="
                            fod-activity-card
                            fod-status-{{ $cell['status'] }}
                        "
                        wire:key="
                            activity-cell-
                            {{ $activityRow['id'] }}-
                            {{ $unit['id'] }}
                        "
                    >
                        @if($cell['has_data'])
                            <div class="fod-progress-head">
                                <span class="fod-percentage">
                                    {{ $cell['percentage'] }}%
                                </span>

                                <div class="fod-progress">
                                    <div
                                        class="fod-progress-bar"
                                        style="
                                            width:
                                            {{ $cell['percentage'] }}%;
                                        "
                                    ></div>
                                </div>
                            </div>

                            <div class="fod-activity-details">
                                <div class="fod-detail">
                                    <span class="fod-detail-label">
                                        Plan
                                    </span>

                                    <span class="fod-detail-value">
                                        {{ number_format(
                                            $cell['plan_area'],
                                            2
                                        ) }}
                                        {{ $this->dashboardQtyUnitLabel() }}
                                    </span>
                                </div>

                                <div class="fod-detail">
                                    <span class="fod-detail-label">
                                        Requested
                                    </span>

                                    <span class="fod-detail-value">
                                        @if($cell['requested'] > 0)
                                            {{ number_format(
                                                $cell['requested'],
                                                2
                                            ) }}
                                            {{ $this->dashboardFuelRateLabel() }}
                                        @else
                                            â€”
                                        @endif
                                    </span>
                                </div>

                                <div class="fod-detail">
                                    <span class="fod-detail-label">
                                        Completed
                                    </span>

                                    <span class="
                                        fod-detail-value
                                        is-completed
                                    ">
                                        {{ number_format(
                                            $cell['completed_area'],
                                            2
                                        ) }}
                                        {{ $this->dashboardQtyUnitLabel() }}
                                    </span>
                                </div>

                                <div class="fod-detail">
                                    <span class="fod-detail-label">
                                        Used
                                    </span>

                                    <span
                                        class="
                                            fod-detail-value
                                            {{
                                                $cell['requested'] > 0
                                                && $cell['used']
                                                    > $cell['requested']
                                                    ? 'is-over'
                                                    : 'is-normal'
                                            }}
                                        "
                                    >
                                        @if($cell['used'] > 0)
                                            {{ number_format(
                                                $cell['used'],
                                                2
                                            ) }}
                                            {{ $this->dashboardFuelRateLabel() }}
                                        @else
                                            â€”
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="fod-empty-text">
                                No work recorded
                            </div>
                        @endif
                    </div>
                @endforeach

                @php
                    $totalCell = $activityRow['total'];
                @endphp

                <div
                    class="
                        fod-activity-card
                        is-total
                        fod-status-{{ $totalCell['status'] }}
                    "
                    wire:key="activity-total-{{ $activityRow['id'] }}"
                >
                    @if($totalCell['has_data'])
                        <div class="fod-progress-head">
                            <span class="fod-percentage">
                                {{ $totalCell['percentage'] }}%
                            </span>

                            <div class="fod-progress">
                                <div
                                    class="fod-progress-bar"
                                    style="
                                        width:
                                        {{ $totalCell['percentage'] }}%;
                                    "
                                ></div>
                            </div>
                        </div>

                        <div class="fod-activity-details">
                            <div class="fod-detail">
                                <span class="fod-detail-label">
                                    Plan
                                </span>

                                <span class="fod-detail-value">
                                    {{ number_format(
                                        $totalCell['plan_area'],
                                        2
                                    ) }}
                                    {{ $this->dashboardQtyUnitLabel() }}
                                </span>
                            </div>

                            <div class="fod-detail">
                                <span class="fod-detail-label">
                                    Requested
                                </span>

                                <span class="fod-detail-value">
                                    @if($totalCell['requested'] > 0)
                                        {{ number_format(
                                            $totalCell['requested'],
                                            2
                                        ) }}
                                        {{ $this->dashboardFuelRateLabel() }}
                                    @else
                                        â€”
                                    @endif
                                </span>
                            </div>

                            <div class="fod-detail">
                                <span class="fod-detail-label">
                                    Completed
                                </span>

                                <span class="
                                    fod-detail-value
                                    is-completed
                                ">
                                    {{ number_format(
                                        $totalCell['completed_area'],
                                        2
                                    ) }}
                                    {{ $this->dashboardQtyUnitLabel() }}
                                </span>
                            </div>

                            <div class="fod-detail">
                                <span class="fod-detail-label">
                                    Used
                                </span>

                                <span
                                    class="
                                        fod-detail-value
                                        {{
                                            $totalCell['requested'] > 0
                                            && $totalCell['used']
                                                > $totalCell['requested']
                                                ? 'is-over'
                                                : 'is-normal'
                                        }}
                                    "
                                >
                                    @if($totalCell['used'] > 0)
                                        {{ number_format(
                                            $totalCell['used'],
                                            2
                                        ) }}
                                        {{ $this->dashboardFuelRateLabel() }}
                                    @else
                                        â€”
                                    @endif
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="fod-empty-text">
                            No work recorded
                        </div>
                    @endif
                </div>
            @empty
                <div class="fod-no-activities">
                    No task categories are available.
                </div>
            @endforelse
        </div>
    </div>
</div>