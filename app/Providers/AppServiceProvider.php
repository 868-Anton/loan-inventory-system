<?php

namespace App\Providers;

use App\Models\Loan;
use App\Observers\LoanObserver;
use Illuminate\Pagination\Paginator;
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
        // Paginator::useBootstrapFive();
        // Or if you prefer Tailwind for pagination
        Paginator::useTailwind();

        // Register observers
        Loan::observe(LoanObserver::class);
    }
}
