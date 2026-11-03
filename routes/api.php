<?php



use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrustedDeviceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\FeeTypeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\CommitmentController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\CopilotController;


Route::prefix('login')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:5,1');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/trusted-device', [TrustedDeviceController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth:sanctum')->group(function () {
    // All authenticated users may end their own session.
    Route::post('/logout', [AuthController::class, 'logout']);

    // Manage only the authenticated user's trusted devices.
    Route::get('/trusted-devices', [TrustedDeviceController::class, 'index']);
    Route::delete('/trusted-devices/{deviceId}', [TrustedDeviceController::class, 'revoke']);

    // School administration and fee-management APIs.
    Route::middleware('role:admin,principal,accountant')->group(function () {
        Route::get('/accountant/dashboard', [DashboardController::class, 'show']);
        Route::get('/students', [StudentController::class, 'index']);
        Route::post('/students', [StudentController::class, 'store']);
        Route::get('/students/{student}', [StudentController::class, 'show']);
        Route::get('/students/{student}/payments', [PaymentController::class, 'studentHistory']);
        Route::get('/fee-types', [FeeTypeController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt']);
        Route::post('/commitments', [CommitmentController::class, 'store']);
        Route::post('/reminders', [ReminderController::class, 'store']);
        Route::post('/copilot/query', [CopilotController::class, 'query']);
    });
});
