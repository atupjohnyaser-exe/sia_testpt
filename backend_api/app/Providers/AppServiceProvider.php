<?php

namespace App\Providers;

use App\Models\CaseNote;
use App\Models\Infraction;
use App\Models\Manhunt;
use App\Models\Sighting;
use App\Models\StatusChange;
use App\Models\Subject;
use App\Observers\AuditObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Route ids are always numbers. This also stops /subjects/export
        // from being read as a subject id.
        foreach (['subject', 'infraction', 'manhunt', 'note'] as $parameter) {
            Route::pattern($parameter, '[0-9]+');
        }

        $this->registerAuditObservers();
        $this->registerRateLimiters();
    }

    private function registerAuditObservers(): void
    {
        foreach ([Subject::class, Infraction::class, Manhunt::class, CaseNote::class, Sighting::class, StatusChange::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('badge_id')).'|'.$request->ip()),
            Limit::perMinute(20)->by((string) $request->ip()),
        ]);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
    }
}
