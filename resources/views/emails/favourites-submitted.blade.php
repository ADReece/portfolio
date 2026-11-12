<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>New favourites</title></head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="margin:0 0 16px; font-size:22px;">{{ $list->clientLabel() }} {{ $updated ? 'updated their' : 'sent their' }} favourites</h1>
        <p style="margin:0 0 16px;">{{ $count }} {{ \Illuminate\Support\Str::plural('photo', $count) }} from <strong>{{ $collection->name }}</strong>.</p>
        @if($list->note)
            <p style="margin:0 0 16px; padding:12px 16px; background:#f9fafb; border-left:4px solid #d1d5db; white-space:pre-line;">{{ $list->note }}</p>
        @endif
        <p style="margin:24px 0; text-align:center;">
            <a href="{{ $url }}" style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; font-weight:bold; padding:12px 24px; border-radius:8px;">See their favourites</a>
        </p>
        @if($list->email)
            <p style="margin:0; font-size:14px; color:#4b5563;">Reply to this email to answer {{ $list->name ?: $list->email }}.</p>
        @endif
    </div>
</body>
</html>
