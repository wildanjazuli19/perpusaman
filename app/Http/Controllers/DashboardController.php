<?php

namespace App\Http\Controllers;

use App\Models\ExperimentRun;
use App\Models\Member;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'memberCount' => Member::count(),
            'experimentCount' => ExperimentRun::count(),
            'latestMembers' => Member::latest()->take(5)->get(),
            'latestExperiment' => ExperimentRun::latest()->first(),
        ]);
    }
}
