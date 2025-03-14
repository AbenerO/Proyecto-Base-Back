<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/auth.php';


Route::post('menu-opcions/actualizar/orden', [App\Http\Controllers\Api\MenuOpcionApiController::class, 'actualizarOrden'])->name('menu-opcions.getColumnas');

Route::apiResource('menu-opcions', App\Http\Controllers\Api\MenuOpcionApiController::class);

Route::apiResource('permissions', App\Http\Controllers\Api\PermissionApiController::class);

Route::get('roles/getColumnas', [App\Http\Controllers\Api\RoleApiController::class, 'getColumnas'])->name('roles.getColumnas');

Route::apiResource('roles', App\Http\Controllers\Api\RoleApiController::class);
