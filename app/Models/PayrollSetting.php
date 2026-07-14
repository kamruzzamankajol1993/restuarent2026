<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_frequency',
        'period_start_day',
        'payment_day',
        'salary_calculation_basis',
        'currency',
        'payslip_prefix',
        'overtime_enabled',
        'overtime_rate_multiplier',
        'attendance_deduction_enabled',
        'absent_deduction_method',
        'late_deduction_enabled',
        'late_count_for_one_day_deduction',
        'salary_advance_auto_deduction',
        'require_payroll_approval',
        'lock_after_approval',
        'include_paid_holidays',
        'net_salary_rounding',
    ];

    protected $casts = [
        'period_start_day' => 'integer',
        'payment_day' => 'integer',
        'overtime_enabled' => 'boolean',
        'overtime_rate_multiplier' => 'decimal:2',
        'attendance_deduction_enabled' => 'boolean',
        'late_deduction_enabled' => 'boolean',
        'late_count_for_one_day_deduction' => 'integer',
        'salary_advance_auto_deduction' => 'boolean',
        'require_payroll_approval' => 'boolean',
        'lock_after_approval' => 'boolean',
        'include_paid_holidays' => 'boolean',
    ];

    /**
     * Safe initial values for a fresh production installation.
     */
    public static function defaults(): array
    {
        return [
            'payroll_frequency' => 'monthly',
            'period_start_day' => 1,
            'payment_day' => 7,
            'salary_calculation_basis' => 'working_days',
            'currency' => 'BDT',
            'payslip_prefix' => 'PAY',
            'overtime_enabled' => true,
            'overtime_rate_multiplier' => 1.50,
            'attendance_deduction_enabled' => true,
            'absent_deduction_method' => 'per_day',
            'late_deduction_enabled' => false,
            'late_count_for_one_day_deduction' => 3,
            'salary_advance_auto_deduction' => true,
            'require_payroll_approval' => true,
            'lock_after_approval' => true,
            'include_paid_holidays' => true,
            'net_salary_rounding' => 'nearest',
        ];
    }
}
