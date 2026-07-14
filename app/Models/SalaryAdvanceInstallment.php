<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryAdvanceInstallment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function salaryAdvance()
    {
        return $this->belongsTo(SalaryAdvance::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }
}
