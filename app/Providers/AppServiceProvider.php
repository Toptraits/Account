<?php

namespace App\Providers;

use App\Http\Responses\PasskeyApiLoginResponse;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            PasskeyLoginResponse::class,
            PasskeyApiLoginResponse::class
        );
    }

    public function boot(): void
    {
        Passkeys::authorizeLoginUsing(
            static fn ($request, User $user): bool =>
                (bool) $user->is_active
        );
    }
}
