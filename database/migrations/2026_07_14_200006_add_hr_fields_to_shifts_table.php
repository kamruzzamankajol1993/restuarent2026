<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->unique()->after('name');
            $table->time('start_time')->nullable()->after('code');
            $table->time('end_time')->nullable()->after('start_time');
            $table->unsignedInteger('break_minutes')->default(0)->after('end_time');
            $table->unsignedInteger('grace_minutes')->default(0)->after('break_minutes');
            $table->unsignedInteger('minimum_work_minutes')->nullable()->after('grace_minutes');
            $table->unsignedInteger('overtime_after_minutes')->nullable()->after('minimum_work_minutes');
            $table->boolean('is_overnight')->default(false)->after('overtime_after_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code',
                'start_time',
                'end_time',
                'break_minutes',
                'grace_minutes',
                'minimum_work_minutes',
                'overtime_after_minutes',
                'is_overnight',
            ]);
        });
    }
};
