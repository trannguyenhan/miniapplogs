<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('app.login_title') }} | MiniAppLogs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            background: #0F172A;
            color: #F8FAFC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 60px; height: 60px;
            background: #22C55E;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #0F172A;
            margin-bottom: 14px;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
        }

        .login-logo h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .login-logo p {
            font-size: 13px;
            color: #CBD5E1;
            margin-top: 4px;
        }

        .login-card {
            background: #1E293B;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
        }

        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #CBD5E1;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 13px;
        }

        .form-control {
            width: 100%;
            background: #0F172A;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 10px 12px 10px 36px;
            color: #F8FAFC;
            font-size: 14px;
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .form-control:focus {
            border-color: #22C55E;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }

        .form-control::placeholder { color: #94A3B8; }

        .btn-login {
            width: 100%;
            background: #22C55E;
            border: 1px solid #16A34A;
            border-radius: 6px;
            padding: 11px;
            color: #0F172A;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Space Grotesk', 'DM Sans', sans-serif;
            transition: background 0.15s ease;
            margin-top: 8px;
            letter-spacing: 0.3px;
        }

        .btn-login:hover { background: #16A34A; }
        .btn-login:active { opacity: 0.9; }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #EF4444;
            border-radius: 8px;
            padding: 10px 14px;
            color: #EF4444;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #94A3B8;
        }

        /* Lang switcher on login */
        .lang-switch {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 24px;
        }
        .lang-switch a {
            padding: 3px 10px;
            border-radius: 4px;
            border: 1px solid #334155;
            color: #94A3B8;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s;
        }
        .lang-switch a:hover { background: #334155; color: #F8FAFC; }
        .lang-switch a.active { background: #22C55E; color: #0F172A; border-color: #22C55E; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="logo-icon"><i class="fas fa-terminal"></i></div>
            <h1>MiniAppLogs</h1>
            <p>{{ __('app.login_subtitle') }}</p>
        </div>

        {{-- Language switcher (hidden – uncomment to enable multi-lang)
        <div class="lang-switch">
            <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
            <a href="{{ route('lang.switch', 'vi') }}" class="{{ app()->getLocale() === 'vi' ? 'active' : '' }}">VI</a>
        </div>
        --}}

        <div class="login-card">
            @if($errors->has('email') || session('status'))
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first('email') ?? session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">{{ __('app.email') }}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="admin@example.com"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            autofocus
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">{{ __('app.password') }}</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt" style="margin-right:6px;"></i>
                    {{ __('app.btn_login') }}
                </button>
            </form>
        </div>

        <div class="footer-note">
            MiniAppLogs &copy; {{ date('Y') }} · {{ __('app.login_footer') }}
        </div>
    </div>
</body>
</html>
