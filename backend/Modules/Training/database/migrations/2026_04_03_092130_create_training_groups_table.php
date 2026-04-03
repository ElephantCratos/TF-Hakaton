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
        Schema::create('training_groups', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique()->nullable();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('specification_id')
                ->nullable()
                ->constrained('specifications')
                ->nullOnDelete();

            $table->foreignId('status_id')
                ->nullable()
                ->constrained('statuses')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('price_per_person', 12, 2);

            $table->integer('participant_count')->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->decimal('average_progress', 5, 2)->default(0);

            $table->string('gantt_color', 20)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'start_date']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_groups');
    }
};
