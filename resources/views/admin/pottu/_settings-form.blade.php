<div class="glass-card p-4">
    <form method="POST" action="{{ $action }}">
        @csrf @method('PUT')

        <h5 class="text-white mb-3">Gameplay</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="form-check">
                    <input type="checkbox" name="enable_game" value="1" class="form-check-input" id="enable_game" @checked(old('enable_game', $settings['enable_game'] ?? true))>
                    <label for="enable_game" class="form-check-label">Enable Game</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-check">
                    <input type="checkbox" name="overlay_enabled" value="1" class="form-check-input" id="overlay_enabled" @checked(old('overlay_enabled', $settings['overlay_enabled'] ?? true))>
                    <label for="overlay_enabled" class="form-check-label">Enable Overlay</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Overlay Color</label>
                <input type="text" name="overlay_color" class="form-control" value="{{ old('overlay_color', $settings['overlay_color'] ?? '#FFFFFF') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Overlay Opacity</label>
                <input type="number" step="0.05" min="0" max="1" name="overlay_opacity" class="form-control" value="{{ old('overlay_opacity', $settings['overlay_opacity'] ?? 1) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Reveal Speed (ms)</label>
                <input type="number" name="reveal_speed_ms" class="form-control" value="{{ old('reveal_speed_ms', $settings['reveal_speed_ms'] ?? 200) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fail Threshold (px)</label>
                <input type="number" name="fail_threshold_px" class="form-control" value="{{ old('fail_threshold_px', $settings['fail_threshold_px'] ?? 30) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Attempts</label>
                <input type="number" name="max_attempts" class="form-control" value="{{ old('max_attempts', $settings['max_attempts'] ?? 5) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Time Limit (sec, 0 = none)</label>
                <input type="number" name="time_limit_sec" class="form-control" value="{{ old('time_limit_sec', $settings['time_limit_sec'] ?? '') }}">
            </div>
        </div>

        <h5 class="text-white mb-3">Rewards & Features</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="leaderboard_enabled" value="1" class="form-check-input" id="lb" @checked(old('leaderboard_enabled', $settings['leaderboard_enabled'] ?? true))><label for="lb" class="form-check-label">Leaderboard</label></div></div>
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="coupon_enabled" value="1" class="form-check-input" id="coupon" @checked(old('coupon_enabled', $settings['coupon_enabled'] ?? true))><label for="coupon" class="form-check-label">Coupon</label></div></div>
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="analytics_enabled" value="1" class="form-check-input" id="analytics" @checked(old('analytics_enabled', $settings['analytics_enabled'] ?? true))><label for="analytics" class="form-check-label">Analytics</label></div></div>
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="reward_lucky_draw" value="1" class="form-check-input" id="lucky" @checked(old('reward_lucky_draw', $settings['rewards']['lucky_draw'] ?? true))><label for="lucky" class="form-check-label">Lucky Draw</label></div></div>
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="reward_coupon" value="1" class="form-check-input" id="reward_coupon" @checked(old('reward_coupon', $settings['rewards']['coupon'] ?? true))><label for="reward_coupon" class="form-check-label">Reward Coupon</label></div></div>
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="reward_badge" value="1" class="form-check-input" id="badge" @checked(old('reward_badge', $settings['rewards']['badge'] ?? true))><label for="badge" class="form-check-label">Badge</label></div></div>
            <div class="col-md-3"><div class="form-check"><input type="checkbox" name="reward_points" value="1" class="form-check-input" id="points" @checked(old('reward_points', $settings['rewards']['points'] ?? false))><label for="points" class="form-check-label">Points</label></div></div>
        </div>

        <h5 class="text-white mb-3">Tolerance Bands (JSON)</h5>
        <div class="mb-4">
            <textarea name="tolerance_bands" class="form-control" rows="8">{{ old('tolerance_bands', json_encode($settings['tolerance_bands'] ?? [], JSON_PRETTY_PRINT)) }}</textarea>
            <div class="form-text text-muted-admin">Array of {min, max, stars, label, points}</div>
        </div>

        <button type="submit" class="btn btn-admin-primary">Save Settings</button>
    </form>
</div>
