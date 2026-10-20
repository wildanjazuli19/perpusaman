<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\MemberCryptography;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(): View
    {
        return view('members.index', [
            'members' => Member::latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('members.create');
    }

    public function store(Request $request, MemberCryptography $cryptography): RedirectResponse
    {
        $member = $request->validate([
            'no_anggota' => ['required', 'string', 'max:50'],
            'nama' => ['required', 'string', 'max:150'],
            'kampus' => ['required', 'string', 'max:100'],
            'tanggal_berlaku' => ['required', 'date'],
            'email' => ['required', 'email', 'max:150'],
            'telepon' => ['required', 'string', 'max:30'],
        ]);

        $protected = $cryptography->protect($member);
        $record = Member::create([
            'hybrid_payload' => $protected['hybrid_payload'],
            'baseline_payload' => $protected['baseline_payload'],
            'metrics' => $protected['metrics'],
        ]);

        return redirect()->route('members.show', $record)
            ->with('status', 'Data anggota berhasil dienkripsi dan disimpan tanpa plaintext.');
    }

    public function show(Member $member): View
    {
        return view('members.show', ['member' => $member]);
    }

    public function decrypt(Member $member, MemberCryptography $cryptography): View
    {
        return view('members.show', [
            'member' => $member,
            'hybridDecryption' => $cryptography->decryptHybrid($member->hybrid_payload),
            'baselineDecryption' => $cryptography->decryptBaseline($member->baseline_payload),
        ]);
    }

    public function tamperTest(Member $member, MemberCryptography $cryptography): View
    {
        $tampered = $cryptography->tamperCiphertext($member->hybrid_payload);
        $result = $cryptography->decryptHybrid($tampered);

        return view('members.show', [
            'member' => $member,
            'tamperRejected' => $result['authenticated'] === false,
        ]);
    }
}
