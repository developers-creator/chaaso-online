<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('show_banner_image')->default(0)->after('trending_count');
            $table->boolean('show_on_front')->default(0)->after('show_banner_image');
            $table->boolean('use_video')->default(0)->after('show_on_front');

            $table->index('show_banner_image');
            $table->index('show_on_front');
            $table->index('use_video');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['show_banner_image']);
            $table->dropIndex(['show_on_front']);
            $table->dropIndex(['use_video']);

            $table->dropColumn(['show_banner_image', 'show_on_front', 'use_video']);
        });
    }
};
