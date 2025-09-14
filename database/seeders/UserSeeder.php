<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      // إنشاء مدير النظام
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // إنشاء منظمين
        User::factory()->organizer()->count(5)->create();

        // إنشاء حضور
        User::factory()->attendee()->count(20)->create();
    }
}
