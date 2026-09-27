<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_categories')) {
            Schema::create('business_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        $hasPropertyStatus = Schema::hasColumn('businesses', 'property_status');
        $hasDocumentType = Schema::hasColumn('businesses', 'document_type');
        $hasDocumentNumber = Schema::hasColumn('businesses', 'document_number');
        $hasPrimaryCiiu = Schema::hasColumn('businesses', 'primary_ciiu_id');
        $hasSecondaryCiiu = Schema::hasColumn('businesses', 'secondary_ciiu_id');
        $hasPhotoUrl = Schema::hasColumn('businesses', 'photo_url');

        Schema::table('businesses', function (Blueprint $table) use ($hasPropertyStatus, $hasDocumentType, $hasDocumentNumber, $hasPrimaryCiiu, $hasSecondaryCiiu, $hasPhotoUrl): void {
            if (! $hasPropertyStatus) {
                $table->unsignedTinyInteger('property_status')->nullable()->after('registration_status');
            }
            if (! $hasDocumentType) {
                $table->unsignedTinyInteger('document_type')->nullable()->after('property_status');
            }
            if (! $hasDocumentNumber) {
                $table->string('document_number')->nullable()->after('document_type');
            }
            if (! $hasPrimaryCiiu) {
                $table->foreignId('primary_ciiu_id')->nullable()->after('rnc')->constrained('business_categories')->nullOnDelete();
            }
            if (! $hasSecondaryCiiu) {
                $table->foreignId('secondary_ciiu_id')->nullable()->after('primary_ciiu_id')->constrained('business_categories')->nullOnDelete();
            }
            if (! $hasPhotoUrl) {
                $table->string('photo_url')->nullable()->after('secondary_ciiu_id');
            }
        });

        if (! Schema::hasTable('business_employees')) {
            Schema::create('business_employees', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('first_name');
                $table->string('last_name');
                $table->unsignedTinyInteger('document_type');
                $table->string('document_number');
                $table->decimal('salary', 12, 2);
                $table->timestamps();

                $table->index(['business_id', 'document_type', 'document_number'], 'business_employee_docs_idx');
            });
        } elseif (! Schema::hasIndex('business_employees', 'business_employee_docs_idx')) {
            Schema::table('business_employees', function (Blueprint $table): void {
                $table->index(['business_id', 'document_type', 'document_number'], 'business_employee_docs_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_employees');

        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('secondary_ciiu_id');
            $table->dropConstrainedForeignId('primary_ciiu_id');
            $table->dropColumn(['property_status', 'document_type', 'document_number', 'photo_url']);
        });

        Schema::dropIfExists('business_categories');
    }
};
