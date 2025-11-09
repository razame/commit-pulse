<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Week in Code - CommitPulse</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 30px 0;
            background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #0ea5e9;
        }
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Your Week in Code</h1>
        <p>Week of {{ $stats->week_start->format('M d') }} - {{ $stats->week_end->format('M d') }}</p>
    </div>
    
    <div class="content">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div class="stat-box" style="text-align: center;">
                <div class="stat-number">{{ $stats->commits_count }}</div>
                <div class="stat-label">Total Commits</div>
            </div>
            <div class="stat-box" style="text-align: center;">
                <div class="stat-number" style="color: #10b981;">{{ number_format($stats->total_additions) }}</div>
                <div class="stat-label">Lines Added</div>
            </div>
            <div class="stat-box" style="text-align: center;">
                <div class="stat-number" style="color: #ef4444;">{{ number_format($stats->total_deletions) }}</div>
                <div class="stat-label">Lines Removed</div>
            </div>
        </div>

        <div class="stat-box" style="margin-top: 20px;">
            <h3 style="margin-top: 0;">Top Repository</h3>
            <p style="font-size: 18px; font-weight: bold;">{{ $stats->top_repo ?? 'N/A' }}</p>
        </div>

        <div class="stat-box">
            <h3 style="margin-top: 0;">Top Language</h3>
            <p style="font-size: 18px; font-weight: bold;">{{ $stats->top_language ?? 'N/A' }}</p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ config('app.url') }}/dashboard" style="display: inline-block; padding: 12px 24px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
                View Full Dashboard
            </a>
        </div>
    </div>

    <div class="footer">
        <p>CommitPulse - Track your coding activity</p>
        <p>You're receiving this because you have weekly digest enabled.</p>
    </div>
</body>
</html>

