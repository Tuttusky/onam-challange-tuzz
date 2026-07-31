<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('sort_order');
        });

        Schema::create('pottu_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('path');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active', 'sort_order']);
        });

        Schema::create('pottu_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->enum('type', ['image', 'lottie'])->default('image');
            $table->unsignedSmallInteger('default_size')->default(48);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active', 'sort_order']);
        });

        Schema::create('pottu_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_session_id')->constrained('player_sessions')->cascadeOnDelete();
            $table->foreignId('pottu_image_id')->constrained('pottu_images')->cascadeOnDelete();
            $table->foreignId('pottu_style_id')->nullable()->constrained('pottu_styles')->nullOnDelete();
            $table->decimal('x', 8, 6);
            $table->decimal('y', 8, 6);
            $table->unsignedSmallInteger('size')->default(48);
            $table->decimal('rotation', 6, 2)->default(0);
            $table->unsignedSmallInteger('board_width')->nullable();
            $table->unsignedSmallInteger('board_height')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(1);
            $table->timestamps();

            $table->unique('player_session_id');
        });

        Schema::table('challenge_links', function (Blueprint $table) {
            $table->foreignId('pottu_image_id')->nullable()->after('friend_media_id')->constrained('pottu_images')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('challenge_links', function (Blueprint $table) {
            $table->dropForeign(['pottu_image_id']);
            $table->dropColumn('pottu_image_id');
        });

        Schema::dropIfExists('pottu_placements');
        Schema::dropIfExists('pottu_styles');
        Schema::dropIfExists('pottu_images');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
