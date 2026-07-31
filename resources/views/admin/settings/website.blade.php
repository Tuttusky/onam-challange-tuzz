@extends('admin.layouts.app')

@section('title', 'Website Settings')
@section('page-title', 'Website Settings')

@section('content')
<div class="glass-card p-4">
    <form method="POST" action="{{ route('admin.settings.website') }}">
        @csrf @method('PUT')
        <h5 class="text-white mb-3">General</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label">Site Name</label><input name="site_name" class="form-control" value="{{ old('site_name', $groups['general']['site_name'] ?? config('app.name')) }}"></div>
            <div class="col-md-6"><label class="form-label">Tagline</label><input name="site_tagline" class="form-control" value="{{ old('site_tagline', $groups['general']['site_tagline'] ?? '') }}"></div>
            <div class="col-md-4"><label class="form-label">Referral Reward Points</label><input type="number" name="referral_reward_points" class="form-control" value="{{ old('referral_reward_points', $groups['general']['referral_reward_points'] ?? 10) }}"></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="maintenance_mode" value="1" class="form-check-input" id="maint" @checked(old('maintenance_mode', $groups['general']['maintenance_mode'] ?? false))><label for="maint" class="form-check-label">Maintenance Mode</label></div></div>
        </div>
        <h5 class="text-white mb-3">Branding</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label">Logo URL</label><input name="logo" class="form-control" value="{{ old('logo', $groups['branding']['logo'] ?? '') }}"></div>
            <div class="col-md-6"><label class="form-label">Favicon URL</label><input name="favicon" class="form-control" value="{{ old('favicon', $groups['branding']['favicon'] ?? '') }}"></div>
            <div class="col-md-3"><label class="form-label">Primary Color</label><input type="color" name="primary_color" class="form-control form-control-color" value="{{ old('primary_color', $groups['branding']['primary_color'] ?? '#6366f1') }}"></div>
            <div class="col-md-3"><label class="form-label">Secondary Color</label><input type="color" name="secondary_color" class="form-control form-control-color" value="{{ old('secondary_color', $groups['branding']['secondary_color'] ?? '#1B4332') }}"></div>
        </div>
        <h5 class="text-white mb-3">Contact</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $groups['contact']['contact_email'] ?? '') }}"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input name="contact_phone" class="form-control" value="{{ old('contact_phone', $groups['contact']['contact_phone'] ?? '') }}"></div>
        </div>
        @php $flags = $groups['features']['feature_flags'] ?? ['referrals'=>true,'leaderboard'=>true,'analytics'=>true]; @endphp
        <h5 class="text-white mb-3">Features</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="feature_referrals" value="1" class="form-check-input" @checked(old('feature_referrals', $flags['referrals'] ?? true))><label class="form-check-label">Referrals</label></div></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="feature_leaderboard" value="1" class="form-check-input" @checked(old('feature_leaderboard', $flags['leaderboard'] ?? true))><label class="form-check-label">Leaderboard</label></div></div>
            <div class="col-md-4"><div class="form-check"><input type="checkbox" name="feature_analytics" value="1" class="form-check-input" @checked(old('feature_analytics', $flags['analytics'] ?? true))><label class="form-check-label">Analytics</label></div></div>
        </div>
        <button type="submit" class="btn btn-admin-primary">Save Settings</button>
    </form>
</div>
@endsection
