<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketType;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            $ticketTypes = TicketType::where('event_id', $order->event_id)->get();
            
            if ($ticketTypes->isNotEmpty()) {
                // إضافة 1-3 أنواع تذاكر لكل طلب
                $itemCount = rand(1, 3);
                
                for ($i = 0; $i < $itemCount; $i++) {
                    $ticketType = $ticketTypes->random();
                    
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'ticket_type_id' => $ticketType->id,
                        'quantity' => rand(1, 4),
                        'price' => $ticketType->price
                    ]);
                }
            }
        }
    }
}
