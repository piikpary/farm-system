@php
    use App\Models\SidebarMenuSetting;

    $sidebarSettings = SidebarMenuSetting::pluck('is_visible', 'menu_key')->toArray();

    $showTractors = $sidebarSettings['tractors'] ?? false;
    $showDrivers = $sidebarSettings['drivers'] ?? false;
    $showZones = $sidebarSettings['zones'] ?? false;
    $showZoneBlocks = $sidebarSettings['zone_blocks'] ?? true;
    $showTaskCategories = $sidebarSettings['task_categories'] ?? false;
    $showPlantingCycleTypes = $sidebarSettings['planting_cycle_types'] ?? true;
    $showBlockRegisters = $sidebarSettings['block_registers'] ?? true;

    $showFuelReport = $sidebarSettings['fuel_report'] ?? false;
    $showTractorReport = $sidebarSettings['tractor_report'] ?? false;
    $showDriverReport = $sidebarSettings['driver_report'] ?? false;
    $showZoneReport = $sidebarSettings['zone_report'] ?? false;
    $showTaskCategorySummaryReport = $sidebarSettings['task_category_summary_report'] ?? false;

    $showAiSettings = $sidebarSettings['ai_settings'] ?? false;
    $showUsers = $sidebarSettings['users'] ?? false;
    $showRoles = $sidebarSettings['roles'] ?? false;

    $hasMain = auth()->user()->hasPermission('dashboard.view');

    $hasFarmOperation = (
        auth()->user()->hasPermission('stock_fuel.view') ||
        auth()->user()->hasPermission('work_logs.view') ||
        auth()->user()->hasPermission('work_logs.create') ||
        ($showBlockRegisters && auth()->user()->hasPermission('block_registers.view'))
    );

    $hasReports = (
        ($showTaskCategorySummaryReport && auth()->user()->hasPermission('reports.task_category_summary')) ||
        ($showFuelReport && auth()->user()->hasPermission('reports.fuel')) ||
        ($showTractorReport && auth()->user()->hasPermission('reports.tractors')) ||
        ($showDriverReport && auth()->user()->hasPermission('reports.drivers')) ||
        ($showZoneReport && auth()->user()->hasPermission('reports.zones'))
    );

    $hasSettings = (
        auth()->user()->hasPermission('sidebar_settings.view') ||
        auth()->user()->hasPermission('tractor_field_settings.view') ||
        ($showAiSettings && auth()->user()->hasPermission('ai_settings.view')) ||
        ($showUsers && auth()->user()->hasPermission('users.view')) ||
        ($showRoles && auth()->user()->hasPermission('roles.view'))
    );

    $hasMasterData = (
        ($showTractors && auth()->user()->hasPermission('tractors.view')) ||
        ($showDrivers && auth()->user()->hasPermission('drivers.view')) ||
        ($showZones && auth()->user()->hasPermission('zones.view')) ||
        ($showZoneBlocks && auth()->user()->hasPermission('zone_blocks.view')) ||
        ($showTaskCategories && auth()->user()->hasPermission('task_categories.view')) ||
        ($showPlantingCycleTypes && auth()->user()->hasPermission('planting_cycle_types.view'))
    );

    $mainActive = request()->routeIs('dashboard');

    $farmOperationActive = request()->routeIs('stock-fuel.*') ||
        request()->routeIs('farm-work-logs.*') ||
        request()->routeIs('block-registers.*');

    $reportsActive = request()->routeIs('reports.*');

    $settingsActive = request()->routeIs('sidebar-settings.*') ||
        request()->routeIs('tractor-field-settings.*') ||
        request()->routeIs('ai-settings.*') ||
        request()->routeIs('users.*') ||
        request()->routeIs('roles.*');

    $masterDataActive = request()->routeIs('tractors.*') ||
        request()->routeIs('drivers.*') ||
        request()->routeIs('zones.*') ||
        request()->routeIs('zone-blocks.*') ||
        request()->routeIs('task-categories.*') ||
        request()->routeIs('planting-cycle-types.*');
@endphp

<style>
    .farm-dropdown {
        margin-top: 8px;
    }

    .farm-dropdown summary {
        list-style: none;
        cursor: pointer;
        user-select: none;
    }

    .farm-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .farm-dropdown-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        margin: 4px 10px;
        border-radius: 12px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: 0.2s ease;
    }

    .farm-dropdown-title:hover {
        background: rgba(255,255,255,0.06);
        color: #ffffff;
    }

    .farm-dropdown.active > summary .farm-dropdown-title {
        background: rgba(22, 163, 74, 0.16);
        color: #bbf7d0;
    }

    .farm-dropdown-arrow {
        transition: 0.2s ease;
        font-size: 11px;
    }

    .farm-dropdown[open] .farm-dropdown-arrow {
        transform: rotate(180deg);
    }

    .farm-dropdown-body {
        padding: 2px 0 8px;
    }

    .farm-dropdown-body .farm-menu-link {
        margin-left: 14px;
        margin-right: 10px;
        padding-left: 16px;
    }
