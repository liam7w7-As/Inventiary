<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSchedule
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->bypass_schedule) {
            return $next($request);
        }

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
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Acceso no permitido en este horario.');
            }
        }

        return $next($request);
    }
}
