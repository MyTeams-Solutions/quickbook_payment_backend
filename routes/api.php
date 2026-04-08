<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\QuickBooksController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Route::middleware('auth:sanctum')->group(function () {
// });
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);

// routes/api.php
Route::get('/qb/connect', [QuickBooksController::class, 'connect']);
Route::get('/qb/callback', [QuickBooksController::class, 'callback']);
Route::post('/qb/charge', [QuickBooksController::class, 'charge']);
Route::post('/qb/tokenize', [QuickBooksController::class, 'tokenizeCard']);
// routes/api.php
Route::get('/qb/charge/{chargeId}', [QuickBooksController::class, 'getCharge']);