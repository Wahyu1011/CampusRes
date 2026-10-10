<?php

namespace App\Providers;

use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

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
        ViewFacade::composer('layouts.dashboard', function (View $view): void {
            $user = Auth::user();

            $notificationCount = in_array($user->role, ['admin', 'petugas'], true)
                ? Reservation::where('status', 'pending')->count()
                : Reservation::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('start_time', '>=', now())
                    ->count();

            $view->with('notifCount', $notificationCount);
        });
    }
}
