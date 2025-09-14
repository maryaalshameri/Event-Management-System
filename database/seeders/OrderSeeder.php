<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendees = User::where('role', 'attendee')->get();
        $events = Event::where('status', 'published')->get();

        foreach ($attendees as $attendee) {
            // كل حضر يطلب من 1-3 طلبات
            $orderCount = rand(1, 3);
            
            for ($i = 0; $i < $orderCount; $i++) {
                $event = $events->random();
                
                Order::factory()->create([
                    'user_id' => $attendee->id,
                    'event_id' => $event->id,
                    'status' => rand(0, 1) ? 'confirmed' : 'reserved',
                    'payment_status' => rand(0, 1) ? 'completed' : 'pending'
                ]);
            }
        }

        // إنشاء بعض الطلبات الملغاة
        Order::factory()
            ->cancelled()
            ->count(5)
            ->create();
    }
}
