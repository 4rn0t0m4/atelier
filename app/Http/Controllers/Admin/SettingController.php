<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'daily_order_limit' => Setting::get('daily_order_limit', 0),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'daily_order_limit' => 'required|integer|min:0|max:9999',
        ]);

        Setting::set('daily_order_limit', $validated['daily_order_limit']);

        return redirect()->route('admin.settings.index')->with('success', 'Réglages mis à jour.');
    }
}
