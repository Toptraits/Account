<?php



use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
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
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/students/{student}/payments', [PaymentController::class, 'studentHistory']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/accountant/dashboard', [DashboardController::class, 'show']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::get('/fee-types', [FeeTypeController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt']);
    Route::post('/commitments', [CommitmentController::class, 'store']);
    Route::post('/reminders', [ReminderController::class, 'store']);
    Route::post('/copilot/query', [CopilotController::class, 'query']);
    Route::get(
    '/students/{student}/payments',
    [PaymentController::class, 'studentHistory']
);
});
