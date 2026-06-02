<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function edit()
    {
        $setting = SystemSetting::firstOrFail();

        return Inertia::render('Settings/Edit', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:100',
            'alias'                 => 'required|string|max:100',
            'activity'              => 'nullable|string|max:150',
            'currency'              => 'required|string|max:10',
            'admin_hour_start'      => 'required|date_format:H:i',
            'admin_hour_end'        => 'required|date_format:H:i',
            'encargado_hour_start'  => 'required|date_format:H:i',
            'encargado_hour_end'    => 'required|date_format:H:i',
            'print_format'          => 'required|in:58mm,80mm,A4',
            'logo'                  => 'nullable|image|max:2048',
        ]);

        $setting = SystemSetting::firstOrFail();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        // Remove logo key if no file sent (don't overwrite existing)
        if (!$request->hasFile('logo')) {
            unset($validated['logo']);
        }

        $setting->update($validated);

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }
}
