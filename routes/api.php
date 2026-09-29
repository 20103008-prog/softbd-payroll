<?php

use App\Http\Controllers\Api\AttendanceLogController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('login', [AuthTokenController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('logout', [AuthTokenController::class, 'logout']);

    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('devices', DeviceController::class);

    Route::get('attendance-logs', [AttendanceLogController::class, 'index']);
    Route::get('attendance-logs/{attendance_log}', [AttendanceLogController::class, 'show']);
});
