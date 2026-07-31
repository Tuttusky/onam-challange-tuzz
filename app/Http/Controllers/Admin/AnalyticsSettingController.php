<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsSettingController extends Controller
{
    public function edit(): View
    {
        $settings = AnalyticsSetting::query()->pluck('value', 'key')->toArray();

        return view('admin.settings.analytics', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:50'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:50'],
            'hotjar_id' => ['nullable', 'string', 'max:50'],
            'custom_head_scripts' => ['nullable', 'string'],
            'custom_body_scripts' => ['nullable', 'string'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            if ($key === 'enabled') {
                AnalyticsSetting::set($key, $request->boolean('enabled') ? '1' : '0');
            } else {
                AnalyticsSetting::set($key, $value);
            }
        }

        return back()->with('success', 'Analytics settings updated successfully.');
    }
}
