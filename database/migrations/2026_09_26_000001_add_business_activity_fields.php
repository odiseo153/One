<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->string('primary_activity')->nullable()->after('primary_ciiu_id');
            $table->string('secondary_activity')->nullable()->after('secondary_ciiu_id');
        });

        $categories = DB::table('business_categories')->pluck('name', 'id');

        foreach ($categories as $id => $name) {
            DB::table('businesses')
                ->where('primary_ciiu_id', $id)
                ->whereNull('primary_activity')
                ->update(['primary_activity' => $name]);
            DB::table('businesses')
                ->where('secondary_ciiu_id', $id)
                ->whereNull('secondary_activity')
                ->update(['secondary_activity' => $name]);
        }
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropColumn(['primary_activity', 'secondary_activity']);
        });
    }
};
