<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your photos are ready</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="margin:0 0 16px; font-size:22px;">Your photos are ready</h1>
        <p style="margin:0 0 16px;">
            Thank you for your order. Your {{ $count }} full-resolution {{ \Illuminate\Support\Str::plural('photo', $count) }}
            from <strong>{{ $collectionName }}</strong> by {{ $photographerName }} {{ $count === 1 ? 'is' : 'are' }} ready to download.
        </p>
        <p style="margin:24px 0; text-align:center;">
            <a href="{{ $downloadUrl }}" style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; font-weight:bold; padding:12px 24px; border-radius:8px;">Download your photos</a>
        </p>
        <p style="margin:0 0 8px; font-size:14px; color:#4b5563;">
            The link works until {{ $expiresAt }}. You can download each photo{{ $count > 1 ? ', or all of them as one ZIP file' : '' }}, as often as you like until then. Keep a copy somewhere safe.
        </p>
        <p style="margin:0 0 8px; font-size:14px; color:#4b5563;">
            Order total: {{ \App\Support\Currency::format($order->total) }}. Questions about your photos? Reply to this email to reach {{ $photographerName }}.
        </p>
        <p style="margin:24px 0 0; font-size:12px; color:#6b7280; word-break:break-all;">If the button doesn't work, copy this link into your browser: {{ $downloadUrl }}</p>
    </div>
</body>
</html>
