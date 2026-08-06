<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthPasswordResetController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Authentication Routes
|
*/

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/session', [AuthController::class, 'session'])->name('session');

/*
|--------------------------------------------------------------------------
| Password Reset Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/forgot-password', [AuthPasswordResetController::class, 'requestForm'])
    ->name('password.request');

Route::post('/forgot-password', [AuthPasswordResetController::class, 'sendOtp'])
    ->name('password.send');

Route::get('/verify-otp', [AuthPasswordResetController::class, 'verifyForm'])
    ->name('password.verify.form');

Route::post('/verify-otp', [AuthPasswordResetController::class, 'verifyOtp'])
    ->name('password.verify');

Route::get('/reset-password', [AuthPasswordResetController::class, 'resetForm'])
    ->name('password.reset.form');

Route::post('/reset-password', [AuthPasswordResetController::class, 'updatePassword'])
    ->name('password.update');

Route::get('/password-changed', [AuthPasswordResetController::class, 'success'])
    ->name('password.success');

/*
|--------------------------------------------------------------------------
| CSRF Token Route
|--------------------------------------------------------------------------
|
*/

Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
});

/*
|--------------------------------------------------------------------------
| Home Route
|--------------------------------------------------------------------------
|
*/

Route::get('/', function () {
    return view('welcome');
});