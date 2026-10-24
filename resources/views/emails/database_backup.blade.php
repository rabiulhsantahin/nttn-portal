<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Automated Database Backup</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f1f5f9; margin: 0; padding: 24px; }
        .card { max-width: 650px; margin: 0 auto; background-color: #1e293b; border: 1px solid #334155; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); padding: 32px 28px; text-align: left; }
        .logo { font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
        .badge { display: inline-block; background-color: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); font-size: 11px; font-family: monospace; padding: 3px 10px; border-radius: 999px; margin-top: 8px; font-weight: 600; }
        .content { padding: 32px 28px; color: #cbd5e1; font-size: 14px; line-height: 1.6; }
        .meta-table { width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #0f172a; border-radius: 12px; overflow: hidden; border: 1px solid #334155; }
        .meta-table td { padding: 12px 16px; border-bottom: 1px solid #1e293b; font-size: 13px; }
        .meta-table tr:last-child td { border-bottom: none; }
        .meta-label { color: #94a3b8; font-weight: 600; width: 38%; }
        .meta-val { color: #ffffff; font-family: monospace; font-weight: 600; }
        .code-box { background-color: #0b1120; border: 1px solid #334155; border-radius: 10px; padding: 16px; font-family: 'JetBrains Mono', Consolas, monospace; font-size: 12px; color: #38bdf8; overflow-x: auto; margin: 16px 0; }
        .alert-box { background-color: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 14px 18px; border-radius: 8px; margin: 20px 0; font-size: 13px; color: #bfdbfe; }
        .footer { padding: 20px 28px; background-color: #0f172a; border-top: 1px solid #334155; text-align: center; font-size: 11px; color: #64748b; font-family: monospace; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="logo">⚡ {{ $appName }} &bull; Core Operations</div>
            <div style="color: #93c5fd; font-size: 13px; margin-top: 4px;">Automated Daily Database Disaster Recovery Archive</div>
            <span class="badge">&bull; ENCRYPTED &amp; COMPRESSED (GZIP)</span>
        </div>

        <div class="content">
            <p style="margin-top: 0; font-size: 15px; color: #f8fafc;">
                Hello Super Administrator,
            </p>
            <p>
                As part of the daily automated disaster recovery policy, your complete database snapshot has been compiled, compressed, and attached to this email.
            </p>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">Backup File:</td>
                    <td class="meta-val" style="color: #38bdf8;">{{ $backup['filename'] }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Archive Size:</td>
                    <td class="meta-val" style="color: #4ade80;">{{ $backup['size_formatted'] }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Timestamp (UTC):</td>
                    <td class="meta-val">{{ $backup['created_at']->format('Y-m-d H:i:s') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Total Tables:</td>
                    <td class="meta-val">{{ $backup['tables_count'] }} Tables</td>
                </tr>
                <tr>
                    <td class="meta-label">Database Target:</td>
                    <td class="meta-val">{{ $backup['database'] }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Host Node:</td>
                    <td class="meta-val">{{ $serverIp }}</td>
                </tr>
            </table>

            <div class="alert-box">
                <strong>Disaster Recovery Assurance:</strong> Keep this email or download the attached archive to a secure location (e.g. Google Drive, external drive, or offsite vault). If your server or VPS encounters a hardware fault or crash, this file restores 100% of all users, surveys, links, billing, tickets, and operational logs.
            </div>

            <h3 style="color: #ffffff; font-size: 14px; margin-top: 24px; border-bottom: 1px solid #334155; padding-bottom: 8px;">
                How to Restore on Any Server or VPS (রিস্টোর করার নিয়ম)
            </h3>
            
            <p style="font-size: 13px; margin-bottom: 6px;">
                <strong>Step 1:</strong> Decompress the attached <code>.sql.gz</code> file:
            </p>
            <div class="code-box">
gunzip {{ $backup['filename'] }}
            </div>

            <p style="font-size: 13px; margin-bottom: 6px;">
                <strong>Step 2:</strong> Import into MariaDB / MySQL on your new VPS:
            </p>
            <div class="code-box">
mariadb -u root -p nttn_portal &lt; {{ str_replace('.gz', '', $backup['filename']) }}
            </div>

            <p style="font-size: 12px; color: #94a3b8; margin-top: 16px;">
                <em>Note: If creating a fresh database first, run <code>CREATE DATABASE nttn_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code> in MariaDB before importing.</em>
            </p>
        </div>

        <div class="footer">
            {{ $appName }} Enterprise Core &bull; Automated Disaster Recovery Daemon &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
