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
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $setting = DB::table('app_settings')
            ->where('key', 'registration_locked')
            ->value('value');

        abort_unless($setting === '0' && ! User::query()->exists(), 404);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:' . User::class,
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = DB::transaction(function () use ($request) {
            $setting = DB::table('app_settings')
                ->where('key', 'registration_locked')
                ->lockForUpdate()
                ->value('value');

            abort_unless($setting === '0', 404);
            abort_if(User::query()->exists(), 404);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            DB::table('app_settings')
                ->where('key', 'registration_locked')
                ->update([
                    'value' => '1',
                    'updated_at' => now(),
                ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}