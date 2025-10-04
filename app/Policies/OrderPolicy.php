<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * التحقق إذا كان المستخدم يمكنه عرض أي طلبات
     */
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    /**
     * التحقق إذا كان المستخدم يمكنه عرض طلب محدد
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isOrganizer()) {
            return $order->event->organizer_id === $user->id;
        }

        return $order->user_id === $user->id;
    }

    /**
     * التحقق إذا كان المستخدم يمكنه إنشاء طلب
     */
    public function create(User $user): bool
    {
        return $user->isAttendee();
    }

    /**
     * التحقق إذا كان المستخدم يمكنه تحديث طلب
     */
    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->event->organizer_id === $user->id;
    }

    /**
     * التحقق إذا كان المستخدم يمكنه حذف طلب
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}