<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class UserLoggedIn extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database']; // فقط في قاعدة البيانات، ليس بريد
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'تم تسجيل الدخول إلى حسابك',
            'time' => now()->toDateTimeString(),
            'ip' => request()->ip(),
            'type' => 'user_logged_in'
        ];
    }
}