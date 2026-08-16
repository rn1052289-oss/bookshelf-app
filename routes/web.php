<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\RankingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index']);

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');
