<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>إعادة تعيين كلمة المرور</title>
</head>
<body>
    <h2>مرحباً {{ $user->name }}!</h2>
    <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور لحسابك.</p>
    <p>
        <a href="{{ url('reset-password?token='.$token.'&email='.$user->email) }}" 
           style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
           إعادة تعيين كلمة المرور
        </a>
    </p>
    <p>إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد.</p>
    <p>مع التحية،<br>فريق التطبيق</p>
</body>
</html>