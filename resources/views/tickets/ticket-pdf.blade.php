<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>تذكرة {{ $event->title }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/dejavu-sans.ttf') }}') format('truetype');
        }
        
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 20px;
            background: #f8fafc;
        }
        
        .ticket-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border: 2px solid #4f46e5;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #e5e7eb;
        }
        
        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
        }
        
        .ticket-code {
            background: #4f46e5;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 16px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .ticket-info {
            margin: 15px 0;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
            border-right: 4px solid #4f46e5;
        }
        
        .info-label {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #6b7280;
        }
        
        .qr-section {
            text-align: center;
            margin: 25px 0;
            padding: 20px;
            background: #f0f9ff;
            border-radius: 10px;
        }
        
        .qr-placeholder {
            width: 150px;
            height: 150px;
            background: white;
            margin: 0 auto;
            border: 2px dashed #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #6b7280;
        }
        
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px dashed #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table td:first-child {
            font-weight: bold;
            color: #374151;
            width: 30%;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="header">
            <div class="event-title">{{ $event->title }}</div>
            <div style="color: #6b7280; margin-bottom: 15px;">تذكرة حضور الفعالية</div>
            <div class="ticket-code">{{ $ticket->code }}</div>
        </div>
        
        <table>
            <tr>
                <td>نوع التذكرة:</td>
                <td>{{ $ticketType->name }}</td>
            </tr>
            <tr>
                <td>الحالة:</td>
                <td>{{ $ticket->used_at ? 'مستخدمة' : 'نشطة' }}</td>
            </tr>
            <tr>
                <td>الفعالية:</td>
                <td>{{ $event->title }}</td>
            </tr>
            <tr>
                <td>المكان:</td>
                <td>{{ $event->location }}</td>
            </tr>
            <tr>
                <td>التاريخ:</td>
                <td>{{ date('Y-m-d H:i', strtotime($event->start_date)) }}</td>
            </tr>
            <tr>
                <td>الاسم:</td>
                <td>{{ $user->name }}</td>
            </tr>
            <tr>
                <td>البريد:</td>
                <td>{{ $user->email }}</td>
            </tr>
        </table>
        
        <div class="qr-section">
            <div style="font-weight: bold; margin-bottom: 15px; color: #4f46e5;">رمز الاستجابة السريعة</div>
            <div class="qr-placeholder">
                QR Code<br>{{ $ticket->code }}
            </div>
        </div>
        
        <div class="footer">
            <div>يرجى عرض هذه التذكرة عند الدخول للفعالية</div>
            <div>تم الإنشاء في: {{ $generated_at }}</div>
        </div>
    </div>
</body>
</html>