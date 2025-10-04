<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;
    public $verificationCode;

    public function __construct($token)
    {
        $this->token = $token;
        // الحصول على الرمز من الكاش
        $this->verificationCode = Cache::get('password_reset_' . request()->email);
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('رمز إعادة تعيين كلمة المرور - ' . config('app.name'))
            ->greeting('مرحباً ' . $notifiable->name . '!')
            ->line('لقد تلقينا طلباً لإعادة تعيين كلمة المرور لحسابك.')
            ->line('**رمز التحقق الخاص بك هو:**')
            ->line('## ' . $this->verificationCode)
            ->line('استخدم هذا الرمز في الصفحة الحالية لإكمال عملية إعادة التعيين.')
            ->line('ستنتهي صلاحية الرمز خلال 60 ثانية.')
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد.')
            ->salutation('مع تحيات،<br>فريق ' . config('app.name'));
    }
}