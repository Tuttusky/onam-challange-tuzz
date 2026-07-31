<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\SeoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoSettingController extends Controller
{
    public function edit(Request $request): View
    {
        $homepage = SeoSetting::query()->where('page_key', 'homepage')->first();
        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);
        $selectedCampaignId = $request->input('campaign_id', $campaigns->first()?->id);
        $campaignSeo = $selectedCampaignId
            ? SeoSetting::query()
                ->where('seoable_type', Campaign::class)
                ->where('seoable_id', $selectedCampaignId)
                ->first()
            : null;

        return view('admin.settings.seo', compact('homepage', 'campaigns', 'selectedCampaignId', 'campaignSeo'));
    }

    public function update(Request $request): RedirectResponse
    {
        $scope = $request->input('scope', 'homepage');

        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'twitter_card' => ['nullable', 'string', 'max:50'],
            'canonical_url' => ['nullable', 'url'],
            'robots' => ['nullable', 'string', 'max:100'],
            'google_verification' => ['nullable', 'string', 'max:255'],
            'bing_verification' => ['nullable', 'string', 'max:255'],
            'facebook_verification' => ['nullable', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
        ]);

        if ($scope === 'campaign' && $request->filled('campaign_id')) {
            SeoSetting::query()->updateOrCreate(
                [
                    'seoable_type' => Campaign::class,
                    'seoable_id' => $request->campaign_id,
                ],
                collect($data)->except('campaign_id')->toArray()
            );
        } else {
            SeoSetting::query()->updateOrCreate(
                ['page_key' => 'homepage'],
                collect($data)->except('campaign_id')->toArray() + ['page_key' => 'homepage']
            );
        }

        return back()->with('success', 'SEO settings updated successfully.');
    }
}
