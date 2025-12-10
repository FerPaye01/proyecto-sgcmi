<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\VesselCall::class => \App\Policies\VesselCallPolicy::class,
        \App\Models\Tramite::class => \App\Policies\TramitePolicy::class,
        \App\Models\OperationsMeeting::class => \App\Policies\OperationsMeetingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for permission-based authorization
        // This allows @can('PERMISSION_CODE') to work in Blade templates
        Gate::before(function ($user, $ability) {
            // Check if the ability matches a permission code
            if ($user->hasPermission($ability)) {
                return true;
            }

            // If not found, continue to policies
            return null;
        });
    }
}
