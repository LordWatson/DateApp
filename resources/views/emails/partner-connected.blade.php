<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>You're connected on Date Night!</title>
    <style>
        body { margin: 0; padding: 0; background-color: #FFF7FB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1F2937; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #ffffff; border-radius: 24px; box-shadow: 0 20px 60px rgba(236,72,153,0.12); overflow: hidden; }
        .header { background: linear-gradient(135deg, #EC4899, #9333EA); padding: 48px 40px; text-align: center; }
        .header-emoji { font-size: 64px; display: block; margin-bottom: 16px; }
        .header h1 { color: #ffffff; font-size: 28px; font-weight: 700; margin: 0 0 8px; }
        .header p { color: rgba(255,255,255,0.85); font-size: 16px; margin: 0; }
        .body { padding: 40px; text-align: center; }
        .message { font-size: 16px; line-height: 1.7; color: #4B5563; margin-bottom: 32px; }
        .partner-badge { display: inline-block; background: linear-gradient(135deg, #FFF7FB, #F3E8FF); border: 2px solid #EC4899; border-radius: 50px; padding: 12px 28px; font-size: 18px; font-weight: 700; color: #EC4899; margin-bottom: 32px; }
        .cta { margin-bottom: 32px; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #EC4899, #9333EA); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; padding: 16px 40px; border-radius: 50px; box-shadow: 0 8px 24px rgba(236,72,153,0.35); }
        .footer { padding: 24px 40px; text-align: center; background: #FFF7FB; }
        .footer p { font-size: 12px; color: #9CA3AF; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <span class="header-emoji">❤️</span>
                <h1>You're Connected!</h1>
                <p>Your Date Night journey begins now</p>
            </div>
            <div class="body">
                <p class="message">
                    Wonderful news, <strong>{{ $userName }}</strong>! You and <strong>{{ $partnerName }}</strong> are now connected on Date Night. Start planning your perfect evening together.
                </p>

                <div class="partner-badge">💕 {{ $partnerName }}</div>

                <div class="cta">
                    <a href="{{ $dashboardUrl }}">Go to Dashboard ❤️</a>
                </div>
            </div>
            <div class="footer">
                <p>Date Night &mdash; Made with ❤️ for couples</p>
            </div>
        </div>
    </div>
</body>
</html>
