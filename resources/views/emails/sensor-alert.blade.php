<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartAqua Alert</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f6f9; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #0e7490; padding: 24px 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">
                                🐟 SmartAqua
                            </h1>
                            <p style="margin: 4px 0 0; color: #cffafe; font-size: 13px;">
                                Aquaculture Monitoring System
                            </p>
                        </td>
                    </tr>

                    {{-- Alert Badge --}}
                    <tr>
                        <td style="padding: 24px 32px 0;">
                            @php
                                $badgeColor = match($alertType) {
                                    'critical', 'danger' => '#dc2626',
                                    'warning' => '#f59e0b',
                                    'info' => '#3b82f6',
                                    default => '#6b7280',
                                };
                            @endphp
                            <span style="display: inline-block; background-color: {{ $badgeColor }}; color: #ffffff; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                {{ $alertType }}
                            </span>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding: 16px 32px;">
                            <h2 style="margin: 0 0 12px; color: #1e293b; font-size: 20px; font-weight: 600;">
                                {{ $alertTitle }}
                            </h2>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 16px;">
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #f0fdfa; border-left: 4px solid #0e7490; border-radius: 0 4px 4px 0;">
                                        <p style="margin: 0; color: #475569; font-size: 13px; font-weight: 600;">Pond</p>
                                        <p style="margin: 4px 0 0; color: #0f172a; font-size: 15px;">{{ $pond->name }} ({{ $pond->code }})</p>
                                    </td>
                                </tr>
                            </table>

                            <div style="padding: 16px; background-color: #fefce8; border: 1px solid #fde68a; border-radius: 6px;">
                                <p style="margin: 0; color: #1e293b; font-size: 14px; line-height: 1.6;">
                                    {{ $alertMessage }}
                                </p>
                            </div>
                        </td>
                    </tr>

                    {{-- Timestamp --}}
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                ⏱ Alert generated at: {{ $timestamp }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 16px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                                This is an automated alert from SmartAqua Monitoring System.<br>
                                Please log in to your dashboard for more details.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
