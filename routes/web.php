<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Password reset route (required by Laravel Auth)
Route::get('password/reset/{token}', function (string $token) {
    return response()->json([
        'message' => 'Password reset token: ' . $token,
        'instructions' => 'Use this token with your frontend reset form'
    ]);
})->name('password.reset');
