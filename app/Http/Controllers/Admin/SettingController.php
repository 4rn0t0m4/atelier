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
            'closure_notice_enabled' => Setting::get('closure_notice_enabled', '0'),
            'closure_notice_message' => Setting::get('closure_notice_message', ''),
            'closure_notice_until' => Setting::get('closure_notice_until', ''),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'daily_order_limit' => 'required|integer|min:0|max:9999',
            'closure_notice_message' => 'nullable|string|max:300',
            'closure_notice_until' => 'nullable|date',
        ]);

        Setting::set('daily_order_limit', $validated['daily_order_limit']);
        Setting::set('closure_notice_enabled', $request->boolean('closure_notice_enabled') ? '1' : '0');
        Setting::set('closure_notice_message', $validated['closure_notice_message'] ?? '');
        Setting::set('closure_notice_until', $validated['closure_notice_until'] ?? '');

        return redirect()->route('admin.settings.index')->with('success', 'Réglages mis à jour.');
    }
}
