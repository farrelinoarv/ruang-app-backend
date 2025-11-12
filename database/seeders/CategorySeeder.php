<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Kemanusiaan',
            'Pendidikan',
            'Lingkungan',
            'Teknologi',
            'Sosial',
            'Kesehatan'
        ];

        foreach ($categories as $name) {
            Category::updateOrCreate(['name' => $name, 'slug' => \Str::slug($name)]);
        }
    }
}
