<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order_item;
use App\Models\Ticket_type;
class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            $ticketTypes = Ticket_type::where('event_id', $order->event_id)->get();
            
            if ($ticketTypes->isNotEmpty()) {
                // إضافة 1-3 أنواع تذاكر لكل طلب
                $itemCount = rand(1, 3);
                
                for ($i = 0; $i < $itemCount; $i++) {
                    $ticketType = $ticketTypes->random();
                    
                    Order_item::factory()->create([
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
