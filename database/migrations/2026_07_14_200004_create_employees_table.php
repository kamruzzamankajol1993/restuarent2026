<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            /*
             * Flexible account links:
             * - Employee only: user_id = null, waiter_id = null
             * - Employee + User login: user_id set, waiter_id = null
             * - Employee + Waiter: waiter_id set; user_id may be null or set
             * - Employee + User + Waiter: both IDs set
             */
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('waiter_id')->nullable()->unique()->constrained('waiters')->nullOnDelete();

            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->foreignId('default_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->unsignedBigInteger('supervisor_id')->nullable()->index();

            $table->string('employee_code', 50)->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('email')->nullable()->unique();
            $table->string('image')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->string('nid_number', 50)->nullable()->unique();
            $table->string('passport_number', 50)->nullable()->unique();

            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();

            $table->date('join_date');
            $table->date('confirmation_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->date('termination_date')->nullable();

            $table->string('payment_method', 30)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('mobile_banking_type', 30)->nullable();
            $table->string('mobile_banking_number', 30)->nullable();

            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supervisor_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
