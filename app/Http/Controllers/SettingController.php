<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function branding()
    {
        $logo = Setting::get('app_logo');
        return Inertia::render('Settings/Branding', [
            'current_logo' => $logo ? asset('storage/' . $logo) : null,
            'current_app_name' => Setting::get('app_name', ''),
        ]);
    }

    public function updateBranding(Request $request)
    {
        \Log::info('updateBranding payload:', $request->all());
        \Log::info('hasFile app_logo:', [$request->hasFile('app_logo')]);
        
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean'
        ]);

        if ($request->has('app_name')) {
            Setting::set('app_name', $request->app_name ?? '');
        }

        if ($request->boolean('remove_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('app_logo', null);
        } elseif ($request->hasFile('app_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::set('app_logo', $path);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $logo = Setting::get('app_logo');
            return response()->json([
                'success' => true,
                'message' => 'Branding berhasil diperbarui.',
                'app_logo' => $logo ? asset('storage/' . $logo) : null,
            ]);
        }

        return redirect()->back()->with('success', 'Branding berhasil diperbarui.');
    }
}
