<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_frequency', 20)->default('monthly');
            $table->unsignedTinyInteger('period_start_day')->default(1);
            $table->unsignedTinyInteger('payment_day')->nullable();
            $table->string('salary_calculation_basis', 30)->default('working_days');
            $table->string('currency', 10)->default('BDT');
            $table->string('payslip_prefix', 30)->default('PAY');
            $table->boolean('overtime_enabled')->default(true);
            $table->decimal('overtime_rate_multiplier', 8, 2)->default(1.50);
            $table->boolean('attendance_deduction_enabled')->default(true);
            $table->string('absent_deduction_method', 30)->default('per_day');
            $table->boolean('late_deduction_enabled')->default(false);
            $table->unsignedSmallInteger('late_count_for_one_day_deduction')->default(3);
            $table->boolean('salary_advance_auto_deduction')->default(true);
            $table->boolean('require_payroll_approval')->default(true);
            $table->boolean('lock_after_approval')->default(true);
            $table->boolean('include_paid_holidays')->default(true);
            $table->string('net_salary_rounding', 20)->default('nearest');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
