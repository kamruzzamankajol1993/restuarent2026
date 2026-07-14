<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'attendance_date' => 'date',
        'scheduled_in' => 'datetime',
        'scheduled_out' => 'datetime',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dutyRoster()
    {
        return $this->belongsTo(DutyRoster::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function adjustments()
    {
        return $this->hasMany(AttendanceAdjustment::class);
    }

    public function overtimeRecords()
    {
        return $this->hasMany(OvertimeRecord::class);
    }
}
