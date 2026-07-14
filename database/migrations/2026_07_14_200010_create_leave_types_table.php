<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->boolean('is_paid')->default(true);
            $table->decimal('annual_limit', 6, 2)->default(0);
            $table->boolean('carry_forward_allowed')->default(false);
            $table->decimal('maximum_carry_forward', 6, 2)->nullable();
            $table->boolean('requires_document')->default(false);
            $table->boolean('status')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
