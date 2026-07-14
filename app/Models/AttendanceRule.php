<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'default_grace_minutes',
        'full_day_minimum_minutes',
        'half_day_minimum_minutes',
        'minimum_overtime_minutes',
        'maximum_overtime_minutes',
        'auto_mark_absent',
        'allow_manual_attendance',
        'allow_attendance_adjustment',
        'require_checkout',
        'missing_checkout_action',
        'overtime_requires_approval',
    ];

    protected $casts = [
        'default_grace_minutes' => 'integer',
        'full_day_minimum_minutes' => 'integer',
        'half_day_minimum_minutes' => 'integer',
        'minimum_overtime_minutes' => 'integer',
        'maximum_overtime_minutes' => 'integer',
        'auto_mark_absent' => 'boolean',
        'allow_manual_attendance' => 'boolean',
        'allow_attendance_adjustment' => 'boolean',
        'require_checkout' => 'boolean',
        'overtime_requires_approval' => 'boolean',
    ];

    /**
     * Safe initial values for a fresh production installation.
     */
    public static function defaults(): array
    {
        return [
            'default_grace_minutes' => 10,
            'full_day_minimum_minutes' => 480,
            'half_day_minimum_minutes' => 240,
            'minimum_overtime_minutes' => 30,
            'maximum_overtime_minutes' => 240,
            'auto_mark_absent' => true,
            'allow_manual_attendance' => true,
            'allow_attendance_adjustment' => true,
            'require_checkout' => true,
            'missing_checkout_action' => 'missing_checkout',
            'overtime_requires_approval' => true,
        ];
    }
}
