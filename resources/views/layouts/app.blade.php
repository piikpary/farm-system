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
            width: 270px;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            color: white;
            position: fixed;
            inset: 0 auto 0 0;
            overflow-y: auto;
            z-index: 40;
            border-right: 1px solid rgba(255,255,255,.08);
        }

        .farm-brand {
            padding: 22px 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .farm-brand-icon {
            width: 42px;
            height: 42px;
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
        }

        .farm-brand-sub {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 4px;
            font-weight: 600;
        }

        .farm-main {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
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

        .farm-mobile-btn {
            display: none;
            border: 1px solid var(--border);
            background: white;
            border-radius: 10px;
            padding: 8px 10px;
            cursor: pointer;
            font-size: 18px;
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

        @media (max-width: 1024px) {
            .farm-sidebar {
                transform: translateX(-100%);
                transition: .25s ease;
            }

            .farm-sidebar.open {
                transform: translateX(0);
            }

            .farm-main {
                margin-left: 0;
                width: 100%;
            }

            .farm-mobile-btn {
                display: inline-flex;
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
        }
    </style>
</head>

<body>
    <div class="farm-layout">
        @include('layouts.sidebar')

        <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="toggleSidebar()"></div>

        <main class="farm-main">
            <header class="farm-topbar">
                <div class="farm-topbar-left">
                    <button type="button" class="farm-mobile-btn" onclick="toggleSidebar()">☰</button>

                    <div>
                        <div class="farm-topbar-title">{{ __('pages.farm_control_system') }}</div>
                        <div class="farm-topbar-sub">{{ __('pages.system_subtitle') }}</div>
                    </div>
                </div>

                <div class="farm-user">
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
        function toggleSidebar() {
            const sidebar = document.getElementById('farmSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');

            sidebar.classList.toggle('open');
            backdrop.classList.toggle('show');
        }
    </script>
</body>
</html>