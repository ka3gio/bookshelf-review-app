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

    /*
     * Temporary routes for features whose controllers are not implemented yet.
     */
    Route::get('/reports', fn() => abort(501))->name('reports.index');

    Route::prefix('reading-plans')->name('reading-plans.')->group(function () {
        Route::get('/', fn() => abort(501))->name('index');
        Route::get('/create', fn() => abort(501))->name('create');
        Route::post('/', fn() => abort(501))->name('store');
        Route::get('/{readingPlan}/edit', fn() => abort(501))->name('edit');
        Route::put('/{readingPlan}', fn() => abort(501))->name('update');
        Route::delete('/{readingPlan}', fn() => abort(501))->name('destroy');
        Route::post('/{readingPlan}/complete', fn() => abort(501))->name('complete');
    });

    Route::get('/notifications', fn() => abort(501))->name('notifications.index');
    Route::post('/notifications/{notification}/read', fn() => abort(501))->name('notifications.read');
});

Route::resource('/books', BookController::class)->only(['index', 'show']);