<?php

namespace Tests\Feature;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_flow_works_with_otp_and_json_responses(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $otpRecord = PasswordResetOtp::where('email', 'user@example.com')->latest()->first();

        $this->assertNotNull($otpRecord);
        $this->assertSame(6, mb_strlen($otpRecord->otp));

        $verifyResponse = $this->postJson('/verify-otp', [
            'email' => 'user@example.com',
            'otp' => $otpRecord->otp,
        ]);

        $verifyResponse->assertOk()
            ->assertJsonPath('success', true);

        $resetResponse = $this->postJson('/reset-password', [
            'email' => 'user@example.com',
            'password' => 'NewStrong123!',
            'password_confirmation' => 'NewStrong123!',
        ]);

        $resetResponse->assertOk()
            ->assertJsonPath('success', true);

        $user = User::where('email', 'user@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('NewStrong123!', $user->password));
        $this->assertDatabaseMissing('password_reset_otps', ['email' => 'user@example.com']);
    }
}
