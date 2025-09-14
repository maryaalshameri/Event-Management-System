<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Ticket_type;
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
            Ticket_type::factory()->regular()->create([
                'event_id' => $event->id,
                'quantity' => rand(50, 200),
                'sold' => rand(0, 50)
            ]);

            Ticket_type::factory()->vip()->create([
                'event_id' => $event->id,
                'quantity' => rand(20, 50),
                'sold' => rand(0, 20)
            ]);

            Ticket_type::factory()->premium()->create([
                'event_id' => $event->id,
                'quantity' => rand(10, 30),
                'sold' => rand(0, 10)
            ]);
        }
    }
}
