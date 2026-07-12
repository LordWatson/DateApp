<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>You're invited to Date Night</title>
    <style>
        body { margin: 0; padding: 0; background-color: #FFF7FB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1F2937; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #ffffff; border-radius: 24px; box-shadow: 0 20px 60px rgba(236,72,153,0.12); overflow: hidden; }
        .header { background: linear-gradient(135deg, #EC4899, #9333EA); padding: 48px 40px; text-align: center; }
        .header-emoji { font-size: 56px; display: block; margin-bottom: 16px; }
        .header h1 { color: #ffffff; font-size: 28px; font-weight: 700; margin: 0 0 8px; }
        .header p { color: rgba(255,255,255,0.85); font-size: 16px; margin: 0; }
        .body { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 16px; }
        .message { font-size: 15px; line-height: 1.7; color: #4B5563; margin-bottom: 32px; }
        .cta { text-align: center; margin-bottom: 32px; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #EC4899, #9333EA); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; padding: 16px 40px; border-radius: 50px; box-shadow: 0 8px 24px rgba(236,72,153,0.35); }
        .divider { border: none; border-top: 1px solid #F3E8FF; margin: 32px 0; }
        .expiry { font-size: 13px; color: #9CA3AF; text-align: center; }
        .footer { padding: 24px 40px; text-align: center; background: #FFF7FB; }
        .footer p { font-size: 12px; color: #9CA3AF; margin: 0; }
        .features { display: flex; gap: 16px; margin-bottom: 32px; }
        .feature { flex: 1; background: #FFF7FB; border-radius: 16px; padding: 16px; text-align: center; }
        .feature-emoji { font-size: 28px; display: block; margin-bottom: 8px; }
        .feature-text { font-size: 13px; color: #6B7280; font-weight: 500; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <span class="header-emoji">💕</span>
                <h1>You're Invited to Date Night</h1>
                <p>Plan more meaningful evenings together</p>
            </div>
            <div class="body">
                <p class="greeting">Hey there! 👋</p>
                <p class="message">
                    <strong>{{ $senderName }}</strong> has invited you to join them on <strong>Date Night</strong> — a beautiful app for couples to share their mood, discover new ideas, and stay connected.
                </p>

                <div class="features">
                    <div class="feature">
                        <span class="feature-emoji">❤️</span>
                        <div class="feature-text">Share your mood</div>
                    </div>
                    <div class="feature">
                        <span class="feature-emoji">✨</span>
                        <div class="feature-text">Discover ideas</div>
                    </div>
                    <div class="feature">
                        <span class="feature-emoji">💕</span>
                        <div class="feature-text">Stay connected</div>
                    </div>
                </div>

                <div class="cta">
                    <a href="{{ $acceptUrl }}">Accept Invitation ❤️</a>
                </div>

                <hr class="divider" />

                <p class="expiry">This invitation expires on {{ $expiresAt }}. If you didn't expect this email, you can safely ignore it.</p>
            </div>
            <div class="footer">
                <p>Date Night &mdash; Made with ❤️ for couples</p>
            </div>
        </div>
    </div>
</body>
</html>
