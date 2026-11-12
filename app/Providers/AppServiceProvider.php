<?php

namespace App\Providers;

use App\Services\WorkflowSettings;
use App\Support\CurrentOrganization;
use App\Support\PassportSetup;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class);
        $this->app->scoped(WorkflowSettings::class);
    }

    public function boot(): void
    {
        // The key files are not in git. A fresh server creates them on boot
        // so login does not die with "Invalid key supplied".
        PassportSetup::ensure();
    }
}
