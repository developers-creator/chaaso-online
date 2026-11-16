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
            $table->unsignedBigInteger('old_id')->nullable()->after('id');
            $table->string('tag_line', 191)->nullable()->after('post_title');
            $table->text('second_heading')->nullable()->after('tag_line');
            $table->unsignedInteger('trending_count')->default(0)->after('views');

            $table->index('old_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['old_id']);

            $table->dropColumn(['old_id', 'tag_line', 'second_heading', 'trending_count']);
        });
    }
};
