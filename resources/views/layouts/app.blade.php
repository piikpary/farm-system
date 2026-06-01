<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farm Control System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <style>
        :root {
            --sidebar: #0f172a;
            --sidebar-soft: #111c33;
            --primary: #166534;
            --primary-soft: #dcfce7;
            --danger: #dc2626;
            --warning: #d97706;
            --body: #f4f6f9;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 30px rgba(15, 23, 42, .08);
            --sidebar-width: 270px;
            --sidebar-collapsed-width: 82px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--body);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        .farm-layout {
            min-height: 100vh;
            display: flex;
        }

        .farm-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            color: white;
            position: fixed;
            inset: 0 auto 0 0;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 40;
            border-right: 1px solid rgba(255,255,255,.08);
            transition: width .25s ease, transform .25s ease;
        }

        .farm-brand {
            min-height: 82px;
            padding: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,.08);
            transition: .25s ease;
        }

        .farm-brand-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #22c55e, #166534);
            display: grid;
            place-items: center;
            font-size: 22px;
            box-shadow: 0 10px 25px rgba(34,197,94,.25);
        }

        .farm-brand-title {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1.1;
            white-space: nowrap;
        }

        .farm-brand-sub {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 600;
            white-space: nowrap;
        }

        .farm-main {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            transition: margin-left .25s ease, width .25s ease;
        }

        .farm-topbar {
            height: 68px;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .farm-topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .farm-sidebar-toggle {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border: 1px solid #dbe4ee;
            background: #ffffff;
            color: #166534;
            border-radius: 12px;
            cursor: pointer;
            font-size: 19px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .18s ease;
        }

        .farm-sidebar-toggle:hover {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #15803d;
        }

        .farm-topbar-title {
            font-size: 17px;
            font-weight: 900;
            letter-spacing: -.02em;
        }

        .farm-topbar-sub {
            color: var(--muted);
            font-size: 12px;
            margin-top: 2px;
        }

        .farm-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .farm-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 900;
        }

        .farm-user-info {
            text-align: right;
        }

        .farm-user-name {
            font-size: 14px;
            font-weight: 900;
            line-height: 1.1;
        }

        .farm-user-role {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        .logout-btn {
            border: none;
            background: #fee2e2;
            color: #991b1b;
            font-weight: 900;
            border-radius: 10px;
            padding: 9px 12px;
            cursor: pointer;
            transition: .18s ease;
        }

        .logout-btn:hover {
            background: #fecaca;
        }

        .farm-content {
            min-height: calc(100vh - 68px);
        }

        .sidebar-backdrop {
            display: none;
        }

        .farm-layout.sidebar-collapsed .farm-sidebar {
            width: var(--sidebar-collapsed-width);
        }

        .farm-layout.sidebar-collapsed .farm-main {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }

        .farm-layout.sidebar-collapsed .farm-brand {
            justify-content: center;
            padding-left: 10px;
            padding-right: 10px;
        }

        .farm-layout.sidebar-collapsed .farm-brand-title,
        .farm-layout.sidebar-collapsed .farm-brand-sub,
        .farm-layout.sidebar-collapsed .farm-dropdown-title span:first-child,
        .farm-layout.sidebar-collapsed .farm-dropdown-arrow,
        .farm-layout.sidebar-collapsed .farm-menu-link span:last-child {
            display: none;
        }

        .farm-layout.sidebar-collapsed .farm-dropdown-title {
            justify-content: center;
            margin-left: 8px;
            margin-right: 8px;
            padding-left: 8px;
            padding-right: 8px;
        }

        .farm-layout.sidebar-collapsed .farm-dropdown-title::before {
            content: "•••";
            color: #64748b;
            letter-spacing: 2px;
        }

        .farm-layout.sidebar-collapsed .farm-dropdown-body .farm-menu-link {
            justify-content: center;
            margin-left: 8px;
            margin-right: 8px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .farm-layout.sidebar-collapsed .farm-menu-icon {
            width: 24px;
            font-size: 17px;
        }

        @media (max-width: 1024px) {
            .farm-sidebar {
                width: var(--sidebar-width);
                transform: translateX(-100%);
            }

            .farm-layout.sidebar-collapsed .farm-sidebar {
                width: var(--sidebar-width);
            }

            .farm-sidebar.open {
                transform: translateX(0);
            }

            .farm-main,
            .farm-layout.sidebar-collapsed .farm-main {
                margin-left: 0;
                width: 100%;
            }

            .farm-layout.sidebar-collapsed .farm-brand-title,
            .farm-layout.sidebar-collapsed .farm-brand-sub,
            .farm-layout.sidebar-collapsed .farm-dropdown-title span:first-child,
            .farm-layout.sidebar-collapsed .farm-dropdown-arrow,
            .farm-layout.sidebar-collapsed .farm-menu-link span:last-child {
                display: inline;
            }

            .farm-layout.sidebar-collapsed .farm-brand {
                justify-content: flex-start;
                padding: 20px;
            }

            .farm-layout.sidebar-collapsed .farm-dropdown-title {
                justify-content: space-between;
                margin-left: 10px;
                margin-right: 10px;
                padding-left: 14px;
                padding-right: 14px;
            }

            .farm-layout.sidebar-collapsed .farm-dropdown-title::before {
                content: none;
            }

            .farm-layout.sidebar-collapsed .farm-dropdown-body .farm-menu-link {
                justify-content: flex-start;
                margin-left: 14px;
                margin-right: 10px;
                padding-left: 16px;
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(15,23,42,.45);
                z-index: 35;
            }

            .sidebar-backdrop.show {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .farm-topbar {
                height: auto;
                min-height: 66px;
                padding: 12px 14px;
            }

            .farm-user-info {
                display: none;
            }

            .logout-btn {
                padding: 8px 10px;
                font-size: 12px;
            }

            .farm-topbar-sub {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div id="farmLayout" class="farm-layout">
        @include('layouts.sidebar')

        <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

        <main class="farm-main">
            <header class="farm-topbar">
                <div class="farm-topbar-left">
                    <button type="button" id="sidebarToggleBtn" class="farm-sidebar-toggle" title="Close sidebar">
                        <span id="sidebarToggleIcon">←</span>
                    </button>

                    <div>
                        <div class="farm-topbar-title">{{ __('pages.farm_control_system') }}</div>
                        <div class="farm-topbar-sub">{{ __('pages.system_subtitle') }}</div>
                    </div>
                </div>

                <div class="farm-user">
    <div class="language-switcher topbar-language-switcher">
        <a href="{{ route('lang.switch', ['locale' => 'en']) }}"
        class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">
            EN
        </a>

        <a href="{{ route('lang.switch', ['locale' => 'km']) }}"
        class="lang-btn {{ app()->getLocale() === 'km' ? 'active' : '' }}">
            ខ្មែរ
        </a>
    </div>

    <div class="farm-user-info">
        <div class="farm-user-name">{{ auth()->user()->name ?? 'User' }}</div>
        <div class="farm-user-role">{{ auth()->user()->role->name ?? __('pages.system_user') }}</div>
    </div>

    <div class="farm-user-avatar">
        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">{{ __('pages.logout') }}</button>
    </form>
</div>
            </header>

            <section class="farm-content">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </section>
        </main>
    </div>

    @php
        $aiSetting = \App\Models\AiSetting::where('status', 'active')->first();
    @endphp

    @if(auth()->check() && $aiSetting && $aiSetting->is_enabled && $aiSetting->api_key)
        @include('components.ai-help')
    @endif

    @livewireScripts

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const layout = document.getElementById('farmLayout');
            const sidebar = document.getElementById('farmSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const toggleIcon = document.getElementById('sidebarToggleIcon');

            function isMobile() {
                return window.innerWidth <= 1024;
            }

            function updateIcon() {
                if (isMobile()) {
                    if (sidebar.classList.contains('open')) {
                        toggleIcon.innerText = '✕';
                        toggleBtn.title = 'Close sidebar';
                    } else {
                        toggleIcon.innerText = '☰';
                        toggleBtn.title = 'Open sidebar';
                    }
                    return;
                }

                if (layout.classList.contains('sidebar-collapsed')) {
                    toggleIcon.innerText = '☰';
                    toggleBtn.title = 'Open sidebar';
                } else {
                    toggleIcon.innerText = '←';
                    toggleBtn.title = 'Close sidebar';
                }
            }

            function closeMobileSidebar() {
                sidebar.classList.remove('open');
                backdrop.classList.remove('show');
                updateIcon();
            }

            const savedSidebarState = localStorage.getItem('farm_sidebar_state');

            if (!isMobile() && savedSidebarState === 'collapsed') {
                layout.classList.add('sidebar-collapsed');
            }

            updateIcon();

            toggleBtn.addEventListener('click', function () {
                if (isMobile()) {
                    sidebar.classList.toggle('open');
                    backdrop.classList.toggle('show');
                    updateIcon();
                    return;
                }

                layout.classList.toggle('sidebar-collapsed');

                if (layout.classList.contains('sidebar-collapsed')) {
                    localStorage.setItem('farm_sidebar_state', 'collapsed');
                } else {
                    localStorage.setItem('farm_sidebar_state', 'expanded');
                }

                updateIcon();
            });

            backdrop.addEventListener('click', closeMobileSidebar);

            window.addEventListener('resize', function () {
                if (isMobile()) {
                    layout.classList.remove('sidebar-collapsed');
                    closeMobileSidebar();
                } else {
                    sidebar.classList.remove('open');
                    backdrop.classList.remove('show');

                    const state = localStorage.getItem('farm_sidebar_state');

                    if (state === 'collapsed') {
                        layout.classList.add('sidebar-collapsed');
                    } else {
                        layout.classList.remove('sidebar-collapsed');
                    }

                    updateIcon();
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>