<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Date Night Plan ❤️</title>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #FFF7FB; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1F2937; padding: 40px 20px; }
        .container { max-width: 700px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #EC4899, #9333EA); border-radius: 24px; padding: 48px 40px; text-align: center; margin-bottom: 24px; }
        .header-emoji { font-size: 64px; display: block; margin-bottom: 16px; }
        .header h1 { color: #fff; font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .header .subtitle { color: rgba(255,255,255,0.85); font-size: 16px; }
        .score-row { display: flex; gap: 16px; margin-bottom: 24px; }
        .score-card { flex: 1; background: #fff; border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 4px 20px rgba(236,72,153,0.1); }
        .score-card .value { font-size: 36px; font-weight: 700; background: linear-gradient(135deg, #EC4899, #9333EA); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .score-card .label { font-size: 13px; color: #9CA3AF; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px; }
        .theme-card { background: #fff; border-radius: 20px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(236,72,153,0.1); }
        .theme-card .theme-name { font-size: 22px; font-weight: 700; color: #1F2937; margin-bottom: 12px; }
        .theme-card .summary { font-size: 15px; line-height: 1.7; color: #4B5563; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .detail-card { background: #fff; border-radius: 20px; padding: 20px; box-shadow: 0 4px 20px rgba(236,72,153,0.08); }
        .detail-card .icon { font-size: 28px; margin-bottom: 8px; }
        .detail-card .label { font-size: 11px; color: #9CA3AF; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
        .detail-card .value { font-size: 14px; color: #1F2937; font-weight: 500; line-height: 1.5; }
        .challenge-card { background: linear-gradient(135deg, #FDF2F8, #F5F3FF); border: 1px solid #F9A8D4; border-radius: 20px; padding: 24px; margin-bottom: 24px; }
        .challenge-card .label { font-size: 12px; color: #EC4899; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .challenge-card .value { font-size: 16px; color: #1F2937; font-weight: 600; line-height: 1.5; }
        .partners { display: flex; gap: 16px; margin-bottom: 24px; }
        .partner-chip { flex: 1; background: #fff; border-radius: 16px; padding: 16px; text-align: center; box-shadow: 0 4px 20px rgba(236,72,153,0.08); }
        .partner-chip .avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #EC4899, #9333EA); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: 700; margin: 0 auto 8px; }
        .partner-chip .name { font-size: 14px; font-weight: 600; color: #1F2937; }
        .footer { text-align: center; padding: 24px; color: #9CA3AF; font-size: 13px; }
        .print-btn { position: fixed; bottom: 24px; right: 24px; background: linear-gradient(135deg, #EC4899, #9333EA); color: #fff; border: none; border-radius: 50px; padding: 14px 28px; font-size: 15px; font-weight: 600; cursor: pointer; box-shadow: 0 8px 24px rgba(236,72,153,0.4); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-emoji">{{ $plan->theme_emoji ?? '❤️' }}</span>
            <h1>{{ $plan->theme }}</h1>
            <div class="subtitle">Your personalised Date Night plan</div>
        </div>

        <div class="score-row">
            <div class="score-card">
                <div class="value">{{ $plan->compatibility_score }}%</div>
                <div class="label">Compatibility</div>
            </div>
            <div class="score-card">
                <div class="value">{{ $plan->created_at->format('M j') }}</div>
                <div class="label">Generated</div>
            </div>
        </div>

        @php
            $partnerOne = $plan->partnerOneResponse?->user;
            $partnerTwo = $plan->partnerTwoResponse?->user;
        @endphp

        @if($partnerOne || $partnerTwo)
        <div class="partners">
            @if($partnerOne)
            <div class="partner-chip">
                <div class="avatar">{{ mb_substr($partnerOne->display_name ?? $partnerOne->name, 0, 1) }}</div>
                <div class="name">{{ $partnerOne->display_name ?? $partnerOne->name }}</div>
            </div>
            @endif
            @if($partnerTwo)
            <div class="partner-chip">
                <div class="avatar">{{ mb_substr($partnerTwo->display_name ?? $partnerTwo->name, 0, 1) }}</div>
                <div class="name">{{ $partnerTwo->display_name ?? $partnerTwo->name }}</div>
            </div>
            @endif
        </div>
        @endif

        <div class="theme-card">
            <div class="theme-name">✨ Your Evening</div>
            <div class="summary">{{ $plan->summary }}</div>
        </div>

        <div class="grid">
            @if($plan->meal_suggestion)
            <div class="detail-card">
                <div class="icon">🍽️</div>
                <div class="label">Meal</div>
                <div class="value">{{ $plan->meal_suggestion }}</div>
            </div>
            @endif


            @if($plan->atmosphere)
            <div class="detail-card">
                <div class="icon">🕯️</div>
                <div class="label">Atmosphere</div>
                <div class="value">{{ $plan->atmosphere }}</div>
            </div>
            @endif

            @if($plan->activity)
            <div class="detail-card">
                <div class="icon">✨</div>
                <div class="label">Activity</div>
                <div class="value">{{ $plan->activity }}</div>
            </div>
            @endif

            @if($plan->conversation_prompt)
            <div class="detail-card">
                <div class="icon">💬</div>
                <div class="label">Conversation</div>
                <div class="value">{{ $plan->conversation_prompt }}</div>
            </div>
            @endif
        </div>

        @if($plan->romantic_challenge)
        <div class="challenge-card">
            <div class="label">🌹 Tonight's Romantic Challenge</div>
            <div class="value">{{ $plan->romantic_challenge }}</div>
        </div>
        @endif

        <div class="footer">
            Generated by Date Night ❤️ &mdash; {{ $plan->created_at->format('F j, Y') }}
        </div>
    </div>

    <button class="print-btn no-print" onclick="window.print()">🖨️ Save as PDF</button>
</body>
</html>
