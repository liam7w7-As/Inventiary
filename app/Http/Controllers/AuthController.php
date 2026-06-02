<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with('role')->where('username', $request->username)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return back()->withErrors([
                'username' => 'Credenciales incorrectas.',
            ]);
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'username' => 'Tu cuenta está deshabilitada. Contacta al administrador.',
            ]);
        }

        // Check schedule unless bypass is enabled
        if (!$user->bypass_schedule) {
            $settings = SystemSetting::first();

            if ($settings) {
                $now = now()->format('H:i:s');
                $roleName = $user->role->name;

                if ($roleName === 'admin') {
                    $start = $settings->admin_hour_start;
                    $end = $settings->admin_hour_end;
                } else {
                    $start = $settings->encargado_hour_start;
                    $end = $settings->encargado_hour_end;
                }

                if ($start && $end && ($now < $start || $now > $end)) {
                    return back()->withErrors([
                        'username' => 'Acceso no permitido en este horario.',
                    ]);
                }
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
