<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('street');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address_text')->nullable();
            $table->string('status')->default('planned');
            $table->decimal('budget_assigned', 14, 2)->nullable();
            $table->decimal('budget_executed', 14, 2)->default(0);
            $table->date('start_date_planned')->nullable();
            $table->date('end_date_planned')->nullable();
            $table->date('start_date_real')->nullable();
            $table->date('end_date_real')->nullable();
            $table->unsignedSmallInteger('progress_percentage')->default(0);
            $table->string('contractor_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['municipality_id', 'status']);
            $table->index(['municipality_id', 'type']);
            $table->index(['municipality_id', 'sector_id']);
            $table->index(['end_date_planned']);
            $table->index(['end_date_real']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
