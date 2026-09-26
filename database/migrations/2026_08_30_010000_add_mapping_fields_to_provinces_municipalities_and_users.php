<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('provinces', 'geojson_polygon')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->json('geojson_polygon')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('municipalities', 'geojson_polygon')) {
            Schema::table('municipalities', function (Blueprint $table) {
                $table->json('geojson_polygon')->nullable()->after('province_id');
            });
        }

        if (! Schema::hasColumn('users', 'sector_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('sector_id')->nullable()->after('municipality_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'sector_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sector_id');
            });
        }

        if (Schema::hasColumn('municipalities', 'geojson_polygon')) {
            Schema::table('municipalities', function (Blueprint $table) {
                $table->dropColumn('geojson_polygon');
            });
        }

        if (Schema::hasColumn('provinces', 'geojson_polygon')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->dropColumn('geojson_polygon');
            });
        }
    }
};
