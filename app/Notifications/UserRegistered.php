<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class UserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('مرحباً بك في ' . config('app.name'))
            ->greeting('مرحباً ' . $this->user->name . '!')
            ->line('شكراً لتسجيلك في منصتنا.')
            ->line('يمكنك الآن استكشاف الفعاليات والبدء في شراء التذاكر.')
            ->action('استكشف الفعاليات', url('/events'))
            ->line('شكراً لاستخدامك منصتنا!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'تم تسجيل حساب جديد: ' . $this->user->name,
            'user_id' => $this->user->id,
            'type' => 'user_registered'
        ];
    }
}