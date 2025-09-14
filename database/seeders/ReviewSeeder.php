<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Review;
use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $attendees = User::where('role', 'attendee')->get();
        $events = Event::where('status', 'completed')->get();

        foreach ($events as $event) {
            // 30-70% من الحضور يتركون تقييمات للفعاليات المكتملة
            $reviewCount = rand(
                ceil($attendees->count() * 0.3),
                ceil($attendees->count() * 0.7)
            );

            $eventAttendees = $attendees->random($reviewCount);

            foreach ($eventAttendees as $attendee) {
                Review::factory()->create([
                    'user_id' => $attendee->id,
                    'event_id' => $event->id,
                    'rating' => rand(3, 5), // تقييمات إيجابية بشكل عام
                    'comment' => rand(0, 1) ? null : 'تجربة رائعة!'
                ]);
            }
        }
    }
}
