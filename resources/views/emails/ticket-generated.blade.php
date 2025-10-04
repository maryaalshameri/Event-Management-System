<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تذاكر فعاليتك جاهزة</title>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px; }
        .ticket-info { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-right: 4px solid #4F46E5; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 تذاكر فعاليتك جاهزة!</h1>
        </div>
        
        <div class="content">
            <p>مرحباً {{ $order->user->name }},</p>
            
            <p>تم تأكيد حجزك بنجاح للفعالية <strong>{{ $order->event->title }}</strong>.</p>
            
            <div class="ticket-info">
                <h3>معلومات التذاكر:</h3>
                <p><strong>رقم الطلب:</strong> #{{ $order->id }}</p>
                <p><strong>تاريخ الفعالية:</strong> {{ $order->event->start_date->format('Y-m-d H:i') }}</p>
                <p><strong>المكان:</strong> {{ $order->event->location }}</p>
                <p><strong>عدد التذاكر:</strong> {{ $order->orderItems->sum('quantity') }}</p>
                <p><strong>المبلغ الإجمالي:</strong> {{ number_format($order->total_amount, 2) }} ر.س</p>
            </div>

            <p>📎 تم إرفاق ملف PDF يحتوي على جميع تذاكرك.</p>
            <p>يرجى إحضار التذاكر معك إلى الفعالية.</p>
            
            <p>شكراً لثقتك بنا، ونتمنى لك تجربة ممتعة!</p>
        </div>
        
        <div class="footer">
            <p>مع تحيات فريق المنظم</p>
        </div>
    </div>
</body>
</html>