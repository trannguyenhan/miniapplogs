<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - {{ __('app.forbidden_title') }} | MiniAppLogs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background:#0d1117; color:#e6edf3; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .container { text-align:center; max-width:400px; padding:20px; }
        .icon { font-size:64px; color:#30363d; margin-bottom:20px; }
        h1 { font-size:80px; font-weight:700; color:#30363d; margin:0 0 10px; }
        h2 { font-size:20px; font-weight:600; margin-bottom:12px; }
        p { color:#8b949e; font-size:14px; margin-bottom:24px; }
        a { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:#58a6ff; color:#0d1117; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; }
        a:hover { background:#79c0ff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><i class="fas fa-shield-alt"></i></div>
        <h1>403</h1>
        <h2>{{ __('app.forbidden_title') }}</h2>
        <p>{{ __('app.forbidden_description') }}</p>
        <a href="{{ url('/logs') }}"><i class="fas fa-arrow-left"></i> {{ __('app.back_to_logs') }}</a>
    </div>
</body>
</html>
