<?php

namespace App\Http\Controllers;

use App\Models\ExperimentRun;
use App\Services\MemberCryptography;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ExperimentController extends Controller
{
    public function index(): View
    {
        return view('experiments.index', [
            'experiments' => ExperimentRun::latest()->paginate(10),
        ]);
    }

    public function store(MemberCryptography $cryptography): RedirectResponse
    {
        $data = [
            'no_anggota' => 'DEMO-2026-001',
            'nama' => 'Data Sintetis Eksperimen',
            'kampus' => 'Meruya',
            'tanggal_berlaku' => '2027-12-31',
            'email' => 'anggota.demo@example.test',
            'telepon' => '081200000000',
        ];

        $result = $cryptography->benchmark($data);
        $experiment = ExperimentRun::create([
            'metrics' => $result['results'],
            'data_size_bytes' => $result['data_size_bytes'],
        ]);

        return redirect()->route('experiments.show', $experiment)
            ->with('status', 'Eksperimen selesai. Angka berikut berasal dari pengukuran proses aktual.');
    }

    public function show(ExperimentRun $experiment): View
    {
        return view('experiments.show', ['experiment' => $experiment]);
    }
}
