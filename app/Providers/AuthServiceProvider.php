<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Policies\EventPolicy;
use App\Policies\OrderPolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Event::class => EventPolicy::class,
        Order::class => OrderPolicy::class,
        Ticket::class => TicketPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // تعريف الـ Gates الإضافية
        $this->defineGates();
    }

    /**
     * تعريف الـ Gates الإضافية
     */
    protected function defineGates(): void
    {
        // Gate للتحقق إذا كان المستخدم منظم
        \Gate::define('is-organizer', function (User $user) {
            return $user->isOrganizer();
        });

        // Gate للتحقق إذا كان المستخدم مدير
        \Gate::define('is-admin', function (User $user) {
            return $user->isAdmin();
        });

        // Gate للتحقق إذا كان المستخدم حضور
        \Gate::define('is-attendee', function (User $user) {
            return $user->isAttendee();
        });

        // Gate للتحقق إذا كان المستخدم يمكنه إدارة الفعالية
        \Gate::define('manage-event', function (User $user, Event $event) {
            return $user->isAdmin() || $event->organizer_id === $user->id;
        });

        // Gate للتحقق إذا كان المستخدم يمكنه إدارة الطلب
        \Gate::define('manage-order', function (User $user, Order $order) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isOrganizer()) {
                return $order->event->organizer_id === $user->id;
            }

            return $order->user_id === $user->id;
        });
    }
}