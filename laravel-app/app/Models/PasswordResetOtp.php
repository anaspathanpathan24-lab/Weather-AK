<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $table = 'password_reset_otps';

    protected $fillable = ['email', 'otp', 'verified', 'expires_at'];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
