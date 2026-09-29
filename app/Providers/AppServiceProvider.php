<?php

namespace App\Providers;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
        Paginator::useBootstrapFive();

        try {
            if (Schema::hasTable('users') && !Schema::hasColumn('users', 'employee_id')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            // Log or ignore
        }

        // Register every permission as a Gate, resolved against the role → permission map.
        foreach (array_keys(User::permissions()) as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasPermission($ability));
        }

        // Make the company profile (name/logo) available to the shell + login views.
        View::composer(['layouts.app', 'auth.login', 'auth.forgot-password', 'auth.reset-password', 'welcome'], function ($view) {
            $company = null;

            try {
                if (Schema::hasTable('company_settings')) {
                    $company = CompanySetting::current();
                }
            } catch (\Throwable $e) {
                $company = null;
            }

            $view->with('companyName', $company?->displayName() ?? config('app.name', 'Attendance'));
            $view->with('companyLogo', $company?->logoUrl());
        });
    }
}
