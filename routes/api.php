<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\LoanController;

Route::post('LoginMovil', [UsuariosController::class, 'LoginMovil']);
Route::post('RegisterMovil', [UsuariosController::class, 'RegisterMovil']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('ProfileMovil', [UsuariosController::class, 'ProfileMovil']);
    Route::post('LogOutMovil', [UsuariosController::class, 'LogOutMovil']);
    
    // Loan routes
    Route::post('loans/calculate', [LoanController::class, 'calculate']);
    Route::post('loans/request', [LoanController::class, 'store']);
    Route::get('loans/my', [LoanController::class, 'myLoans']);
    Route::get('loans/stats', [LoanController::class, 'stats']);
    Route::get('loans/pending', [LoanController::class, 'pending']);
    Route::get('loans/all', [LoanController::class, 'allLoans']);
    Route::post('loans/{id}/approve', [LoanController::class, 'approve']);
    Route::post('loans/{id}/reject', [LoanController::class, 'reject']);
    Route::post('loans/{id}/pay-installment', [LoanController::class, 'payInstallment']);
    Route::post('loans/{id}/pay-full', [LoanController::class, 'payFull']);
});

Route::get('/test-api-connection', function () {
    return response()->json(['message' => 'Conexión exitosa a la API de Laravel'], 200);
});
