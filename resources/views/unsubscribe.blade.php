<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Email preferences - {{ config('app.name') }}</title>
    <style>
        body { margin: 0; padding: 40px 16px; background: #fdf2f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2937; }
        main { max-width: 440px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 32px; text-align: center; }
        h1 { margin: 0 0 12px; font-size: 22px; }
        p { margin: 0 0 20px; line-height: 1.6; color: #4b5563; }
        button, a.button { display: inline-block; border: 0; border-radius: 999px; padding: 12px 24px; background: #be185d; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; }
        button:focus-visible, a:focus-visible { outline: 3px solid #1f2937; outline-offset: 2px; }
        .quiet { font-size: 13px; margin: 20px 0 0; }
    </style>
</head>
<body>
    <main>
        @if ($done)
            <h1>You're unsubscribed</h1>
            <p>We will no longer email you about <strong>{{ strtolower($category->label()) }}</strong>. You still get them as notifications in the app.</p>
            <a class="button" href="{{ route('notification-settings.edit') }}">Email settings</a>
        @else
            <h1>Stop these emails?</h1>
            <p>You will no longer get emails about <strong>{{ strtolower($category->label()) }}</strong>. You still get them as notifications in the app.</p>
            <form method="POST" action="{{ $action }}">
                @csrf
                <button type="submit">Stop these emails</button>
            </form>
        @endif
        <p class="quiet">You can change this at any time in Settings, Notifications.</p>
    </main>
</body>
</html>
