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
    
    // Loan routes – rutas específicas ANTES que las rutas con parámetros dinámicos
    Route::post('loans/calculate', [LoanController::class, 'calculate']);
    Route::post('loans/request', [LoanController::class, 'store']);
    Route::get('loans/my', [LoanController::class, 'myLoans']);
    Route::get('loans/stats', [LoanController::class, 'stats']);
    Route::get('loans/pending', [LoanController::class, 'pending']);
    Route::get('loans/all', [LoanController::class, 'allLoans']);

    // Rutas con {id} SIEMPRE al final para no interferir con las anteriores
    Route::get('loans/{id}', [LoanController::class, 'show'])->whereNumber('id');
    Route::post('loans/{id}/approve', [LoanController::class, 'approve'])->whereNumber('id');
    Route::post('loans/{id}/reject', [LoanController::class, 'reject'])->whereNumber('id');
    Route::post('loans/{id}/pay-installment', [LoanController::class, 'payInstallment'])->whereNumber('id');
    Route::post('loans/{id}/pay-full', [LoanController::class, 'payFull'])->whereNumber('id');
});

Route::get('/test-api-connection', function () {
    return response()->json(['message' => 'Conexión exitosa a la API de Laravel'], 200);
});
