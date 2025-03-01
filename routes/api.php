<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/auth.php';


Route::get('menu-opcions/getColumnas', [App\Http\Controllers\Api\MenuOpcionApiController::class, 'getColumnas'])->name('menu-opcions.getColumnas');

Route::apiResource('menu-opcions', App\Http\Controllers\Api\MenuOpcionApiController::class);

Route::get('permissions/getColumnas', [App\Http\Controllers\Api\PermissionApiController::class, 'getColumnas'])->name('permissions.getColumnas');

Route::apiResource('permissions', App\Http\Controllers\Api\PermissionApiController::class);
