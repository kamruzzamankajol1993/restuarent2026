<?php

namespace Database\Seeders;

use App\Models\PayrollSetting;
use Illuminate\Database\Seeder;

class PayrollSettingSeeder extends Seeder
{
    /**
     * Create the singleton payroll setting only when one does not exist.
     * Existing production configuration is intentionally never overwritten.
     */
    public function run(): void
    {
        PayrollSetting::query()->firstOrCreate([], PayrollSetting::defaults());
    }
}
