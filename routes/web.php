<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('books.index');
});
Route::resource('/books', BookController::class)->only(['index', 'show']);
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('books.index');
    });
    Route::resource('/books', BookController::class)->except(['index', 'show']);
    Route::get('/favorite', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorite.toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::resource('genres', GenreController::class);
    Route::resource('reviews', ReviewController::class);
    Route::post('reviews/like', [ReviewController::class, 'like'])->name('reviews.like');
});
