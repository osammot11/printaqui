<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('admin-login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('quote-requests', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);

        RateLimiter::for('order-lookup', fn (Request $request) => [
            Limit::perMinute(8)->by($request->ip()),
            Limit::perHour(40)->by($request->ip()),
        ]);

        RateLimiter::for('checkout-submit', fn (Request $request) => [
            Limit::perMinute(5)->by($request->session()->getId().'|'.$request->ip()),
            Limit::perHour(30)->by($request->ip()),
        ]);

        RateLimiter::for('checkout-coupon', fn (Request $request) => [
            Limit::perMinute(12)->by($request->session()->getId().'|'.$request->ip()),
            Limit::perHour(80)->by($request->ip()),
        ]);

        RateLimiter::for('cart-actions', fn (Request $request) => [
            Limit::perMinute(30)->by($request->session()->getId().'|'.$request->ip()),
            Limit::perHour(200)->by($request->ip()),
        ]);
    }
}
