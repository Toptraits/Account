<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrustedDeviceController extends Controller
{

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $deviceToken = $request->cookie('school_os_trusted_device');

        if (!$deviceToken || !preg_match('/^[a-f0-9]{64}$/', $deviceToken)) {
            return response()->json([
                'message' => 'Trusted device not available. Please sign in with OTP.',
            ], 401);
        }

        $email = strtolower(trim($data['email']));

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Trusted device not available. Please sign in with OTP.',
            ], 401);
        }

        $device = $user->trustedDevices()
            ->where('token_hash', hash('sha256', $deviceToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$device) {
            return response()->json([
                'message' => 'Trusted device not available. Please sign in with OTP.',
            ], 401);
        }

        // Rotate the browser credential after each successful trusted login.
        $replacementToken = bin2hex(random_bytes(32));

        $device->update([
            'token_hash' => hash('sha256', $replacementToken),
            'last_used_at' => now(),
            'last_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'name' => substr($request->userAgent() ?: 'Unknown browser', 0, 150),
        ]);

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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'role' => $user->role,
            ],
        ]);

        return $response->withCookie(cookie(
            'school_os_trusted_device',
            $replacementToken,
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));
    }

    public function index(Request $request)
    {
        $devices = $request->user()
            ->trustedDevices()
            ->orderByDesc('last_used_at')
            ->get([
                'id',
                'name',
                'user_agent',
                'last_ip',
                'last_used_at',
                'expires_at',
                'revoked_at',
                'created_at',
            ]);

        return response()->json(['devices' => $devices]);
    }

    public function revoke(Request $request, int $deviceId)
    {
        $device = $request->user()
            ->trustedDevices()
            ->whereKey($deviceId)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Trusted device not found.'], 404);
        }

        if (!$device->revoked_at) {
            $device->update(['revoked_at' => now()]);
        }

        return response()->json(['message' => 'Trusted device revoked.']);
    }
}
