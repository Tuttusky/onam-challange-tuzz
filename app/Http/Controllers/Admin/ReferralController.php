<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $referrals = Referral::query()
            ->with(['referrer', 'referred', 'campaign'])
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->campaign_id))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.referrals.index', compact('referrals'));
    }
}
