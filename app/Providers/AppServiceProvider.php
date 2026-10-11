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

            $isOperator = in_array($user->role, ['admin', 'petugas'], true);

            $notificationQuery = $isOperator
                ? Reservation::where('status', 'pending')
                : Reservation::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('start_time', '>=', now());

            $notificationCount = (clone $notificationQuery)->count();

            $notifications = $notificationQuery
                ->with($isOperator ? ['facility', 'user'] : ['facility'])
                ->when($isOperator, fn ($query) => $query->latest(), fn ($query) => $query->orderBy('start_time'))
                ->limit(5)
                ->get();

            $view->with([
                'notifCount' => $notificationCount,
                'notifications' => $notifications,
                'notifIsOperator' => $isOperator,
                'notifUrl' => route('reservations.index', ['status' => $isOperator ? 'pending' : 'approved']),
            ]);
        });
    }
}
