<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'default_percentage' => 'decimal:4',
        'is_taxable' => 'boolean',
        'is_attendance_based' => 'boolean',
        'is_overtime_component' => 'boolean',
        'status' => 'boolean',
    ];

    public function employeeComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
