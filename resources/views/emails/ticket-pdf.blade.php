<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تذكرة {{ $event->title }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; line-height: 1.6; color: #333; }
        .ticket { border: 2px solid #4F46E5; border-radius: 10px; padding: 20px; margin: 10px; max-width: 400px; }
        .header { background: #4F46E5; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
        .qr-code { text-align: center; margin: 15px 0; }
        .info { margin: 10px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h2>{{ $event->title }}</h2>
        </div>
        
        <div class="info">
            <p><strong>رقم التذكرة:</strong> {{ $ticketCode }}</p>
            <p><strong>اسم الحضور:</strong> {{ $user->name }}</p>
            <p><strong>التاريخ:</strong> {{ $event->start_date->format('Y-m-d') }}</p>
            <p><strong>الوقت:</strong> {{ $event->start_date->format('H:i') }}</p>
            <p><strong>المكان:</strong> {{ $event->location }}</p>
            <p><strong>نوع التذكرة:</strong> {{ $orderItem->ticketType->name }}</p>
        </div>

        <div class="qr-code">
            <!-- سيتم إضافة QR Code في الـ PDF -->
            <p>QR Code: {{ $ticketCode }}</p>
        </div>

        <div class="footer">
            <p>يرجى إحضار هذه التذكرة معك إلى الفعالية</p>
            <p>شكراً لثقتك بنا!</p>
        </div>
    </div>
</body>
</html>