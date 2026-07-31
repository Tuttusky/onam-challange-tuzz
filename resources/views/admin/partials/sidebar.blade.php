@php
    $routeName = request()->route()?->getName() ?? '';
    $isActive = fn (...$patterns) => collect($patterns)->contains(fn ($p) => str_starts_with($routeName, $p));
@endphp
<aside class="admin-sidebar">
    <div class="admin-brand">
        <img src="/images/logo.png" alt="Logo" style="height: 42px; width: auto; object-fit: contain; margin-bottom: 6px;" />
        <h1>Admin Panel</h1>
    </div>

    <nav class="admin-nav py-3">
        <div class="nav-section">Overview</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ $routeName === 'admin.dashboard' ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="nav-section">Campaigns</div>
        <a href="{{ route('admin.campaigns.index') }}" class="nav-link {{ $isActive('admin.campaigns') ? 'active' : '' }}">
            <i class="bi bi-megaphone"></i> Campaigns
        </a>
        <a href="{{ route('admin.badges.index') }}" class="nav-link {{ $isActive('admin.badges') ? 'active' : '' }}">
            <i class="bi bi-award"></i> Badges
        </a>
        <a href="{{ route('admin.result-messages.index') }}" class="nav-link {{ $isActive('admin.result-messages') ? 'active' : '' }}">
            <i class="bi bi-chat-quote"></i> Result Messages
        </a>

        <div class="nav-section">Players</div>
        <a href="{{ route('admin.players.index') }}" class="nav-link {{ $isActive('admin.players') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Players
        </a>
        <a href="{{ route('admin.challenge-links.index') }}" class="nav-link {{ $isActive('admin.challenge-links') ? 'active' : '' }}">
            <i class="bi bi-link-45deg"></i> Challenge Links
        </a>
        <a href="{{ route('admin.challenge-results.index') }}" class="nav-link {{ $isActive('admin.challenge-results') ? 'active' : '' }}">
            <i class="bi bi-trophy"></i> Challenge Results
        </a>
        <a href="{{ route('admin.leaderboards.index') }}" class="nav-link {{ $isActive('admin.leaderboards') ? 'active' : '' }}">
            <i class="bi bi-bar-chart"></i> Leaderboards
        </a>
        <a href="{{ route('admin.referrals.index') }}" class="nav-link {{ $isActive('admin.referrals') ? 'active' : '' }}">
            <i class="bi bi-share"></i> Referrals
        </a>

        <div class="nav-section">Content</div>
        <a href="{{ route('admin.banners.index') }}" class="nav-link {{ $isActive('admin.banners') ? 'active' : '' }}">
            <i class="bi bi-image"></i> Banners
        </a>
        <a href="{{ route('admin.cms.index') }}" class="nav-link {{ $isActive('admin.cms') ? 'active' : '' }}">
            <i class="bi bi-file-text"></i> CMS Pages
        </a>

        <div class="nav-section">Insights</div>
        <a href="{{ route('admin.reports.index') }}" class="nav-link {{ $isActive('admin.reports') ? 'active' : '' }}">
            <i class="bi bi-graph-up"></i> Reports
        </a>

        <div class="nav-section">Settings</div>
        <a href="{{ route('admin.settings.website') }}" class="nav-link {{ $isActive('admin.settings.website') ? 'active' : '' }}">
            <i class="bi bi-globe"></i> Website
        </a>
        <a href="{{ route('admin.settings.friend-challenge') }}" class="nav-link {{ $isActive('admin.settings.friend-challenge') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Friend Challenge
        </a>
        <a href="{{ route('admin.settings.pottu') }}" class="nav-link {{ $isActive('admin.settings.pottu') ? 'active' : '' }}">
            <i class="bi bi-bullseye"></i> Pottu Challenge
        </a>
        @php $pottuCamp = \App\Models\Campaign::where('type', 'pottu')->first(); @endphp
        @if($pottuCamp)
        <a href="{{ route('admin.campaigns.pottu-images.index', $pottuCamp) }}" class="nav-link {{ $isActive('admin.campaigns.pottu-images') ? 'active' : '' }}">
            <i class="bi bi-images"></i> Pottu Girl Photos
        </a>
        @endif
        <a href="{{ route('admin.friend-avatars.index') }}" class="nav-link {{ $isActive('admin.friend-avatars') ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Friend Avatars
        </a>
        <a href="{{ route('admin.settings.seo') }}" class="nav-link {{ $isActive('admin.settings.seo') ? 'active' : '' }}">
            <i class="bi bi-search"></i> SEO
        </a>
        <a href="{{ route('admin.settings.analytics') }}" class="nav-link {{ $isActive('admin.settings.analytics') ? 'active' : '' }}">
            <i class="bi bi-activity"></i> Analytics
        </a>
        <a href="{{ route('admin.roles.index') }}" class="nav-link {{ $isActive('admin.roles') ? 'active' : '' }}">
            <i class="bi bi-shield-lock"></i> Roles
        </a>

        <div class="nav-section">System</div>
        <a href="{{ route('admin.logs.activity') }}" class="nav-link {{ $isActive('admin.logs.activity') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Activity Logs
        </a>
        <a href="{{ route('admin.logs.login') }}" class="nav-link {{ $isActive('admin.logs.login') ? 'active' : '' }}">
            <i class="bi bi-box-arrow-in-right"></i> Login Logs
        </a>
        <a href="{{ route('admin.backups.index') }}" class="nav-link {{ $isActive('admin.backups') ? 'active' : '' }}">
            <i class="bi bi-database"></i> Backups
        </a>
    </nav>
</aside>
