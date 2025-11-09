<?php

use App\Http\Controllers\Auth\GithubController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/github', [GithubController::class, 'redirect']);
Route::get('/auth/github/callback', [GithubController::class, 'callback']);
Route::post('/auth/logout', [GithubController::class, 'logout']);

Route::get('/u/{username}', [PublicProfileController::class, 'show']);

Route::get('/', function () {
    return view('welcome');
});

