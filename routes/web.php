<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BookController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;

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
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('books.index');
    });
    Route::resource('/books', BookController::class)->except(['index', 'show']);
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::resource('reviews', ReviewController::class)->only(['edit', 'update', 'destroy']);
    Route::post('reviews/{review}/like', [ReviewController::class, 'like'])->name('reviews.like');

    Route::get('/favorite', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::resource('genres', GenreController::class);
});

Route::resource('/books', BookController::class)->only(['index', 'show']);
