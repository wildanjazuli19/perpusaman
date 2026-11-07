<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $deleted = DB::transaction(function () use ($user): bool {
            $registrationLock = DB::table('app_settings')
                ->where('key', 'registration_locked')
                ->lockForUpdate()
                ->first();

            if ($registrationLock === null) {
                throw new RuntimeException('Pengaturan penguncian pendaftaran tidak ditemukan.');
            }

            if (User::query()->count() <= 1) {
                return false;
            }

            $user->delete();

            return true;
        });

        if (! $deleted) {
            return Redirect::route('profile.edit')
                ->withErrors(['password' => 'Akun Admin terakhir tidak dapat dihapus.'], 'userDeletion');
        }

        Auth::guard('web')->logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
