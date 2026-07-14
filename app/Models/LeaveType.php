<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_paid' => 'boolean',
        'carry_forward_allowed' => 'boolean',
        'requires_document' => 'boolean',
        'status' => 'boolean',
        'annual_limit' => 'decimal:2',
        'maximum_carry_forward' => 'decimal:2',
    ];

    public function balances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
