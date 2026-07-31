<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_avatars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('friend_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('type', ['upload', 'avatar', 'initial']);
            $table->string('storage_path')->nullable();
            $table->string('public_token', 64)->unique();
            $table->foreignId('friend_avatar_id')->nullable()->constrained('friend_avatars')->nullOnDelete();
            $table->string('initial', 2)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['public_token', 'expires_at']);
        });

        Schema::table('challenge_links', function (Blueprint $table) {
            $table->string('token', 64)->unique()->nullable()->after('code');
            $table->string('friend_name')->nullable()->after('creator_session_id');
            $table->string('challenge_title')->nullable()->after('friend_name');
            $table->text('challenge_message')->nullable()->after('challenge_title');
            $table->foreignId('friend_media_id')->nullable()->after('challenge_message')->constrained('friend_media')->nullOnDelete();
            $table->decimal('creator_score', 10, 2)->nullable()->after('friend_media_id');
            $table->unsignedInteger('creator_completion_time_ms')->nullable()->after('creator_score');
            $table->unsignedSmallInteger('max_rematches')->default(10)->after('creator_completion_time_ms');
            $table->foreignId('parent_link_id')->nullable()->after('max_rematches')->constrained('challenge_links')->nullOnDelete();
            $table->boolean('is_finalized')->default(false)->after('is_active');
        });

        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_session_id')->constrained('player_sessions')->cascadeOnDelete();
            $table->decimal('score', 10, 2)->default(0);
            $table->unsignedInteger('completion_time_ms')->default(0);
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->json('achievements')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique('player_session_id');
        });

        Schema::table('challenge_results', function (Blueprint $table) {
            $table->decimal('creator_score', 10, 2)->nullable()->after('match_percent');
            $table->decimal('friend_score', 10, 2)->nullable()->after('creator_score');
            $table->decimal('score_diff', 10, 2)->nullable()->after('friend_score');
            $table->decimal('accuracy', 5, 2)->nullable()->after('score_diff');
            $table->unsignedInteger('creator_completion_time_ms')->nullable()->after('accuracy');
            $table->unsignedInteger('friend_completion_time_ms')->nullable()->after('creator_completion_time_ms');
            $table->json('meta')->nullable()->after('result_message_id');
        });

        Schema::create('challenge_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_link_id')->constrained('challenge_links')->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->enum('channel', ['whatsapp', 'instagram', 'facebook', 'telegram', 'copy']);
            $table->timestamps();

            $table->index(['challenge_link_id', 'channel']);
        });

        Schema::create('challenge_rematches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_link_id')->constrained('challenge_links')->cascadeOnDelete();
            $table->foreignId('to_link_id')->constrained('challenge_links')->cascadeOnDelete();
            $table->foreignId('initiated_by_player_id')->constrained('players')->cascadeOnDelete();
            $table->enum('type', ['challenge_back', 'rematch', 'new_friend']);
            $table->timestamps();

            $table->index('from_link_id');
        });

        Schema::create('coupon_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_result_id')->constrained('challenge_results')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('lucky_draw_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_result_id')->constrained('challenge_results')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('default_challenge_title')->nullable()->after('share_message');
        });

        $completedSessionIds = DB::table('player_sessions')
            ->where('status', 'completed')
            ->where('role', 'creator')
            ->pluck('id');

        if ($completedSessionIds->isNotEmpty()) {
            DB::table('challenge_links')
                ->whereIn('creator_session_id', $completedSessionIds)
                ->update(['is_finalized' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('default_challenge_title');
        });

        Schema::dropIfExists('lucky_draw_entries');
        Schema::dropIfExists('coupon_entries');
        Schema::dropIfExists('challenge_rematches');
        Schema::dropIfExists('challenge_shares');
        Schema::dropIfExists('game_scores');

        Schema::table('challenge_results', function (Blueprint $table) {
            $table->dropColumn([
                'creator_score',
                'friend_score',
                'score_diff',
                'accuracy',
                'creator_completion_time_ms',
                'friend_completion_time_ms',
                'meta',
            ]);
        });

        Schema::table('challenge_links', function (Blueprint $table) {
            $table->dropForeign(['friend_media_id']);
            $table->dropForeign(['parent_link_id']);
            $table->dropColumn([
                'token',
                'friend_name',
                'challenge_title',
                'challenge_message',
                'friend_media_id',
                'creator_score',
                'creator_completion_time_ms',
                'max_rematches',
                'parent_link_id',
                'is_finalized',
            ]);
        });

        Schema::dropIfExists('friend_media');
        Schema::dropIfExists('friend_avatars');
    }
};
