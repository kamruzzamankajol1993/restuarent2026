<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('holiday_date')->index();
            $table->string('holiday_type', 30)->default('restaurant');
            $table->boolean('is_paid')->default(true);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->unique(['holiday_date', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
