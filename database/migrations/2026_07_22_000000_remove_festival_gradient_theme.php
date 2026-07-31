<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaign_themes')
            ->where('background_gradient', 'like', '%#FF6B35%')
            ->orWhere('background_gradient', 'like', '%#ff6b35%')
            ->update([
                'primary_color' => '#6366f1',
                'secondary_color' => '#64748b',
                'background_gradient' => '#0f172a',
                'updated_at' => now(),
            ]);

        DB::table('website_settings')
            ->where('key', 'primary_color')
            ->where('value', '#FF6B35')
            ->update(['value' => '#6366f1', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('campaign_themes')
            ->where('primary_color', '#6366f1')
            ->update([
                'primary_color' => '#FF6B35',
                'secondary_color' => '#1B7D3A',
                'background_gradient' => 'linear-gradient(135deg, #FF6B35 0%, #FFD700 50%, #1B7D3A 100%)',
                'updated_at' => now(),
            ]);

        DB::table('website_settings')
            ->where('key', 'primary_color')
            ->where('value', '#6366f1')
            ->update(['value' => '#FF6B35', 'updated_at' => now()]);
    }
};
