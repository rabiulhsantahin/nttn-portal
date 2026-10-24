<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fault Escalation — Trouble Ticket #{{ $ticket->ticket_no }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b1120; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e2e8f0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0b1120; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 650px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e11d48 0%, #be123c 50%, #881337 100%); padding: 24px 30px; text-align: left;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: #ffe4e6; margin-bottom: 4px;">
                                            🚨 NTTN CARRIER FAULT ESCALATION NOTICE
                                        </div>
                                        <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff; line-height: 1.3;">
                                            Trouble Ticket #{{ $ticket->ticket_no }}
                                        </h1>
                                        <div style="font-size: 13px; color: #fecdd3; margin-top: 4px;">
                                            {{ $appName }} &bull; Urgent NOC Attention Requested
                                        </div>
                                    </td>
                                    <td align="right" valign="top">
                                        <span style="display: inline-block; padding: 6px 14px; border-radius: 9999px; background-color: #ffffff; color: #be123c; font-size: 12px; font-weight: 900; letter-spacing: 1px; font-family: monospace;">
                                            {{ strtoupper($ticket->priority) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 28px 30px;">

                            <!-- Attention Banner -->
                            <div style="background-color: #1e1b4b; border-left: 4px solid #818cf8; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px;">
                                <p style="margin: 0; font-size: 13px; color: #c7d2fe; line-height: 1.5;">
                                    Dear <strong>{{ $ticket->nttn_provider ?: ($link->nttn_provider ?? 'NTTN Carrier') }}</strong> NOC Team,<br>
                                    A network incident has been escalated regarding the circuit provisioned on your optical network infrastructure. Please treat this fault ticket as high priority and acknowledge with your internal TT reference and ETR (Estimated Time of Restoration).
                                </p>
                            </div>

                            <!-- NTTN Link & Circuit Identification Card -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #020617; border: 1px solid #334155; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
                                <tr>
                                    <td colspan="2" style="background-color: #1e293b; padding: 10px 16px; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #38bdf8;">
                                        📡 NTTN Carrier &amp; Circuit Identification
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #94a3b8; width: 38%;">
                                        NTTN Provider:
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 14px; font-weight: 700; color: #38bdf8;">
                                        {{ $ticket->nttn_provider ?: ($link->nttn_provider ?? 'N/A') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #94a3b8;">
                                        <strong>NTTN Company Link ID:</strong>
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 16px; font-weight: 900; color: #f59e0b; font-family: monospace;">
                                        {{ $ticket->nttn_link_id ?: ($link->nttn_link_id ?: 'N/A') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #94a3b8;">
                                        Customer / End Site:
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; font-weight: 600; color: #f1f5f9;">
                                        {{ $link->customer->name ?? 'N/A' }} 
                                        @if(!empty($link->customer->district))
                                            ({{ $link->customer->district }}{{ !empty($link->customer->address) ? ' — ' . $link->customer->address : '' }})
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #94a3b8;">
                                        Circuit Capacity:
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #34d399; font-family: monospace;">
                                        {{ $link->bandwidth_mbps }} Mbps
                                        @if(!empty($link->vlan))
                                            &bull; VLAN: {{ $link->vlan }}
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- Fault Incident Details -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #020617; border: 1px solid #334155; border-radius: 12px; margin-bottom: 24px; overflow: hidden;">
                                <tr>
                                    <td colspan="2" style="background-color: #1e293b; padding: 10px 16px; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #f43f5e;">
                                        ⚠️ Fault Incident Specifications
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #94a3b8; width: 38%;">
                                        Classification:
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 14px; font-weight: 700; color: #f43f5e;">
                                        {{ $ticket->type }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #94a3b8;">
                                        Incident Severity:
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; font-weight: 800; color: #ffffff;">
                                        {{ $ticket->priority }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #94a3b8;">
                                        Reported Timestamp:
                                    </td>
                                    <td style="padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; color: #e2e8f0; font-family: monospace;">
                                        {{ $ticket->created_at ? $ticket->created_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A') }} (BST)
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #94a3b8;">
                                        Escalated By:
                                    </td>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #e2e8f0;">
                                        <strong>{{ $ticket->creator->name ?? 'NOC Team' }}</strong>
                                        @if(!empty($ticket->creator->phone))
                                            &bull; {{ $ticket->creator->phone }}
                                        @endif
                                        @if(!empty($ticket->creator->email))
                                            &bull; {{ $ticket->creator->email }}
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- Detailed Description -->
                            <div style="background-color: #020617; border: 1px solid #334155; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                                <div style="font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">
                                    📝 Detailed Incident Description &amp; Technical Findings
                                </div>
                                <div style="font-size: 14px; line-height: 1.6; color: #f8fafc; white-space: pre-wrap; font-family: monospace; background-color: #0f172a; padding: 14px; border-radius: 8px; border: 1px solid #1e293b;">{{ $ticket->description }}</div>
                            </div>

                            @if($ticket->attachment_path)
                                <div style="background-color: #020617; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px;">
                                    <span style="font-size: 13px; color: #38bdf8;">📎 Photographic / trace evidence is attached to this email.</span>
                                </div>
                            @endif

                            <!-- Action Steps -->
                            <div style="background-color: #092e20; border: 1px solid #059669; border-radius: 10px; padding: 16px 18px;">
                                <div style="font-size: 12px; font-weight: 800; color: #34d399; margin-bottom: 6px;">
                                    REQUIRED ACTION FROM NTTN CARRIER NOC:
                                </div>
                                <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #a7f3d0; line-height: 1.6;">
                                    <li>Check optical power, OTDR distance, and fiber continuity for NTTN Link ID: <strong>{{ $ticket->nttn_link_id ?: ($link->nttn_link_id ?: 'N/A') }}</strong>.</li>
                                    <li>Dispatch fiber jointing / transmission field team if physical cut or high loss is detected.</li>
                                    <li>Reply to this email with your internal NTTN Fault Ticket # and ETR.</li>
                                </ul>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #020617; padding: 18px 30px; border-top: 1px solid #1e293b; text-align: center; font-size: 11px; color: #64748b; line-height: 1.5;">
                            This is an automated network fault escalation dispatched from <strong>{{ $appName }}</strong>.<br>
                            For live status, please liaise with the reporting NOC contact.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
