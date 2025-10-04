<?php
// app/Notifications/VerifyEmailNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        return (new MailMessage)
            ->subject('تفعيل حسابك في ' . config('app.name'))
            ->greeting('مرحباً ' . $notifiable->name . '!')
            ->line('شكراً لتسجيلك في منصتنا. يرجى النقر على الزر أدناه لتفعيل حسابك.')
            ->action('تفعيل الحساب', $verificationUrl)
            ->line('إذا لم تقم بإنشاء هذا الحساب، يمكنك تجاهل هذا البريد.')
            ->salutation('مع تحيات فريق ' . config('app.name'));
    }
}