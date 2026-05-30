@php
    use App\Models\SidebarMenuSetting;

    $sidebarSettings = SidebarMenuSetting::pluck('is_visible', 'menu_key')->toArray();

    $showTractors = $sidebarSettings['tractors'] ?? false;
    $showDrivers = $sidebarSettings['drivers'] ?? false;
    $showZones = $sidebarSettings['zones'] ?? false;
    $showTaskCategories = $sidebarSettings['task_categories'] ?? false;

    $showFuelReport = $sidebarSettings['fuel_report'] ?? false;
    $showTractorReport = $sidebarSettings['tractor_report'] ?? false;
    $showDriverReport = $sidebarSettings['driver_report'] ?? false;
    $showZoneReport = $sidebarSettings['zone_report'] ?? false;

    // New Sheet2 final report setting
    $showTaskCategorySummaryReport = $sidebarSettings['task_category_summary_report'] ?? false;

    $hasMasterData = $showTractors || $showDrivers || $showZones || $showTaskCategories;

    $hasReports = $showFuelReport
        || $showTractorReport
        || $showDriverReport
        || $showZoneReport
        || $showTaskCategorySummaryReport;
@endphp

<aside id="farmSidebar" class="farm-sidebar">
    <div class="farm-brand">
        <div class="farm-brand-icon">🌿</div>

        <div>
            <div class="farm-brand-title">{{ __('sidebar.farm_control') }}</div>
            <div class="farm-brand-sub">{{ __('sidebar.smart_farm_operation') }}</div>
        </div>
    </div>

    <nav class="farm-menu">
        <div class="farm-menu-section">{{ __('sidebar.main') }}</div>

        <a href="{{ route('dashboard') }}"
           class="farm-menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="farm-menu-icon">🏠</span>
            <span>{{ __('sidebar.dashboard') }}</span>
        </a>

        <div class="farm-menu-section">{{ __('sidebar.farm_operation') }}</div>

        <a href="{{ route('stock-fuel.index') }}"
           class="farm-menu-link {{ request()->routeIs('stock-fuel.*') ? 'active' : '' }}">
            <span class="farm-menu-icon">⛽</span>
            <span>{{ __('sidebar.stock_fuel') }}</span>
        </a>

        <a href="{{ route('farm-work-logs.index') }}"
           class="farm-menu-link {{ request()->routeIs('farm-work-logs.index') ? 'active' : '' }}">
            <span class="farm-menu-icon">📝</span>
            <span>{{ __('sidebar.work_logs') }}</span>
        </a>

        <a href="{{ route('farm-work-logs.create') }}"
           class="farm-menu-link {{ request()->routeIs('farm-work-logs.create') ? 'active' : '' }}">
            <span class="farm-menu-icon">➕</span>
            <span>{{ __('sidebar.add_work_log') }}</span>
        </a>

        @if($hasMasterData)
            <div class="farm-menu-section">{{ __('sidebar.master_data') }}</div>

            @if($showTractors)
                <a href="{{ route('tractors.index') }}"
                   class="farm-menu-link {{ request()->routeIs('tractors.*') ? 'active' : '' }}">
                    <span class="farm-menu-icon">🚜</span>
                    <span>{{ __('sidebar.tractors') }}</span>
                </a>
            @endif

            @if($showDrivers)
                <a href="{{ route('drivers.index') }}"
                   class="farm-menu-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}">
                    <span class="farm-menu-icon">👷</span>
                    <span>{{ __('sidebar.drivers') }}</span>
                </a>
            @endif

            @if($showZones)
                <a href="{{ route('zones.index') }}"
                   class="farm-menu-link {{ request()->routeIs('zones.*') ? 'active' : '' }}">
                    <span class="farm-menu-icon">📍</span>
                    <span>{{ __('sidebar.zones') }}</span>
                </a>
            @endif

            @if($showTaskCategories)
                <a href="{{ route('task-categories.index') }}"
                   class="farm-menu-link {{ request()->routeIs('task-categories.*') ? 'active' : '' }}">
                    <span class="farm-menu-icon">🌾</span>
                    <span>{{ __('sidebar.task_categories') }}</span>
                </a>
            @endif
        @endif

        @if($hasReports)
            <div class="farm-menu-section">{{ __('sidebar.reports') }}</div>

            @if($showTaskCategorySummaryReport)
                <a href="{{ route('reports.task-category-summary') }}"
                class="farm-menu-link {{ request()->routeIs('reports.task-category-summary') ? 'active' : '' }}">
                    <span class="farm-menu-icon">📊</span>
                    <span>{{ __('sidebar.task_category_summary_report') }}</span>
                </a>
            @endif

            @if($showFuelReport)
                <a href="{{ route('reports.fuel') }}"
                   class="farm-menu-link {{ request()->routeIs('reports.fuel') ? 'active' : '' }}">
                    <span class="farm-menu-icon">⛽</span>
                    <span>{{ __('sidebar.fuel_report') }}</span>
                </a>
            @endif

            @if($showTractorReport)
                <a href="{{ route('reports.tractors') }}"
                   class="farm-menu-link {{ request()->routeIs('reports.tractors') ? 'active' : '' }}">
                    <span class="farm-menu-icon">🚜</span>
                    <span>{{ __('sidebar.tractor_report') }}</span>
                </a>
            @endif

            @if($showDriverReport)
                <a href="{{ route('reports.drivers') }}"
                   class="farm-menu-link {{ request()->routeIs('reports.drivers') ? 'active' : '' }}">
                    <span class="farm-menu-icon">👷</span>
                    <span>{{ __('sidebar.driver_report') }}</span>
                </a>
            @endif

            @if($showZoneReport)
                <a href="{{ route('reports.zones') }}"
                   class="farm-menu-link {{ request()->routeIs('reports.zones') ? 'active' : '' }}">
                    <span class="farm-menu-icon">🗺️</span>
                    <span>{{ __('sidebar.zone_report') }}</span>
                </a>
            @endif
        @endif

        <div class="farm-menu-section">{{ __('sidebar.settings') }}</div>

        <a href="{{ route('sidebar-settings.index') }}"
           class="farm-menu-link {{ request()->routeIs('sidebar-settings.*') ? 'active' : '' }}">
            <span class="farm-menu-icon">⚙️</span>
            <span>{{ __('sidebar.sidebar_settings') }}</span>
        </a>

        <a href="{{ route('users.index') }}"
           class="farm-menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <span class="farm-menu-icon">👥</span>
            <span>{{ __('sidebar.users') }}</span>
        </a>

        <a href="{{ route('roles.index') }}"
           class="farm-menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
            <span class="farm-menu-icon">🔐</span>
            <span>{{ __('sidebar.roles') }}</span>
        </a>
    </nav>
</aside>