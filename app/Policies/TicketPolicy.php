<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * التحقق إذا كان المستخدم يمكنه عرض أي تذاكر
     */
    public function viewAny(User $user): bool
    {
        return true; // الجميع يمكنهم رؤية التذاكر مع التحقق لاحقاً
    }

    /**
     * التحقق إذا كان المستخدم يمكنه عرض تذكرة محددة
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOrganizer()) {
            return $ticket->orderItem->order->event->organizer_id === $user->id;
        }

        return $ticket->orderItem->order->user_id === $user->id;
    }

    /**
     * التحقق إذا كان المستخدم يمكنه استخدام التذكرة
     */
    public function use(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOrganizer()) {
            return $ticket->orderItem->order->event->organizer_id === $user->id;
        }

        return false; // الحضور لا يمكنهم استخدام التذاكر، فقط المنظمون
    }
}