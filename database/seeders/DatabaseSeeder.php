<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's production-safe default data.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            HrMasterDataSeeder::class,
            AttendanceRuleSeeder::class,
            PayrollSettingSeeder::class,
        ]);
    }
}
