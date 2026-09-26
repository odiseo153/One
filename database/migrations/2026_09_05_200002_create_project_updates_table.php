<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('update_date');
            $table->unsignedSmallInteger('progress_percentage_at_update')->default(0);
            $table->text('description')->nullable();
            $table->string('status_at_update')->nullable();
            $table->decimal('budget_spent_at_update', 14, 2)->nullable();
            $table->timestamps();

            $table->index(['project_id', 'update_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};
