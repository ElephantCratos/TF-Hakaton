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
        Schema::create('specifications', function (Blueprint $table) {
            $table->id();

            $table->string('document_number', 50)->unique();
            $table->date('specification_date');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->decimal('amount_without_vat', 14, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(22.00);
            $table->decimal('vat_amount', 14, 2)->nullable();
            $table->decimal('total_with_vat', 14, 2)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specifications');
    }
};
