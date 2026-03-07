<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private const REGISTRATION_OTP_TTL_MINUTES = 10;
    private const REGISTRATION_OTP_MAX_ATTEMPTS = 5;

    public function register(Request $request): JsonResponse
    {
        $validated = $this->validateRegistrationPayload($request);

        $email = strtolower($validated['email']);
        $now = now();
        $pending = PendingRegistration::query()->where('email', $email)->first();
        if ($pending && $pending->expires_at->isPast()) {
            $pending->delete();
            $pending = null;
        }

        $code = $this->generateVerificationCode();
        $payload = [
            'name' => $validated['name'],
            'email' => $email,
            'password_encrypted' => Crypt::encryptString($validated['password']),
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => $now->copy()->addMinutes(self::REGISTRATION_OTP_TTL_MINUTES),
            'last_sent_at' => $now,
        ];

        try {
            $this->sendRegistrationCodeEmail($validated['email'], $validated['name'], $code);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to send verification email right now. Please try again.',
            ], 500);
        }

        if ($pending) {
            $pending->update($payload);
        } else {
            PendingRegistration::query()->create($payload);
        }

        return response()->json([
            'message' => 'Verification code sent to your email. Enter the code to complete registration.',
            'email' => $email,
            'expires_in_minutes' => self::REGISTRATION_OTP_TTL_MINUTES,
        ]);
    }

    public function verifyRegistrationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = strtolower($validated['email']);
        $pending = PendingRegistration::query()->where('email', $email)->first();
        if (! $pending) {
            return response()->json([
                'message' => 'Verification code expired or not found. Please register again.',
            ], 422);
        }

        if ($pending->expires_at->isPast()) {
            $pending->delete();
            return response()->json([
                'message' => 'Verification code expired or not found. Please register again.',
            ], 422);
        }

        $attempts = (int) $pending->attempts;
        if ($attempts >= self::REGISTRATION_OTP_MAX_ATTEMPTS) {
            $pending->delete();
            return response()->json([
                'message' => 'Too many invalid attempts. Please request a new verification code.',
            ], 422);
        }

        if (! Hash::check((string) $validated['code'], (string) $pending->code_hash)) {
            $pending->attempts = $attempts + 1;
            $pending->save();

            return response()->json([
                'message' => 'Invalid verification code.',
                'remaining_attempts' => max(0, self::REGISTRATION_OTP_MAX_ATTEMPTS - (int) $pending->attempts),
            ], 422);
        }

        if (User::query()->where('email', $email)->exists()) {
            $pending->delete();
            return response()->json([
                'message' => 'An account with this email already exists.',
            ], 422);
        }

        $password = Crypt::decryptString((string) $pending->password_encrypted);
        $user = User::query()->create([
            'name' => (string) $pending->name,
            'email' => $email,
            'password' => $password,
            'role' => 'member',
            'is_active' => true,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $pending->delete();

        return response()->json([
            'message' => 'Registration completed successfully. You can login now.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active']),
        ], 201);
    }

    public function resendRegistrationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (User::query()->where('email', strtolower($validated['email']))->exists()) {
            return response()->json([
                'message' => 'An account with this email already exists.',
            ], 422);
        }

        $email = strtolower($validated['email']);
        $pending = PendingRegistration::query()->where('email', $email)->first();
        if (! $pending || $pending->expires_at->isPast()) {
            if ($pending) {
                $pending->delete();
            }
            return response()->json([
                'message' => 'No pending registration found. Please register again.',
            ], 422);
        }

        $code = $this->generateVerificationCode();
        $name = (string) $pending->name;
        try {
            $this->sendRegistrationCodeEmail($validated['email'], $name, $code);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to resend verification email right now. Please try again.',
            ], 500);
        }

        $pending->update([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::REGISTRATION_OTP_TTL_MINUTES),
            'last_sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'A new verification code was sent to your email.',
            'email' => $email,
            'expires_in_minutes' => self::REGISTRATION_OTP_TTL_MINUTES,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account is deactivated by admin.'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()?->only(['id', 'name', 'email', 'role', 'is_approved', 'is_active']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
        } else {
            $user->tokens()->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * @return array{name: string, email: string, password: string}
     */
    private function validateRegistrationPayload(Request $request): array
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        return $validated;
    }

    private function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendRegistrationCodeEmail(string $email, string $name, string $code): void
    {
        $appName = config('app.name', 'SFACO');
        $subject = "{$appName} registration verification code";
        $recipient = trim($name) !== '' ? $name : 'Member';

        Mail::raw(
            "Hi {$recipient},\n\nYour verification code is: {$code}\n\nThis code expires in ".self::REGISTRATION_OTP_TTL_MINUTES." minutes.",
            function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            }
        );
    }
}
