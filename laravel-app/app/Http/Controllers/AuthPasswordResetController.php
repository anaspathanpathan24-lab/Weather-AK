<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthPasswordResetController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_LENGTH = 6;

    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email', 'exists:users,email'],
            ], [
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.exists' => 'No account was found for this email address.',
            ]);
        } catch (ValidationException $exception) {
            return $this->respondWithValidationErrors($request, $exception);
        }

        $email = strtolower(trim($data['email']));
        PasswordResetOtp::where('email', $email)->delete();

        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        $record = PasswordResetOtp::create([
            'email' => $email,
            'otp' => Hash::make($otp),
            'verified' => false,
            'expires_at' => $expiresAt,
        ]);

        try {
            Mail::to($email)->send(new PasswordResetOtpMail($otp, $email));
        } catch (\Throwable) {
            $record->delete();

            return $this->jsonResponse(false, 'We could not send the OTP right now. Please try again later.', 500);
        }

        $request->session()->put('password_reset.email', $email);
        $request->session()->put('password_reset.otp_sent', true);
        $request->session()->forget('password_reset.verified');

        return $this->jsonResponse(true, 'A secure OTP has been sent to your email address.');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset.email');

        if (! $email) {
            return redirect()->route('password.request')->withErrors(['email' => 'Please request a new OTP first.']);
        }

        return view('auth.verify-otp', compact('email'));
    }

    public function verifyOtp(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
            ], [
                'otp.regex' => 'OTP must be exactly 6 digits.',
            ]);
        } catch (ValidationException $exception) {
            return $this->respondWithValidationErrors($request, $exception);
        }

        $email = strtolower(trim($data['email']));
        $record = PasswordResetOtp::where('email', $email)->latest()->first();

        if (! $record) {
            return $this->jsonResponse(false, 'No OTP request was found for this email.', 404);
        }

        if ($record->verified) {
            return $this->jsonResponse(false, 'This OTP has already been used. Please request a new one.', 401);
        }

        if ($record->expires_at->isPast()) {
            $record->delete();

            return $this->jsonResponse(false, 'OTP expired. Please request a new one.', 401);
        }

        if (! Hash::check($data['otp'], $record->otp)) {
            return $this->jsonResponse(false, 'OTP is invalid. Please try again.', 401);
        }

        $record->update(['verified' => true]);
        $request->session()->put('password_reset.verified', true);

        return $this->jsonResponse(true, 'OTP verified successfully. You can now reset your password.');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset.email');
        $verified = $request->session()->get('password_reset.verified');

        if (! $email || ! $verified) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', compact('email'));
    }

    public function updatePassword(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/'],
            ], [
                'password.required' => 'Please enter a new password.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.confirmed' => 'Passwords do not match.',
                'password.regex' => 'Password must include uppercase, lowercase, a number, and a special character.',
            ]);
        } catch (ValidationException $exception) {
            return $this->respondWithValidationErrors($request, $exception);
        }

        $email = strtolower(trim($data['email']));
        $record = PasswordResetOtp::where('email', $email)->latest()->first();

        if (! $record || ! $record->verified) {
            return $this->jsonResponse(false, 'Please verify your OTP before changing the password.', 401);
        }

        if ($record->expires_at->isPast()) {
            $record->delete();

            return $this->jsonResponse(false, 'OTP expired. Please request a new one.', 401);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->jsonResponse(false, 'We could not find an account for this email.', 404);
        }

        $user->update(['password' => Hash::make($data['password'])]);
        $record->delete();
        $request->session()->forget(['password_reset']);

        return $this->jsonResponse(true, 'Password changed successfully. You can now sign in.');
    }

    public function success(): View
    {
        return view('auth.password-changed');
    }

    private function jsonResponse(bool $success, string $message, int $status = 200, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private function respondWithValidationErrors(Request $request, ValidationException $exception): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return $this->jsonResponse(false, 'Please correct the highlighted errors.', 422, $exception->errors());
        }

        throw $exception;
    }
}
