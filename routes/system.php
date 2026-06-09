<?php

use App\Http\Controllers\System\ActivityLogController;
use App\Http\Controllers\System\MasterDataController;
use App\Http\Controllers\System\RolePermissionController;
use App\Http\Controllers\System\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('master-data', [MasterDataController::class, 'index'])->middleware('permission:master_data.view')->name('master-data.index');
    Route::post('master-data/{type}', [MasterDataController::class, 'store'])->middleware('permission:master_data.create')->name('master-data.store');
    Route::put('master-data/{type}/{id}', [MasterDataController::class, 'update'])->middleware('permission:master_data.edit')->name('master-data.update');
    Route::delete('master-data/{type}/{id}', [MasterDataController::class, 'destroy'])->middleware('permission:master_data.delete')->name('master-data.destroy');

    Route::get('activity-logs', ActivityLogController::class)->middleware('permission:reports.view')->name('activity-logs.index');

    Route::get('users-management', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('users-management.index');
    Route::get('users-management/create', [UserManagementController::class, 'create'])->middleware('permission:users.create')->name('users-management.create');
    Route::post('users-management', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('users-management.store');
    Route::get('users-management/{users_management}', [UserManagementController::class, 'show'])->middleware('permission:users.show')->name('users-management.show');
    Route::get('users-management/{users_management}/edit', [UserManagementController::class, 'edit'])->middleware('permission:users.edit')->name('users-management.edit');
    Route::put('users-management/{users_management}', [UserManagementController::class, 'update'])->middleware('permission:users.edit')->name('users-management.update');
    Route::patch('users-management/{users_management}', [UserManagementController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('users-management/{users_management}', [UserManagementController::class, 'destroy'])->middleware('permission:users.delete')->name('users-management.destroy');

    Route::get('role-permissions', [RolePermissionController::class, 'index'])->middleware('permission:users.view')->name('role-permissions.index');
    Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])->middleware('permission:users.edit')->name('role-permissions.update');
    Route::post('role-permissions/roles', [RolePermissionController::class, 'storeRole'])->middleware('permission:users.create')->name('role-permissions.roles.store');
    Route::put('role-permissions/roles/{role}', [RolePermissionController::class, 'updateRole'])->middleware('permission:users.edit')->name('role-permissions.roles.update');
    Route::delete('role-permissions/roles/{role}', [RolePermissionController::class, 'destroyRole'])->middleware('permission:users.delete')->name('role-permissions.roles.destroy');
    Route::post('role-permissions/{role}/categories', [RolePermissionController::class, 'storeCategory'])->middleware('permission:users.create')->name('role-permissions.categories.store');
    Route::put('role-permissions/categories/{category}', [RolePermissionController::class, 'updateCategory'])->middleware('permission:users.edit')->name('role-permissions.categories.update');
    Route::delete('role-permissions/categories/{category}', [RolePermissionController::class, 'destroyCategory'])->middleware('permission:users.delete')->name('role-permissions.categories.destroy');
});
