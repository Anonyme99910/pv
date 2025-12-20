<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOMA PV Alert</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f59e0b;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #f59e0b;
        }
        .logo span {
            color: #333;
        }
        .alert-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .alert-critical {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .alert-high {
            background-color: #fef3c7;
            color: #d97706;
        }
        .alert-medium {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .alert-low {
            background-color: #d1fae5;
            color: #059669;
        }
        .content {
            padding: 20px 0;
        }
        .message {
            background-color: #f8fafc;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            white-space: pre-line;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .data-table th,
        .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #f59e0b;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #d97706;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">SOMA <span>PV</span></div>
            <p style="color: #6b7280; margin: 5px 0;">Solar Panel Monitoring System</p>
            @if(isset($data['severity']))
                <span class="alert-badge alert-{{ $data['severity'] ?? 'medium' }}">
                    {{ $data['severity'] ?? 'Alert' }}
                </span>
            @endif
        </div>

        <div class="content">
            <div class="message">{{ $alertMessage }}</div>

            @if(!empty($data) && !isset($data['test']))
                <table class="data-table">
                    <tbody>
                        @foreach($data as $key => $value)
                            @if(!is_array($value) && !is_object($value))
                                <tr>
                                    <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                    <td>{{ $value }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button">View Dashboard</a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from SOMA PV Monitoring System.</p>
            <p>© {{ date('Y') }} SOMA PV. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
