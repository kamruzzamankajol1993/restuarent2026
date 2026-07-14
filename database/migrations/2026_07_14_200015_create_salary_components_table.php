<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('component_type', 20)->index(); // earning or deduction
            $table->string('calculation_type', 30)->default('fixed');
            $table->decimal('default_amount', 14, 2)->nullable();
            $table->decimal('default_percentage', 8, 4)->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_attendance_based')->default(false);
            $table->boolean('is_overtime_component')->default(false);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
