<?php

namespace Database\Seeders;

use App\Models\MasterAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MasterAccount::firstOrCreate(['id' => 1], ['balance' => 0]);
    }
}
