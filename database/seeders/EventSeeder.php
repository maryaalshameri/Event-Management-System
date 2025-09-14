<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\User;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizers = User::where('role', 'organizer')->get();

        if ($organizers->isEmpty()) {
            // إذا لم يكن هناك منظمون، أنشئ بعض المنظمين أولاً
            $organizers = User::factory()->organizer()->count(3)->create();
        }

        // إنشاء فعاليات لكل منظم
        foreach ($organizers as $organizer) {
            Event::factory()
                ->count(rand(2, 5))
                ->create([
                    'organizer_id' => $organizer->id,
                    'status' => 'published'
                ]);

            // إنشاء بعض الفعاليات كمسودة
            Event::factory()
                ->count(rand(1, 2))
                ->draft()
                ->create(['organizer_id' => $organizer->id]);
        }

        // إنشاء بعض الفعاليات الملغاة والمكتملة
        Event::factory()
            ->cancelled()
            ->count(2)
            ->create();

        Event::factory()
            ->completed()
            ->count(3)
            ->create();
    }
}
