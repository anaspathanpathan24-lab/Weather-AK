<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthPasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'This email is not registered.',
        ]);

        $request->session()->forget('password_reset');
        PasswordResetOtp::where('email', $data['email'])->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        PasswordResetOtp::create([
            'email' => $data['email'],
            'otp' => $otp,
            'verified' => false,
            'expires_at' => $expiresAt,
        ]);

        Mail::to($data['email'])->send(new PasswordResetOtpMail($otp, $data['email']));

        $request->session()->put('password_reset', [
            'email' => $data['email'],
            'otp_sent' => true,
        ]);

        return redirect()->route('password.verify.form')->with('success', 'OTP sent to your email address.');
    }

    public function verifyForm(Request $request)
    {
        $email = $request->session()->get('password_reset.email');

        if (!$email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Please request a new OTP first.']);
        }

        return view('auth.verify-otp', compact('email'));
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $record = PasswordResetOtp::where('email', $data['email'])->latest()->first();

        if (!$record) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP.']);
        }

        if ($record->expires_at->isPast()) {
            $record->delete();
            throw ValidationException::withMessages(['otp' => 'OTP expired. Please request a new one.']);
        }

        if ($record->otp !== $data['otp']) {
            throw ValidationException::withMessages(['otp' => 'Invalid OTP.']);
        }

        $record->update(['verified' => true]);
        $request->session()->put('password_reset.verified', true);

        return redirect()->route('password.reset.form');
    }

    public function resetForm(Request $request)
    {
        $email = $request->session()->get('password_reset.email');
        $verified = $request->session()->get('password_reset.verified');

        if (!$email || !$verified) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', compact('email'));
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/'],
        ], [
            'password.regex' => 'Password must include uppercase, lowercase, a number, and a special character.',
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => Hash::make($data['password'])]);

        PasswordResetOtp::where('email', $data['email'])->delete();
        $request->session()->forget(['password_reset']);

        return redirect()->route('password.success');
    }

    public function success()
    {
        return view('auth.password-changed');
    }
}
