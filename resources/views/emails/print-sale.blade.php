<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You sold a print order</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="margin:0 0 16px; font-size:22px;">You sold a print order</h1>
        <p style="margin:0 0 16px;">
            {{ $order->customer_name ?: $order->customer_email }} ordered prints from you. We've sent the order to be printed and posted, and emailed your client a confirmation.
        </p>
        <table role="presentation" style="width:100%; border-collapse:collapse; margin:0 0 16px; font-size:14px;">
            @foreach($items as $item)
                <tr>
                    <td style="padding:8px 0; border-bottom:1px solid #e5e7eb;">
                        {{ $item->quantity }} × {{ $item->product?->name ?? 'Print' }}
                        @if($item->template)
                            <span style="color:#6b7280;">– personalised ({{ $item->template->name }})</span>
                        @endif
                        @if($item->prodigi_attributes)
                            <span style="color:#6b7280;">({{ collect($item->prodigi_attributes)->map(fn ($v, $k) => \App\Models\ProdigiProduct::labelAttribute($k).': '.$v)->implode(', ') }})</span>
                        @endif
                    </td>
                    <td style="padding:8px 0; border-bottom:1px solid #e5e7eb; text-align:right; white-space:nowrap;">{{ \App\Support\Currency::format($item->price * $item->quantity) }}</td>
                </tr>
            @endforeach
            <tr><td style="padding:8px 0; font-weight:bold;">Total paid</td><td style="padding:8px 0; text-align:right; font-weight:bold;">{{ \App\Support\Currency::format($order->total) }}</td></tr>
        </table>
        <p style="margin:0; font-size:14px; color:#4b5563;">
            You receive {{ \App\Support\Currency::format($order->photographer_amount) }} after the print and delivery cost and our fee. Stripe pays it to your bank on your normal payout schedule.
        </p>
    </div>
</body>
</html>
