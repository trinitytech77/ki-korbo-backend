<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Task;
use App\Policies\AgentPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\ServicePolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
    }
}
