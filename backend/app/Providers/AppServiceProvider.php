<?php

namespace App\Providers;

use App\Shared\Contracts\PaymentGateway;
use App\Shared\Services\CulqiGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, CulqiGateway::class);
    }

    public function boot(): void
    {
        // Resolve factories by model base name (e.g., Plan → PlanFactory)
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        RateLimiter::for('login', fn (Request $req) =>
            Limit::perMinutes(15, 50)->by($req->ip())
        );

        RateLimiter::for('register', fn (Request $req) =>
            Limit::perHour(100)->by($req->ip())
        );
    }
}
