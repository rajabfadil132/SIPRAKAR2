<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', fn () => redirect()->route(request()->user()->accessibleRouteName()))->name('home');
    Route::get('/siprakar', fn () => redirect()->route(request()->user()->accessibleRouteName()))->name('siprakar.home');
    Route::get('/pengaturan-sistem', function () {
        $user = request()->user();

        foreach ([
            'master_data.view' => 'master-data.index',
            'users.view' => 'users-management.index',
            'reports.view' => 'activity-logs.index',
        ] as $permission => $route) {
            if ($user->hasPermission($permission)) {
                return redirect()->route($route);
            }
        }

        return redirect()->route($user->accessibleRouteName());
    })->name('system.home');
    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->middleware('permission:notifications.view')->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->middleware('permission:notifications.view')->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/system.php';
require __DIR__.'/siprakar.php';
require __DIR__.'/auth.php';
