<?php

use App\Http\Controllers\System\ActivityLogController;
use App\Http\Controllers\System\MasterDataController;
use App\Http\Controllers\System\RolePermissionController;
use App\Http\Controllers\System\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
    Route::post('master-data/{type}', [MasterDataController::class, 'store'])->name('master-data.store');
    Route::put('master-data/{type}/{id}', [MasterDataController::class, 'update'])->name('master-data.update');
    Route::delete('master-data/{type}/{id}', [MasterDataController::class, 'destroy'])->name('master-data.destroy');

    Route::get('activity-logs', ActivityLogController::class)->name('activity-logs.index');

    Route::get('users-management', [UserManagementController::class, 'index'])->name('users-management.index');
    Route::get('users-management/create', [UserManagementController::class, 'create'])->name('users-management.create');
    Route::post('users-management', [UserManagementController::class, 'store'])->name('users-management.store');
    Route::get('users-management/{users_management}', [UserManagementController::class, 'show'])->name('users-management.show');
    Route::get('users-management/{users_management}/edit', [UserManagementController::class, 'edit'])->name('users-management.edit');
    Route::put('users-management/{users_management}', [UserManagementController::class, 'update'])->name('users-management.update');
    Route::patch('users-management/{users_management}', [UserManagementController::class, 'update']);
    Route::delete('users-management/{users_management}', [UserManagementController::class, 'destroy'])->name('users-management.destroy');

    Route::get('role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])->name('role-permissions.update');
    Route::post('role-permissions/roles', [RolePermissionController::class, 'storeRole'])->name('role-permissions.roles.store');
    Route::put('role-permissions/roles/{role}', [RolePermissionController::class, 'updateRole'])->name('role-permissions.roles.update');
    Route::delete('role-permissions/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('role-permissions.roles.destroy');
    Route::post('role-permissions/{role}/categories', [RolePermissionController::class, 'storeCategory'])->name('role-permissions.categories.store');
    Route::put('role-permissions/categories/{category}', [RolePermissionController::class, 'updateCategory'])->name('role-permissions.categories.update');
    Route::delete('role-permissions/categories/{category}', [RolePermissionController::class, 'destroyCategory'])->name('role-permissions.categories.destroy');
});
