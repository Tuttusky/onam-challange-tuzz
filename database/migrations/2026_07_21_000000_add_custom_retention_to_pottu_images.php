<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pottu_images', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('is_active');
            $table->timestamp('expires_at')->nullable()->after('is_custom');

            $table->index(['is_custom', 'expires_at']);
        });

        DB::table('pottu_images')
            ->where('is_active', false)
            ->where(function ($query) {
                $query->where('path', 'like', '%pottu-custom-images%');
            })
            ->update([
                'is_custom' => true,
            ]);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE pottu_images
                SET expires_at = datetime(created_at, '+7 days')
                WHERE is_custom = 1 AND expires_at IS NULL
            ");
        } else {
            DB::table('pottu_images')
                ->where('is_custom', true)
                ->whereNull('expires_at')
                ->update([
                    'expires_at' => DB::raw('DATE_ADD(created_at, INTERVAL 7 DAY)'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('pottu_images', function (Blueprint $table) {
            $table->dropIndex(['is_custom', 'expires_at']);
            $table->dropColumn(['is_custom', 'expires_at']);
        });
    }
};
