<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('default_grace_minutes')->default(10);
            $table->unsignedSmallInteger('full_day_minimum_minutes')->default(480);
            $table->unsignedSmallInteger('half_day_minimum_minutes')->default(240);
            $table->unsignedSmallInteger('minimum_overtime_minutes')->default(30);
            $table->unsignedSmallInteger('maximum_overtime_minutes')->nullable();
            $table->boolean('auto_mark_absent')->default(true);
            $table->boolean('allow_manual_attendance')->default(true);
            $table->boolean('allow_attendance_adjustment')->default(true);
            $table->boolean('require_checkout')->default(true);
            $table->string('missing_checkout_action', 30)->default('missing_checkout');
            $table->boolean('overtime_requires_approval')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rules');
    }
};
