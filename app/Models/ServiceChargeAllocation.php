<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceChargeAllocation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_pool_amount' => 'decimal:2',
        'allocation_percentage' => 'decimal:4',
        'allocated_amount' => 'decimal:2',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
