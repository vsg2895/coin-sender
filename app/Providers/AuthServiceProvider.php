<?php

namespace App\Providers;

use App\Policies\{
    EventPolicy,
    MyTeamPolicy,
    AccessPolicy,
};

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
           if ($user->hasRole('Super Admin')) {
               return true;
           }
        });

        // Events
        Gate::define('view-events', [EventPolicy::class, 'viewAny']);
        Gate::define('create-event', [EventPolicy::class, 'create']);

        // Access
        Gate::define('view-accesses', [AccessPolicy::class, 'viewAny']);
        Gate::define('create-access', [AccessPolicy::class, 'create']);
        Gate::define('update-access', [AccessPolicy::class, 'update']);
        Gate::define('delete-access', [AccessPolicy::class, 'delete']);

        // My Team
        Gate::define('view-team', [MyTeamPolicy::class, 'viewAny']);
        Gate::define('delete-project-member', [MyTeamPolicy::class, 'delete']);
        Gate::define('assign-project-member', [MyTeamPolicy::class, 'assignProjectMember']);
        Gate::define('assign-custom-project-member', [MyTeamPolicy::class, 'assignCustomProjectMember']);
    }
}
