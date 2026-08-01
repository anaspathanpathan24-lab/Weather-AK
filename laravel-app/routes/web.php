<?php

use App\Http\Controllers\AuthPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/forgot-password', [AuthPasswordResetController::class, 'requestForm'])->name('password.request');
Route::post('/forgot-password', [AuthPasswordResetController::class, 'sendOtp'])->name('password.send');
Route::get('/verify-otp', [AuthPasswordResetController::class, 'verifyForm'])->name('password.verify.form');
Route::post('/verify-otp', [AuthPasswordResetController::class, 'verifyOtp'])->name('password.verify');
Route::get('/reset-password', [AuthPasswordResetController::class, 'resetForm'])->name('password.reset.form');
Route::post('/reset-password', [AuthPasswordResetController::class, 'updatePassword'])->name('password.update');
Route::get('/password-changed', [AuthPasswordResetController::class, 'success'])->name('password.success');
