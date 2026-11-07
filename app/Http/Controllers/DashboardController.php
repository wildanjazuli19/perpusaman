<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReturn;
use App\Models\Category;
use App\Models\ExperimentRun;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeLoanQuery = Loan::whereIn('status', ['borrowed', 'partially_returned']);

        return view('dashboard', [
            // Statistik operasional perpustakaan.
            'memberCount' => Member::count(),
            'bookCount' => Book::count(),
            'categoryCount' => Category::count(),
            'availableStock' => (int) Book::sum('stock_available'),
            'activeLoanCount' => (clone $activeLoanQuery)->count(),
            'overdueLoanCount' => Loan::whereIn('status', ['borrowed', 'partially_returned'])
                ->whereDate('due_date', '<', today())
                ->count(),
            'returnCount' => BookReturn::count(),

            // Aktivitas terbaru. Identitas anggota tetap ditampilkan secara anonim.
            'latestLoans' => Loan::with('book')->latest()->take(6)->get(),
            'latestReturns' => BookReturn::with('loan.book')->latest('returned_at')->take(5)->get(),
            'lowStockBooks' => Book::where('stock_available', '<=', 2)
                ->orderBy('stock_available')
                ->orderBy('title')
                ->take(5)
                ->get(),

            // Ringkasan penelitian tetap dipertahankan.
            'experimentCount' => ExperimentRun::count(),
            'latestExperiment' => ExperimentRun::latest()->first(),
        ]);
    }
}
