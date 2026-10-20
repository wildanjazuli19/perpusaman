<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExperimentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('members', MemberController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::post('/members/{member}/decrypt', [MemberController::class, 'decrypt'])
        ->name('members.decrypt');
    Route::post('/members/{member}/tamper-test', [MemberController::class, 'tamperTest'])
        ->name('members.tamper-test');

    Route::get('/experiments', [ExperimentController::class, 'index'])
        ->name('experiments.index');
    Route::post('/experiments', [ExperimentController::class, 'store'])
        ->name('experiments.store');
    Route::get('/experiments/{experiment}', [ExperimentController::class, 'show'])
        ->name('experiments.show');
});

require __DIR__.'/auth.php';
