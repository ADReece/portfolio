<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 40px 30px;
            background: white;
        }
        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }
        .message-box {
            background: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .collection-info {
            background: #f9fafb;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .collection-info h3 {
            margin: 0 0 15px 0;
            color: #667eea;
            font-size: 16px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-weight: 500;
        }
        .info-value {
            color: #111827;
            font-weight: 600;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 25px 0;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
        }
        .button:hover {
            box-shadow: 0 6px 8px rgba(102, 126, 234, 0.4);
        }
        .password-box {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
            text-align: center;
        }
        .password-box h3 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .password {
            font-size: 24px;
            font-weight: 700;
            color: #92400e;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }
        .instructions {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 16px;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
            color: #1e3a8a;
        }
        .footer {
            text-align: center;
            padding: 30px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .header, .content, .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your private gallery</h1>
            <p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">{{ $collection->name }}</p>
        </div>

        <div class="content">
            <p class="greeting">Hi {{ $clientName }},</p>

            <p>{{ $photographerName }} has shared a private photo collection with you.</p>

            @if($customMessage)
                <div class="message-box">
                    <strong>Message from {{ $photographerName }}:</strong>
                    <p style="margin: 10px 0 0 0;">{{ $customMessage }}</p>
                </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $collectionUrl }}" class="button">View my gallery</a>
            </div>

            <div class="collection-info">
                <h3>Collection details</h3>
                @if($collection->event_date)
                    <div class="info-row">
                        <span class="info-label">Event date:</span>
                        <span class="info-value">{{ $collection->event_date->format('F j, Y') }}</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Photographer:</span>
                    <span class="info-value">{{ $photographerName }}</span>
                </div>
            </div>

            <div class="instructions">
                <h3>How to open your gallery</h3>
                <ol>
                    <li>Click <strong>View my gallery</strong> above. The link signs you in to this gallery automatically.</li>
                    <li>View, download and order prints of your photos.</li>
                </ol>
                @if(!empty($linkExpiresAt))
                    <p style="margin: 10px 0 0 0; font-size: 14px;">
                        This link works until {{ $linkExpiresAt->format('F j, Y') }}. After that, use the gallery password from {{ $photographerName }}.
                    </p>
                @endif
            </div>

            <div class="signature">
                <p>Best regards,<br><strong>{{ $photographerName }}</strong></p>
            </div>
        </div>

        <div class="footer">
            <p>This email was sent because {{ $photographerName }} shared a photo collection with you.</p>
            <p style="margin-top: 15px; font-size: 12px; color: #6b7280;">
                If the button doesn't work, copy this address into your browser:<br>
                <span style="word-break: break-all;">{{ $collectionUrl }}</span>
            </p>
        </div>
    </div>
</body>
</html>
