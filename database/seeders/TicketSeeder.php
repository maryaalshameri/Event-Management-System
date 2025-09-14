<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order_item;
use App\Models\Ticket;
class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderItems = Order_item::all();

        foreach ($orderItems as $orderItem) {
            // إنشاء تذاكر حسب الكمية في orderItem
            for ($i = 0; $i < $orderItem->quantity; $i++) {
                Ticket::factory()->create([
                    'order_item_id' => $orderItem->id,
                    'used_at' => rand(0, 1) ? now() : null // 50% chance of being used
                ]);
            }
        }
    }
}
