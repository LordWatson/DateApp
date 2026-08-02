<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>❤️ Your Date Night Is Ready</title>
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
        .summary-box { background: #FFF7FB; border-radius: 16px; padding: 20px; margin-bottom: 24px; border-left: 4px solid #EC4899; }
        .summary-box p { margin: 0; font-size: 15px; line-height: 1.7; color: #4B5563; }
        .score-badge { display: inline-block; background: linear-gradient(135deg, #EC4899, #9333EA); color: #fff; font-size: 22px; font-weight: 700; padding: 12px 28px; border-radius: 50px; margin-bottom: 24px; }
        .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9CA3AF; margin: 0 0 8px; }
        .detail-card { background: #F9FAFB; border-radius: 16px; padding: 16px 20px; margin-bottom: 12px; }
        .detail-card .label { font-size: 12px; color: #9CA3AF; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
        .detail-card .value { font-size: 15px; color: #1F2937; font-weight: 500; line-height: 1.5; }
        .cta { text-align: center; margin: 32px 0; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #EC4899, #9333EA); color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; padding: 16px 40px; border-radius: 50px; box-shadow: 0 8px 24px rgba(236,72,153,0.35); }
        .divider { border: none; border-top: 1px solid #F3E8FF; margin: 32px 0; }
        .footer { padding: 24px 40px; text-align: center; background: #FFF7FB; }
        .footer p { font-size: 12px; color: #9CA3AF; margin: 0; }
        .theme-badge { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #FDF2F8, #F5F3FF); border: 1px solid #F9A8D4; border-radius: 50px; padding: 8px 20px; font-size: 16px; font-weight: 600; color: #9333EA; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <span class="header-emoji">❤️</span>
                <h1>Your Date Night Is Ready</h1>
                <p>Your personalised evening has been crafted just for you</p>
            </div>
            <div class="body">
                <p class="greeting">Hey {{ $recipientName }}! 🥰</p>

                <div style="text-align:center; margin-bottom: 24px;">
                    <div class="theme-badge">{{ $plan->theme_emoji }} {{ $plan->theme }}</div>
                </div>

                <div style="text-align:center; margin-bottom: 24px;">
                    <div class="score-badge">{{ $plan->compatibility_score }}% Compatible ❤️</div>
                </div>

                <div class="summary-box">
                    <p>{{ $plan->summary }}</p>
                </div>

                @if($plan->meal_suggestion)
                <div class="detail-card">
                    <div class="label">🍽️ Meal Suggestion</div>
                    <div class="value">{{ $plan->meal_suggestion }}</div>
                </div>
                @endif


                @if($plan->activity)
                <div class="detail-card">
                    <div class="label">✨ Activity</div>
                    <div class="value">{{ $plan->activity }}</div>
                </div>
                @endif

                @if($plan->conversation_prompt)
                <div class="detail-card">
                    <div class="label">💬 Conversation Prompt</div>
                    <div class="value">{{ $plan->conversation_prompt }}</div>
                </div>
                @endif

                @if($plan->romantic_challenge)
                <div class="detail-card">
                    <div class="label">🌹 Romantic Challenge</div>
                    <div class="value">{{ $plan->romantic_challenge }}</div>
                </div>
                @endif

                <div class="cta">
                    <a href="{{ $resultsUrl }}">View Your Full Plan ✨</a>
                </div>

                <hr class="divider" />

                <p style="font-size: 13px; color: #9CA3AF; text-align: center;">
                    Generated on {{ $plan->created_at->format('F j, Y \a\t g:i A') }}
                </p>
            </div>
            <div class="footer">
                <p>Date Night &mdash; Made with ❤️ for couples</p>
            </div>
        </div>
    </div>
</body>
</html>
