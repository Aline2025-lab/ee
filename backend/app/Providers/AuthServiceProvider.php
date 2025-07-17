<?php

namespace App\Providers;



use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Models\Formation;
use App\Policies\FormationPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Formation::class => FormationPolicy::class,
        // Ajoute ici d'autres policies si besoin
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Tu peux ajouter ici d'autres Gates personnalisées
    }
}
