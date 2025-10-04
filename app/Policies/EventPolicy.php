<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /**
     * التحقق إذا كان المستخدم يمكنه عرض أي فعاليات
     */
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    /**
     * التحقق إذا كان المستخدم يمكنه عرض فعالية محددة
     */
    public function view(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->organizer_id === $user->id;
    }

    /**
     * التحقق إذا كان المستخدم يمكنه إنشاء فعالية
     */
    public function create(User $user): bool
    {
        return $user->isOrganizer();
    }

    /**
     * التحقق إذا كان المستخدم يمكنه تحديث فعالية
     */
    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->organizer_id === $user->id;
    }

    /**
     * التحقق إذا كان المستخدم يمكنه حذف فعالية
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin() || $event->organizer_id === $user->id;
    }

    /**
     * التحقق إذا كان المستخدم يمكنه استعادة فعالية
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    /**
     * التحقق إذا كان المستخدم يمكنه حذف فعالية نهائياً
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }
}