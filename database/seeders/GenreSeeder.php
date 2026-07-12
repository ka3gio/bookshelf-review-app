<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Genre::firstOrCreate([
            'name' => '小説',
        ]);
        Genre::firstOrCreate([
            'name' => 'ビジネス',
        ]);
        Genre::firstOrCreate([
            'name' => '技術書',
        ]);
        Genre::firstOrCreate([
            'name' => '自己啓発',
        ]);
        Genre::firstOrCreate([
            'name' => 'エッセイ',
        ]);
        Genre::firstOrCreate([
            'name' => '歴史',
        ]);
        Genre::firstOrCreate([
            'name' => '科学',
        ]);
        Genre::firstOrCreate([
            'name' => '芸術',
        ]);
        Genre::firstOrCreate([
            'name' => '料理',
        ]);
        Genre::firstOrCreate([
            'name' => '旅行',
        ]);
    }
}
