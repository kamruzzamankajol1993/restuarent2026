<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'break_minutes' => 'integer',
        'grace_minutes' => 'integer',
        'minimum_work_minutes' => 'integer',
        'overtime_after_minutes' => 'integer',
        'is_overnight' => 'boolean',
        'status' => 'boolean',
    ];

    // Existing waiter operation relation.
    public function waiters()
    {
        return $this->hasMany(Waiter::class);
    }

    // Employees using this as their default shift.
    public function defaultEmployees()
    {
        return $this->hasMany(Employee::class, 'default_shift_id');
    }

    public function employeeShiftAssignments()
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function dutyRosters()
    {
        return $this->hasMany(DutyRoster::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
