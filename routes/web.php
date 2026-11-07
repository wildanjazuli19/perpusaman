
<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExperimentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Middleware\RecordActivity;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', RecordActivity::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Data Anggota
    Route::resource('members', MemberController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('/members/{member}/decrypt', [MemberController::class, 'decrypt'])
        ->name('members.decrypt');

    Route::post('/members/{member}/tamper-test', [MemberController::class, 'tamperTest'])
        ->name('members.tamper-test');

    // Eksperimen Kriptografi
    Route::get('/experiments', [ExperimentController::class, 'index'])
        ->name('experiments.index');

    Route::post('/experiments', [ExperimentController::class, 'store'])
        ->name('experiments.store');

    Route::get('/experiments/{experiment}', [ExperimentController::class, 'show'])
        ->name('experiments.show');

    // Katalog Buku
    Route::resource('books', BookController::class)
        ->except(['show']);

    // Kategori
    Route::resource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Peminjaman
    Route::get('/loans', [LoanController::class, 'index'])
        ->name('loans.index');

    Route::get('/loans/create', [LoanController::class, 'create'])
        ->name('loans.create');

    Route::post('/loans', [LoanController::class, 'store'])
        ->name('loans.store');

    // Pengembalian
    // Semua pengembalian melalui ReturnController
    // agar riwayat masuk ke tabel book_returns.

    Route::get('/returns', [ReturnController::class, 'index'])
        ->name('returns.index');

    Route::get('/loans/{loan}/returns/create', [ReturnController::class, 'create'])
        ->name('loans.returns.create');

    Route::post('/loans/{loan}/returns', [ReturnController::class, 'store'])
        ->name('loans.returns.store');

    // Laporan
    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    Route::get('/reports/export', [ReportController::class, 'export'])
        ->name('reports.export');

    Route::get('/reports/print', [ReportController::class, 'print'])
        ->name('reports.print');

    // Audit Aktivitas
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');

});

require __DIR__.'/auth.php';
