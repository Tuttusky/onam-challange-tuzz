@extends('admin.layouts.app')

@section('title', 'SEO Settings')
@section('page-title', 'SEO Settings')

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Homepage SEO</h5>
            <form method="POST" action="{{ route('admin.settings.seo') }}">
                @csrf @method('PUT')
                <input type="hidden" name="scope" value="homepage">
                @include('admin.settings._seo_fields', ['seo' => $homepage])
                <button type="submit" class="btn btn-admin-primary mt-3">Save Homepage SEO</button>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h5 class="text-white mb-3">Campaign SEO</h5>
            <form method="GET" class="mb-3">
                <select name="campaign_id" class="form-select" onchange="this.form.submit()">
                    @foreach($campaigns as $c)
                        <option value="{{ $c->id }}" @selected($selectedCampaignId == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ route('admin.settings.seo') }}">
                @csrf @method('PUT')
                <input type="hidden" name="scope" value="campaign">
                <input type="hidden" name="campaign_id" value="{{ $selectedCampaignId }}">
                @include('admin.settings._seo_fields', ['seo' => $campaignSeo])
                <button type="submit" class="btn btn-admin-primary mt-3">Save Campaign SEO</button>
            </form>
        </div>
    </div>
</div>
@endsection
