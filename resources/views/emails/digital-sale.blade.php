<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You made a sale</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="margin:0 0 16px; font-size:22px;">You sold {{ $count }} {{ \Illuminate\Support\Str::plural('photo', $count) }}</h1>
        <p style="margin:0 0 16px;">
            {{ $order->customer_name ?: $order->customer_email }} bought {{ $count }} full-resolution {{ \Illuminate\Support\Str::plural('download', $count) }} from <strong>{{ $collectionName }}</strong>.
            We've emailed them their download link.
        </p>
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <tr><td style="padding:4px 0;">Paid by the client</td><td style="padding:4px 0; text-align:right;">{{ \App\Support\Currency::format($order->total) }}</td></tr>
            <tr><td style="padding:4px 0;">Platform fee</td><td style="padding:4px 0; text-align:right;">−{{ \App\Support\Currency::format($order->platform_fee) }}</td></tr>
            <tr><td style="padding:4px 0; font-weight:bold;">Paid to your Stripe account</td><td style="padding:4px 0; text-align:right; font-weight:bold;">{{ \App\Support\Currency::format($order->photographer_amount) }}</td></tr>
        </table>
    </div>
</body>
</html>
