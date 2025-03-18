<?php

use App\Http\Controllers\Api\PermissionApiController;
use App\Http\Controllers\Api\RoleApiController;
use Illuminate\Support\Facades\Route;

//Route::apiResource('users', UserController::class);

//Route::apiResource('preferencias', PreferenciaController::class);

Route::group(['prefix' => 'permissions', 'as' => 'permissions.'], function () {
    Route::get('/{permission}/roles', [PermissionApiController::class, 'roles']);
});
Route::apiResource('permissions', PermissionApiController::class);


Route::group(['prefix' => 'roles', 'as' => 'roles.'], function () {
    Route::get('/{role}/permissions', [RoleApiController::class, 'permisos']);
});

Route::apiResource('roles', RoleApiController::class);

//Route::apiResource('user_preferencias', UserPreferenciaController::class);
