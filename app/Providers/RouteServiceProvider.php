<?php

namespace App\Providers;

use App\Models\{
    Project,
    Invitation,
};

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        Route::bind('invitation', function ($value) {
            return Invitation::where('userable_type', 'App\Models\Manager')
                ->where('token', $value)
                ->firstOrFail();
        });

        Route::bind('invitationProject', function ($value) {
            $user = auth()->user();
            if ($user && $user->hasRole('Super Admin')) {
                return Project::withoutGlobalScopes()->findOrFail($value);
            }

            return Project::withoutGlobalScopes()
                ->where('id', $value)
                ->whereHas('invitations', function ($query) {
                    return $query->where('userable_id', auth()->id())
                        ->where('userable_type', 'App\Models\Manager');
                })
                ->orWhereHas('members', function ($query) {
                    return $query->where('userable_id', auth()->id())
                        ->where('userable_type', 'App\Models\Manager');
                })
                ->firstOrFail();
        });

        $this->routes(function () {
            if (app()->environment('stage')) {
                Route::middleware('api')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/api.php'));
            } else {
                Route::prefix('api')
                    ->middleware('api')
                    ->namespace($this->namespace)
                    ->group(base_path('routes/api.php'));
            }
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
