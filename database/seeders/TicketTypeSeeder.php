<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class TicketTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $events = Event::all();

        foreach ($events as $event) {
            // إنشاء أنواع تذاكر مختلفة لكل فعالية
            TicketType::factory()->regular()->create([
                'event_id' => $event->id,
                'quantity' => rand(50, 200),
                'available' => rand(0, 1),
                'description' => 'تذكرة عادية مع دخول قياسي', // نص وليس 0 أو 1                'sold' => rand(0, 50)
            ]);

            TicketType::factory()->vip()->create([
                'event_id' => $event->id,
                'quantity' => rand(20, 50),
                'available' => rand(0, 1),
                'description' => 'تذكرة VIP مع مميزات خاصة',
                'sold' => rand(0, 20)
            ]);

            TicketType::factory()->premium()->create([
                'event_id' => $event->id,
                'quantity' => rand(10, 30),
                'available' => rand(0, 1),
                 'description' => 'تذكرة بريميوم مع أفضل المقاعد',
                'sold' => rand(0, 10)
            ]);
        }
    }
}
