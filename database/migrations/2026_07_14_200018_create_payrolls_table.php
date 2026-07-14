<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();

            $table->decimal('working_days', 7, 2)->default(0);
            $table->decimal('present_days', 7, 2)->default(0);
            $table->decimal('absent_days', 7, 2)->default(0);
            $table->decimal('leave_days', 7, 2)->default(0);
            $table->decimal('paid_leave_days', 7, 2)->default(0);
            $table->decimal('unpaid_leave_days', 7, 2)->default(0);
            $table->decimal('holiday_days', 7, 2)->default(0);
            $table->unsignedInteger('late_days')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('gross_salary', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('due_amount', 14, 2)->default(0);

            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->string('payroll_status', 30)->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
