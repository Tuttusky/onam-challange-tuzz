<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('email');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });

        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();

            $table->index('group');
        });

        Schema::create('analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type')->nullable();
            $table->unsignedBigInteger('seoable_id')->nullable();
            $table->string('page_key')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_card')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('schema_markup')->nullable();
            $table->string('robots')->nullable();
            $table->string('google_verification')->nullable();
            $table->string('bing_verification')->nullable();
            $table->string('facebook_verification')->nullable();
            $table->timestamps();

            $table->index(['seoable_type', 'seoable_id']);
            $table->index('page_key');
        });

        Schema::create('campaign_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('primary_color')->default('#FF6B35');
            $table->string('secondary_color')->default('#004E89');
            $table->string('background_image')->nullable();
            $table->string('background_gradient')->nullable();
            $table->string('font_family')->default('Inter');
            $table->json('animation_pack')->nullable();
            $table->json('sound_pack')->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('dare_challenge');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'draft'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('max_questions')->default(10);
            $table->unsignedSmallInteger('max_friends')->default(50);
            $table->text('share_message')->nullable();
            $table->foreignId('campaign_theme_id')->nullable()->constrained('campaign_themes')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
            $table->index('type');
        });

        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['campaign_id', 'slug']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('question_categories')->nullOnDelete();
            $table->enum('type', ['yes_no', 'multiple_choice', 'emoji', 'text', 'image', 'video']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('difficulty')->default('medium');
            $table->unsignedSmallInteger('points')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['campaign_id', 'is_active', 'sort_order']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('image')->nullable();
            $table->unsignedTinyInteger('min_match_percent')->default(0);
            $table->unsignedTinyInteger('max_match_percent')->default(100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
        });

        Schema::create('result_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->cascadeOnDelete();
            $table->text('message');
            $table->unsignedTinyInteger('min_match_percent')->default(0);
            $table->unsignedTinyInteger('max_match_percent')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('referral_code', 16)->unique();
            $table->unsignedBigInteger('referred_by_player_id')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('share_count')->default(0);
            $table->timestamps();

            $table->index('referral_code');
        });

        Schema::create('player_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->enum('role', ['creator', 'challenger']);
            $table->enum('status', ['started', 'answering', 'completed', 'abandoned'])->default('started');
            $table->unsignedBigInteger('challenge_link_id')->nullable();
            $table->unsignedBigInteger('parent_session_id')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'player_id']);
            $table->index(['status', 'role']);
        });

        Schema::create('challenge_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('creator_session_id')->constrained('player_sessions')->cascadeOnDelete();
            $table->unsignedInteger('share_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
        });

        Schema::table('player_sessions', function (Blueprint $table) {
            $table->foreign('challenge_link_id')
                ->references('id')
                ->on('challenge_links')
                ->nullOnDelete();

            $table->foreign('parent_session_id')
                ->references('id')
                ->on('player_sessions')
                ->nullOnDelete();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->foreign('referred_by_player_id')
                ->references('id')
                ->on('players')
                ->nullOnDelete();
        });

        Schema::create('player_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_session_id')->constrained('player_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('answer_media')->nullable();
            $table->unsignedSmallInteger('points')->default(0);
            $table->timestamps();

            $table->unique(['player_session_id', 'question_id']);
        });

        Schema::create('challenge_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('challenge_link_id')->constrained('challenge_links')->cascadeOnDelete();
            $table->foreignId('creator_session_id')->constrained('player_sessions')->cascadeOnDelete();
            $table->foreignId('challenger_session_id')->constrained('player_sessions')->cascadeOnDelete();
            $table->unsignedSmallInteger('match_count')->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->decimal('match_percent', 5, 2)->default(0);
            $table->foreignId('winner_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('badge_id')->nullable()->constrained('badges')->nullOnDelete();
            $table->foreignId('result_message_id')->nullable()->constrained('result_messages')->nullOnDelete();
            $table->timestamps();

            $table->index(['challenge_link_id', 'created_at']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('referred_player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->unsignedInteger('reward_points')->default(0);
            $table->timestamps();

            $table->unique(['referrer_player_id', 'referred_player_id', 'campaign_id']);
        });

        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->enum('period', ['daily', 'weekly', 'monthly', 'overall']);
            $table->enum('metric', ['most_shared', 'most_invites', 'highest_match']);
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('rank')->default(0);
            $table->date('snapshot_date');
            $table->timestamps();

            $table->index(['period', 'metric', 'snapshot_date']);
            $table->index(['campaign_id', 'period', 'metric']);
        });

        Schema::create('visit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('event_type');
            $table->string('source')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('country')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['campaign_id', 'created_at']);
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['header', 'footer', 'popup', 'festival', 'advertisement']);
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->json('meta')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['is_read', 'created_at']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('visit_events');
        Schema::dropIfExists('leaderboard_snapshots');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('challenge_results');
        Schema::dropIfExists('player_answers');

        Schema::table('player_sessions', function (Blueprint $table) {
            $table->dropForeign(['challenge_link_id']);
            $table->dropForeign(['parent_session_id']);
        });

        Schema::table('challenge_links', function (Blueprint $table) {
            $table->dropForeign(['creator_session_id']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['referred_by_player_id']);
        });

        Schema::dropIfExists('challenge_links');
        Schema::dropIfExists('player_sessions');
        Schema::dropIfExists('players');
        Schema::dropIfExists('result_messages');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_categories');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('campaign_themes');
        Schema::dropIfExists('seo_settings');
        Schema::dropIfExists('analytics_settings');
        Schema::dropIfExists('website_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('admins');
    }
};
