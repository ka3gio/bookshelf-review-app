<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\GoogleBooksController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', [BookController::class, 'index']);
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');

Route::middleware('auth')->group(function () {
    Route::resource('/books', BookController::class)->except(['index', 'show']);
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/books/isbn/{isbn?}', [GoogleBooksController::class, 'show'])->name('books.isbn.show');

    Route::resource('reviews', ReviewController::class)->only(['edit', 'update', 'destroy']);
    Route::post('reviews/{review}/like', [ReviewController::class, 'like'])->name('reviews.like');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::resource('genres', GenreController::class);

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::resource('/reading-plans', ReadingPlanController::class)->except(['show']);
    Route::post('/reading-plans/{plan}/inprogress', [ReadingPlanController::class, 'inprogress'])->name('reading-plans.inprogress');
    Route::post('/reading-plans/{plan}/complete', [ReadingPlanController::class, 'complete'])->name('reading-plans.complete');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::resource('/books', BookController::class)->only(['index', 'show']);
