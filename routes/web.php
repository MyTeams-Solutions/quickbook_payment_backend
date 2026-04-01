<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\QuickBooksController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/qb/test', function () {
    return view('qb-test');
});

Route::get('/qb/connect', [QuickBooksController::class, 'connect'])->name('qb.connect');
Route::get('/qb/callback', [QuickBooksController::class, 'callback'])->name('qb.callback');
Route::get('/qb/token-status', [QuickBooksController::class, 'tokenStatus'])->name('qb.token.status');
Route::post('/qb/tokenize', [QuickBooksController::class, 'tokenizeCard'])->name('qb.tokenize');
Route::post('/qb/charge', [QuickBooksController::class, 'charge'])->name('qb.charge');

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin.auth')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});
