<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

class PasskeyApiLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): JsonResponse
    {
        $user = $request->user('web');

        if (! $user || ! $user->is_active) {
            auth('web')->logout();

            return response()->json([
                'message' => 'This account is not authorized to sign in.',
            ], 403);
        }

        $token = $user->createToken('school-os-web')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'role' => $user->role,
            ],
        ]);
    }
}
