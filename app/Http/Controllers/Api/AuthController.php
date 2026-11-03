<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MicrosoftGraphMailer;
use App\Models\OtpChallenge;
use App\Models\User;
use App\Models\TrustedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AuthController extends Controller
{
    public function sendOtp(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('is_active', true)->first();

        if (!$user) {
            return response()->json(['message' => 'This email address is not registered for access.'], 422);
        }

        $usesEmailOtp = in_array(strtolower((string) $user->role), ['principal', 'admin', 'teacher', 'class_teacher', 'accountant'], true);
        if (!$usesEmailOtp && empty($user->mobile)) {
            return response()->json(['message' => 'This account has no mobile number configured for OTP verification.'], 422);
        }

        $otp = (string) random_int(100000, 999999);
        if ($usesEmailOtp) {
            OtpChallenge::whereRaw('LOWER(email) = ?', [$email])->whereNull('consumed_at')->delete();
            $challenge = OtpChallenge::create([
                'mobile' => null, 'email' => $email, 'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5), 'attempts' => 0,
            ]);
        } else {
            OtpChallenge::where('mobile', $user->mobile)->whereNull('consumed_at')->delete();
            $challenge = OtpChallenge::create([
                'mobile' => $user->mobile, 'email' => null, 'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(5), 'attempts' => 0,
            ]);
        }

        try {
            app(MicrosoftGraphMailer::class)->send(
                $user->email,
                'Your School ERP login OTP',
                "Your School ERP login OTP is: {$otp}\n\nThis code expires in 5 minutes. Do not share it with anyone."
            );
        } catch (Throwable $e) {
            Log::error('School ERP OTP email delivery failed.', [
                'user_id' => $user->id,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            $challenge->delete();
            return response()->json(['message' => 'Unable to send the OTP email. Check mail configuration and try again.'], 500);
        }

        return response()->json(['message' => 'OTP sent to your registered email address.', 'expires_in_seconds' => 300]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
            'trust_device' => ['sometimes', 'boolean'],
        ]);
        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('is_active', true)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP. Request a new code if needed.'], 422);
        }

        $usesEmailOtp = in_array(strtolower((string) $user->role), ['principal', 'admin', 'teacher', 'class_teacher', 'accountant'], true);
        $query = OtpChallenge::whereNull('consumed_at');
        if ($usesEmailOtp) {
            $query->whereRaw('LOWER(email) = ?', [$email]);
        } else {
            if (empty($user->mobile)) {
                return response()->json(['message' => 'Invalid or expired OTP. Request a new code if needed.'], 422);
            }
            $query->where('mobile', $user->mobile);
        }

        $challenge = $query->latest()->first();
        if (!$challenge || $challenge->expires_at->isPast() || $challenge->attempts >= 5
            || !Hash::check($data['otp'], $challenge->otp_hash)) {
            if ($challenge && $challenge->attempts < 5) $challenge->increment('attempts');
            return response()->json(['message' => 'Invalid or expired OTP. Request a new code if needed.'], 422);
        }

        $challenge->update(['consumed_at' => now()]);

        // One-time ticket for the separate browser-session bridge.
        $bridgeTicket = bin2hex(random_bytes(32));
        Cache::put(
            'passkey-bridge:' . hash('sha256', $bridgeTicket),
            $user->id,
            now()->addMinutes(2)
        );

        $token = $user->createToken('school-os-web')->plainTextToken;

        $response = response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'passkey_bridge_ticket' => $bridgeTicket,
            'user' => ['id' => $user->id, 'name' => $user->name, 'mobile' => $user->mobile, 'role' => $user->role],
        ]);

        if ($request->boolean('trust_device')) {
            $deviceToken = bin2hex(random_bytes(32));
            $expiresAt = now()->addDays(30);

            TrustedDevice::create([
                'user_id' => $user->id,
                'name' => substr($request->userAgent() ?: 'Unknown browser', 0, 150),
                'token_hash' => hash('sha256', $deviceToken),
                'user_agent' => $request->userAgent(),
                'last_ip' => $request->ip(),
                'last_used_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            $response->withCookie(cookie(
                'school_os_trusted_device',
                $deviceToken,
                60 * 24 * 30,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        return $response;
    }

    public function logout(Request $request)
    {
        // Revoke the current Sanctum token.
        $request->user()->currentAccessToken()?->delete();

        // Clear the Laravel web session used by passkey authentication.
        if (auth('web')->check()) {
            auth('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()
            ->json(['message' => 'Logged out.'])
            ->withCookie(cookie()->forget(config('session.cookie')));
    }
}
