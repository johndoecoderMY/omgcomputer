<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\CompanyProfile;
use App\Models\Event;
use App\Models\ProjectEstimateItem;
use App\Models\ProjectFeature;
use App\Models\ProjectLink;
use App\Models\ProjectMilestone;
use App\Models\ProjectModule;
use App\Models\ProjectQualityItem;
use App\Models\ProjectRequirement;
use App\Models\ProjectTechnology;
use App\Models\Task;
use App\Observers\ProjectActivityObserver;
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
        foreach ([
            Attachment::class,
            Event::class,
            ProjectEstimateItem::class,
            ProjectFeature::class,
            ProjectLink::class,
            ProjectMilestone::class,
            ProjectModule::class,
            ProjectQualityItem::class,
            ProjectRequirement::class,
            ProjectTechnology::class,
            Task::class,
        ] as $model) {
            $model::observe(ProjectActivityObserver::class);
        }

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
