<?php

namespace Database\Seeders;

use App\Models\AttendanceRule;
use Illuminate\Database\Seeder;

class AttendanceRuleSeeder extends Seeder
{
    /**
     * Create the singleton attendance rule only when one does not exist.
     * Existing production configuration is intentionally never overwritten.
     */
    public function run(): void
    {
        AttendanceRule::query()->firstOrCreate([], AttendanceRule::defaults());
    }
}
