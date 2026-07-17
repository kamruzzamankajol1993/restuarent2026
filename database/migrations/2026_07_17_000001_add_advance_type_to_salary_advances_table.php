<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('salary_advances', 'advance_type')) {
            Schema::table('salary_advances', function (Blueprint $table) {
                $table->string('advance_type', 30)->default('salary_advance')->after('employee_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('salary_advances', 'advance_type')) {
            Schema::table('salary_advances', function (Blueprint $table) {
                $table->dropIndex(['advance_type']);
                $table->dropColumn('advance_type');
            });
        }
    }
};