</style>

<aside id="farmSidebar" class="farm-sidebar">
    <div class="farm-brand">
        <div class="farm-brand-icon">🌿</div>

        <div>
            <div class="farm-brand-title">{{ __('sidebar.farm_control') }}</div>
            <div class="farm-brand-sub">{{ __('sidebar.smart_farm_operation') }}</div>
        </div>
    </div>

    <nav class="farm-menu">
        @if($hasMain)
            <details class="farm-dropdown {{ $mainActive ? 'active' : '' }}" {{ $mainActive ? 'open' : '' }}>
                <summary>
                    <div class="farm-dropdown-title">
                        <span>{{ __('sidebar.main') }}</span>
                        <span class="farm-dropdown-arrow">▼</span>
                    </div>
                </summary>

                <div class="farm-dropdown-body">
                    <a href="{{ route('dashboard') }}"
                       class="farm-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="farm-menu-icon">🏠</span>
                        <span>{{ __('sidebar.dashboard') }}</span>
                    </a>
                </div>
            </details>
        @endif

        @if($hasFarmOperation)
            <details class="farm-dropdown {{ $farmOperationActive ? 'active' : '' }}" {{ $farmOperationActive ? 'open' : '' }}>
                <summary>
                    <div class="farm-dropdown-title">
                        <span>{{ __('sidebar.farm_operation') }}</span>
                        <span class="farm-dropdown-arrow">▼</span>
                    </div>
                </summary>

                <div class="farm-dropdown-body">
                    @if(auth()->user()->hasPermission('stock_fuel.view'))
                        <a href="{{ route('stock-fuel.index') }}"
                           class="farm-menu-link {{ request()->routeIs('stock-fuel.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">⛽</span>
                            <span>{{ __('sidebar.stock_fuel') }}</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('work_logs.view'))
                        <a href="{{ route('farm-work-logs.index') }}"
                           class="farm-menu-link {{ request()->routeIs('farm-work-logs.index') ? 'active' : '' }}">
                            <span class="farm-menu-icon">📝</span>
                            <span>{{ __('sidebar.work_logs') }}</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('work_logs.create'))
                        <a href="{{ route('farm-work-logs.create') }}"
                           class="farm-menu-link {{ request()->routeIs('farm-work-logs.create') ? 'active' : '' }}">
                            <span class="farm-menu-icon">➕</span>
                            <span>{{ __('sidebar.add_work_log') }}</span>
                        </a>
                    @endif

                    @if($showBlockRegisters && auth()->user()->hasPermission('block_registers.view'))
                        <a href="{{ route('block-registers.index') }}"
                           class="farm-menu-link {{ request()->routeIs('block-registers.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">📋</span>
                            <span>{{ __('sidebar.block_registers') }}</span>
                        </a>
                    @endif
                </div>
            </details>
        @endif

        @if($hasReports)
            <details class="farm-dropdown {{ $reportsActive ? 'active' : '' }}" {{ $reportsActive ? 'open' : '' }}>
                <summary>
                    <div class="farm-dropdown-title">
                        <span>{{ __('sidebar.reports') }}</span>
                        <span class="farm-dropdown-arrow">▼</span>
                    </div>
                </summary>

                <div class="farm-dropdown-body">
                    @if($showTaskCategorySummaryReport && auth()->user()->hasPermission('reports.task_category_summary'))
                        <a href="{{ route('reports.task-category-summary') }}"
                           class="farm-menu-link {{ request()->routeIs('reports.task-category-summary') ? 'active' : '' }}">
                            <span class="farm-menu-icon">📊</span>
                            <span>{{ __('sidebar.task_category_summary_report') }}</span>
                        </a>
                    @endif

                    @if($showFuelReport && auth()->user()->hasPermission('reports.fuel'))
                        <a href="{{ route('reports.fuel') }}"
                           class="farm-menu-link {{ request()->routeIs('reports.fuel') ? 'active' : '' }}">
                            <span class="farm-menu-icon">⛽</span>
                            <span>{{ __('sidebar.fuel_report') }}</span>
                        </a>
                    @endif

                    @if($showTractorReport && auth()->user()->hasPermission('reports.tractors'))
                        <a href="{{ route('reports.tractors') }}"
                           class="farm-menu-link {{ request()->routeIs('reports.tractors') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🚜</span>
                            <span>{{ __('sidebar.tractor_report') }}</span>
                        </a>
                    @endif

                    @if($showDriverReport && auth()->user()->hasPermission('reports.drivers'))
                        <a href="{{ route('reports.drivers') }}"
                           class="farm-menu-link {{ request()->routeIs('reports.drivers') ? 'active' : '' }}">
                            <span class="farm-menu-icon">👷</span>
                            <span>{{ __('sidebar.driver_report') }}</span>
                        </a>
                    @endif

                    @if($showZoneReport && auth()->user()->hasPermission('reports.zones'))
                        <a href="{{ route('reports.zones') }}"
                           class="farm-menu-link {{ request()->routeIs('reports.zones') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🗺️</span>
                            <span>{{ __('sidebar.zone_report') }}</span>
                        </a>
                    @endif
                </div>
            </details>
        @endif

        @if($hasSettings)
            <details class="farm-dropdown {{ $settingsActive ? 'active' : '' }}" {{ $settingsActive ? 'open' : '' }}>
                <summary>
                    <div class="farm-dropdown-title">
                        <span>{{ __('sidebar.settings') }}</span>
                        <span class="farm-dropdown-arrow">▼</span>
                    </div>
                </summary>

                <div class="farm-dropdown-body">
                    @if(auth()->user()->hasPermission('sidebar_settings.view'))
                        <a href="{{ route('sidebar-settings.index') }}"
                           class="farm-menu-link {{ request()->routeIs('sidebar-settings.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">⚙️</span>
                            <span>{{ __('sidebar.sidebar_settings') }}</span>
                        </a>
                    @endif

                    @if(auth()->user()->hasPermission('tractor_field_settings.view'))
                        <a href="{{ route('tractor-field-settings.index') }}"
                           class="farm-menu-link {{ request()->routeIs('tractor-field-settings.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🚜</span>
                            <span>{{ __('sidebar.tractor_field_settings') }}</span>
                        </a>
                    @endif

                    @if($showAiSettings && auth()->user()->hasPermission('ai_settings.view'))
                        <a href="{{ route('ai-settings.index') }}"
                           class="farm-menu-link {{ request()->routeIs('ai-settings.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🤖</span>
                            <span>{{ __('sidebar.ai_settings') }}</span>
                        </a>
                    @endif

                    @if($showUsers && auth()->user()->hasPermission('users.view'))
                        <a href="{{ route('users.index') }}"
                           class="farm-menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">👥</span>
                            <span>{{ __('sidebar.users') }}</span>
                        </a>
                    @endif

                    @if($showRoles && auth()->user()->hasPermission('roles.view'))
                        <a href="{{ route('roles.index') }}"
                           class="farm-menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🔐</span>
                            <span>{{ __('sidebar.roles') }}</span>
                        </a>
                    @endif
                </div>
            </details>
        @endif

        @if($hasMasterData)
            <details class="farm-dropdown {{ $masterDataActive ? 'active' : '' }}" {{ $masterDataActive ? 'open' : '' }}>
                <summary>
                    <div class="farm-dropdown-title">
                        <span>{{ __('sidebar.master_data') }}</span>
                        <span class="farm-dropdown-arrow">▼</span>
                    </div>
                </summary>

                <div class="farm-dropdown-body">
                    @if($showTractors && auth()->user()->hasPermission('tractors.view'))
                        <a href="{{ route('tractors.index') }}"
                           class="farm-menu-link {{ request()->routeIs('tractors.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🚜</span>
                            <span>{{ __('sidebar.tractors') }}</span>
                        </a>
                    @endif

                    @if($showDrivers && auth()->user()->hasPermission('drivers.view'))
                        <a href="{{ route('drivers.index') }}"
                           class="farm-menu-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">👷</span>
                            <span>{{ __('sidebar.drivers') }}</span>
                        </a>
                    @endif

                    @if($showZones && auth()->user()->hasPermission('zones.view'))
                        <a href="{{ route('zones.index') }}"
                           class="farm-menu-link {{ request()->routeIs('zones.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">📍</span>
                            <span>{{ __('sidebar.zones') }}</span>
                        </a>
                    @endif

                    @if($showZoneBlocks && auth()->user()->hasPermission('zone_blocks.view'))
                        <a href="{{ route('zone-blocks.index') }}"
                           class="farm-menu-link {{ request()->routeIs('zone-blocks.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🧩</span>
                            <span>{{ __('sidebar.zone_blocks') }}</span>
                        </a>
                    @endif

                    @if($showTaskCategories && auth()->user()->hasPermission('task_categories.view'))
                        <a href="{{ route('task-categories.index') }}"
                           class="farm-menu-link {{ request()->routeIs('task-categories.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🌾</span>
                            <span>{{ __('sidebar.task_categories') }}</span>
                        </a>
                    @endif

                    @if($showPlantingCycleTypes && auth()->user()->hasPermission('planting_cycle_types.view'))
                        <a href="{{ route('planting-cycle-types.index') }}"
                           class="farm-menu-link {{ request()->routeIs('planting-cycle-types.*') ? 'active' : '' }}">
                            <span class="farm-menu-icon">🌱</span>
                            <span>{{ __('sidebar.planting_cycle_types') }}</span>
                        </a>
                    @endif
                </div>
            </details>
        @endif
    </nav>
</aside>