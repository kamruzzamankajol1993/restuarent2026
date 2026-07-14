<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('reviewer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->decimal('overall_rating', 5, 2)->nullable();
            $table->json('rating_details')->nullable();
            $table->text('goals')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->text('comments')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'review_period_start', 'review_period_end'], 'employee_review_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
