<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use App\Services\MailSettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        RateLimiter::for('repair-tracking', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('repair-billing-code-send', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('repair-billing-code-verify', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('repair-tracking-lookup', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        if (Schema::hasTable('email_server_settings')) {
            app(MailSettingsService::class)->apply();
        }

        View::composer(['welcome', 'customer.contact', 'layouts.guest.footer', 'layouts.app.footer'], function ($view): void {
            $view->with('companyProfile', CompanyProfile::current());
        });
    }
}
