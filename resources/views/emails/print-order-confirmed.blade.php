<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your print order is confirmed</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="margin:0 0 16px; font-size:22px;">Your print order is confirmed</h1>
        <p style="margin:0 0 16px;">
            Thank you for your order from {{ $photographerName }}. Your prints are being made now, and we'll email you again with tracking when they're posted.
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
        <p style="margin:0 0 16px; font-size:14px; color:#4b5563;">
            Delivering to: {{ collect([$order->customer_name, $order->shipping_address_line1, $order->shipping_address_line2, $order->shipping_city, $order->shipping_county, $order->shipping_postcode, \App\Support\Countries::LIST[$order->shipping_country_code] ?? $order->shipping_country_code])->filter()->implode(', ') }}
        </p>
        <p style="margin:0; font-size:14px; color:#4b5563;">
            Order reference {{ $reference }}. Questions about your order? Reply to this email to reach {{ $photographerName }}.
        </p>
    </div>
</body>
</html>
