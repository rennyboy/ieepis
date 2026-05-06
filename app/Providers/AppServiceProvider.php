<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Observers\TicketObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Force HTTPS URL generation when APP_URL is https, so asset() / route() / url()
        // produce https URLs even when the request scheme appears as http inside the proxy chain.
        // Must run in register() — Filament's PanelProvider captures asset() URLs during boot().
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);

        $this->configureRateLimiters();

        Model::preventLazyLoading(! app()->environment('production'));

        // Super-admin bypass: shortcut every authorization check across the app.
        // Returning null defers to the policy; returning true grants. Only short-circuit
        // when the user actually has the role — otherwise let policies decide.
        Gate::before(function ($user, string $ability) {
            if ($user instanceof \App\Models\User && $user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by(
            $request->ip() . '|' . ($request->input('email', 'guest')),
        ));

        RateLimiter::for('oauth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
