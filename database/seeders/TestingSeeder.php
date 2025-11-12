<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\Update;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat campaign dummy
        $campaigns = Campaign::factory(10)->create();

        // Buat donasi dummy
        Donation::factory(30)->create();

        // Buat update campaign dummy
        Update::factory(20)->create();

        // Buat notifikasi dummy
        Notification::factory(30)->create();
    }
}
