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
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('old_id')->nullable()->after('id');
            $table->string('nepali_title', 255)->nullable()->after('name');
            $table->string('category_code', 50)->nullable()->after('slug');
            $table->boolean('featured_status')->default(0)->after('menu_order');

            $table->index('old_id');
            $table->index('category_code');
            $table->index('featured_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['old_id']);
            $table->dropIndex(['category_code']);
            $table->dropIndex(['featured_status']);

            $table->dropColumn(['old_id', 'nepali_title', 'category_code', 'featured_status']);
        });
    }
};
