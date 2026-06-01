<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm System Login</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 15% 15%, rgba(34, 197, 94, 0.16), transparent 28%),
                radial-gradient(circle at 85% 85%, rgba(250, 204, 21, 0.16), transparent 28%),
                linear-gradient(135deg, #f8fafc 0%, #edf7ef 100%);
            color: #0f172a;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1100px;
            min-height: 650px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.16);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .login-left {
            position: relative;
            padding: 55px;
            color: #ffffff;
            background:
                linear-gradient(135deg, rgba(20, 83, 45, 0.92), rgba(22, 101, 52, 0.78)),
                url("https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1400&q=80");
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(8px);
        }

        .brand h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 850;
            letter-spacing: -0.03em;
        }

        .brand p {
            margin: 4px 0 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.78);
        }

        .hero-content {
            max-width: 560px;
        }

        .hero-content h2 {
            margin: 0;
            font-size: 48px;
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .hero-content p {
            margin: 22px 0 0;
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.84);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .feature-card {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
        }

        .feature-card span {
            display: block;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .feature-card strong {
            display: block;
            font-size: 13px;
            line-height: 1.4;
        }

        .login-right {
            padding: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .mobile-brand {
            display: none;
            text-align: center;
            margin-bottom: 26px;
        }

        .mobile-brand-icon {
            width: 68px;
            height: 68px;
            border-radius: 24px;
            margin: 0 auto 12px;
            background: #dcfce7;
            color: #166534;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
        }

        .login-badge {
            width: fit-content;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .login-title h3 {
            margin: 0;
            color: #0f172a;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .login-title p {
            margin: 12px 0 30px;
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 14px;
            font-weight: 800;
        }

        .input-box {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            z-index: 2;
        }

        .form-input {
            width: 100%;
            height: 54px;
            border-radius: 16px;
            border: 1px solid #dbe3ef;
            background: #ffffff;
            padding: 0 16px 0 48px;
            color: #0f172a;
            font-size: 15px;
            outline: none;
            transition: 0.2s ease;
        }

        .form-input:focus {
            border-color: #166534;
            box-shadow: 0 0 0 4px rgba(22, 101, 52, 0.12);
        }

        .password-input {
            padding-right: 78px;
        }

        .password-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            padding: 7px;
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: 8px 0 26px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #475569;
            font-size: 14px;
            cursor: pointer;
        }

        .remember-label input {
            width: 17px;
            height: 17px;
            accent-color: #166534;
        }

        .forgot-link {
            color: #166534;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            height: 56px;
            border: 0;
            border-radius: 17px;
            background: linear-gradient(135deg, #166534 0%, #15803d 100%);
            color: #ffffff;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 16px 32px rgba(22, 101, 52, 0.28);
            transition: 0.2s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 38px rgba(22, 101, 52, 0.34);
        }

        .support-box {
            margin-top: 24px;
            padding: 16px;
            border-radius: 17px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            text-align: center;
        }

        .support-box strong {
            color: #166534;
        }

        .error-box {
            margin-bottom: 22px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 14px;
        }

        .error-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .status-box {
            margin-bottom: 22px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
            font-size: 14px;
        }

        .input-error {
            margin-top: 7px;
            color: #dc2626;
            font-size: 13px;
        }

        @media (max-width: 1000px) {
            .login-wrapper {
                max-width: 520px;
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .login-left {
                display: none;
            }

            .login-right {
                padding: 42px 30px;
            }

            .mobile-brand {
                display: block;
            }

            .login-badge {
                margin-left: auto;
                margin-right: auto;
            }

            .login-title {
                text-align: center;
            }
        }

        @media (max-width: 520px) {
            .login-page {
                padding: 18px;
            }

            .login-wrapper {
                border-radius: 24px;
            }

            .login-right {
                padding: 32px 22px;
            }

            .login-title h3 {
                font-size: 30px;
            }

            .form-row {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <main class="login-page">
        <section class="login-wrapper">

            <div class="login-left">
                <div class="brand">
                    <div class="brand-icon">🌾</div>

                    <div>
                        <h1>Farm Management System</h1>
                        <p>Smart agriculture operation dashboard</p>
                    </div>
                </div>

                <div class="hero-content">
                    <h2>Manage your farm operations with confidence.</h2>

                    <p>
                        Track farm zones, blocks, tasks, work logs, fuel stock,
                        staff activities, reports, and daily farm operations from one secure dashboard.
                    </p>
                </div>

                <div class="feature-grid">
                    <div class="feature-card">
                        <span>🌱</span>
                        <strong>Zone & Block Management</strong>
                    </div>

                    <div class="feature-card">
                        <span>🚜</span>
                        <strong>Work Logs & Field Tasks</strong>
                    </div>

                    <div class="feature-card">
                        <span>⛽</span>
                        <strong>Fuel Stock Tracking</strong>
                    </div>
                </div>
            </div>

            <div class="login-right">
                <div class="login-card">

                    <div class="mobile-brand">
                        <div class="mobile-brand-icon">🌾</div>
                        <strong>Farm Management System</strong>
                    </div>

                    <div class="login-title">
                        <div class="login-badge">
                            🌿 Secure Farm Login
                        </div>

                        <h3>Welcome back</h3>

                        <p>
                            Sign in to access your farm dashboard and manage daily operations.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="error-box">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="status-box">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>

                            <div class="input-box">
                                <span class="input-icon">✉️</span>

                                <input
                                    id="email"
                                    class="form-input"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="admin@farm.com"
                                >
                            </div>

                            @error('email')
                                <div class="input-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>

                            <div class="input-box">
                                <span class="input-icon">🔒</span>

                                <input
                                    id="password"
                                    class="form-input password-input"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    id="passwordToggle"
                                    onclick="togglePassword()"
                                >
                                    Show
                                </button>
                            </div>

                            @error('password')
                                <div class="input-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <label for="remember_me" class="remember-label">
                                <input
                                    id="remember_me"
                                    type="checkbox"
                                    name="remember"
                                >
                                <span>Remember me</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a class="forgot-link" href="{{ route('password.request') }}">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="login-button">
                            Login to Farm System
                        </button>
                    </form>

                    <div class="support-box">
                        <strong>Farm Admin Portal</strong><br>
                        Only authorized staff can access this system.
                    </div>

                </div>
            </div>

        </section>
    </main>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('passwordToggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.innerText = 'Hide';
            } else {
                passwordInput.type = 'password';
                toggleButton.innerText = 'Show';
            }
        }
    </script>
</body>
</html>