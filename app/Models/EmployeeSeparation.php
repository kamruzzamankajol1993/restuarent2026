<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSeparation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'notice_date' => 'date',
        'last_working_date' => 'date',
        'rehire_eligible' => 'boolean',
        'final_settlement_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
