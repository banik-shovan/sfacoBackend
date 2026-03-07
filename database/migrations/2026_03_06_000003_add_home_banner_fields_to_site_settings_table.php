<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('hero_eyebrow')->nullable()->after('brand_subtitle');
            $table->text('hero_description')->nullable()->after('hero_eyebrow');
            $table->string('hero_stat_value', 40)->nullable()->after('hero_description');
            $table->string('hero_stat_label')->nullable()->after('hero_stat_value');
            $table->string('banner_heading')->nullable()->after('hero_stat_label');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'hero_eyebrow',
                'hero_description',
                'hero_stat_value',
                'hero_stat_label',
                'banner_heading',
            ]);
        });
    }
};
