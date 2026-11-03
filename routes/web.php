<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/school-os.html')->name('login');

Route::get('/', function () {
    return view('welcome');
});


// One-time OTP-to-web-session bridge for passkey registration.
\Illuminate\Support\Facades\Route::get('/passkeys/bridge-csrf', function (
    \Illuminate\Http\Request $request
) {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('passkey-bridge-csrf');

\Illuminate\Support\Facades\Route::post('/passkeys/bridge-session', function (
    \Illuminate\Http\Request $request
) {
    $data = $request->validate([
        'ticket' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
    ]);

    $key = 'passkey-bridge:' . hash('sha256', $data['ticket']);
    $userId = \Illuminate\Support\Facades\Cache::pull($key);

    if (!$userId) {
        return response()->json([
            'message' => 'The sign-in ticket is invalid or expired. Please sign in again.',
        ], 401);
    }

    $user = \App\Models\User::query()
        ->whereKey($userId)
        ->where('is_active', true)
        ->first();

    if (!$user) {
        return response()->json([
            'message' => 'This account is not authorized to sign in.',
        ], 403);
    }

    \Illuminate\Support\Facades\Auth::guard('web')->login($user);
    $request->session()->regenerate();

    // Successful OTP verification counts as recent authentication
    // for the passkey-registration password-confirmation middleware.
    $request->session()->put('auth.password_confirmed_at', time());

    return response()->json(['message' => 'Browser session established.']);
})->middleware('throttle:6,1')->name('passkey-bridge-session');


// List only the authenticated user's passkeys.
// Credential IDs and credential data are deliberately excluded.
Route::middleware(['auth:web'])
    ->get('/user/passkeys', function (\Illuminate\Http\Request $request) {
        $user = $request->user('web');

        abort_unless($user && $user->is_active, 403);

        $passkeys = $user->passkeys()
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'name', 'last_used_at', 'created_at']);

        return response()->json([
            'passkeys' => $passkeys->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'last_used_at' => $passkey->last_used_at?->toISOString(),
                'created_at' => $passkey->created_at?->toISOString(),
            ])->values(),
        ]);
    })->name('passkey-management.index');
