<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('users.index');
});

Route::resource('users', UserController::class);
Route::put('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
