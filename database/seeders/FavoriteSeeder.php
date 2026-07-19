<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookIds = Book::pluck('id');
        $users = User::all();

        foreach ($users as $user) {
            $user->favoriteBooks()->syncWithoutDetaching(
                $bookIds->shuffle()->take(rand(3, 5))->toArray()
            );
        }
    }
}
