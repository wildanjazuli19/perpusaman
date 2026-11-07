<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the initial registration view only while first-time setup is allowed.
     */
    public function create(): View
    {
        abort_unless($this->registrationIsAllowed(), 404);

        return view('auth.register');
    }

    /**
     * Handle initial Admin registration. The lock is persisted independently
     * from the users table, so deleting the last account cannot reopen signup.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request): User {
            $setting = DB::table('app_settings')
                ->where('key', 'registration_locked')
                ->lockForUpdate()
                ->first();

            // Keep the users check as a defensive fallback for legacy/manual DB states.
            if (($setting && $setting->value === '1') || User::query()->exists()) {
                abort(404);
            }

            DB::table('app_settings')
                ->where('key', 'registration_locked')
                ->update(['value' => '1', 'updated_at' => now()]);

            return User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function registrationIsAllowed(): bool
    {
        $locked = DB::table('app_settings')
            ->where('key', 'registration_locked')
            ->value('value');

        return $locked !== '1' && ! User::query()->exists();
    }
}
