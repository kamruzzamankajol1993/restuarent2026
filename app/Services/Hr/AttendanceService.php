<?php

namespace App\Services\Hr;

use App\Models\AttendanceRecord;
use App\Models\AttendanceRule;
use App\Models\DutyRoster;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AttendanceService
{
    public function save(Employee $employee, Carbon $date, array $data, ?int $userId = null): AttendanceRecord
    {
        $shift = $this->resolveShift($employee, $date, $data);
        $rule = AttendanceRule::query()->first() ?? new AttendanceRule(AttendanceRule::defaults());
        $roster = DutyRoster::query()
            ->where('employee_id', $employee->id)
            ->whereDate('duty_date', $date)
            ->first();

        $scheduledIn = $this->scheduledDateTime($date, $shift?->start_time);
        $scheduledOut = $this->scheduledDateTime($date, $shift?->end_time);
        if ($scheduledIn && $scheduledOut && ($shift?->is_overnight || $scheduledOut->lte($scheduledIn))) {
            $scheduledOut->addDay();
        }

        $checkIn = $this->normaliseDateTime($date, Arr::get($data, 'check_in'));
        $checkOut = $this->normaliseDateTime($date, Arr::get($data, 'check_out'));
        if ($checkIn && $checkOut && $checkOut->lt($checkIn)) {
            $checkOut->addDay();
        }

        $breakMinutes = (int) ($data['break_minutes'] ?? $shift?->break_minutes ?? 0);
        $workedMinutes = ($checkIn && $checkOut)
            ? max(0, $checkIn->diffInMinutes($checkOut) - $breakMinutes)
            : 0;

        $graceMinutes = (int) ($shift?->grace_minutes ?? $rule->default_grace_minutes ?? 0);
        $lateMinutes = ($checkIn && $scheduledIn)
            ? max(0, $scheduledIn->copy()->addMinutes($graceMinutes)->diffInMinutes($checkIn, false))
            : 0;
        $earlyLeaveMinutes = ($checkOut && $scheduledOut)
            ? max(0, $checkOut->diffInMinutes($scheduledOut, false))
            : 0;

        $overtimeBase = $scheduledOut;
        if ($scheduledIn && $shift?->overtime_after_minutes) {
            $overtimeBase = $scheduledIn->copy()->addMinutes((int) $shift->overtime_after_minutes);
        }
        $overtimeMinutes = ($checkOut && $overtimeBase)
            ? max(0, $overtimeBase->diffInMinutes($checkOut, false))
            : 0;
        $minimumOvertime = (int) ($rule->minimum_overtime_minutes ?? 0);
        if ($overtimeMinutes < $minimumOvertime) {
            $overtimeMinutes = 0;
        }
        if ($rule->maximum_overtime_minutes) {
            $overtimeMinutes = min($overtimeMinutes, (int) $rule->maximum_overtime_minutes);
        }

        $status = $data['status'] ?? null;
        if (!$status) {
            $status = $this->deriveStatus($checkIn, $checkOut, $workedMinutes, $lateMinutes, $roster, $rule);
        }

        $query = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date);
        $shift ? $query->where('shift_id', $shift->id) : $query->whereNull('shift_id');
        $record = $query->first() ?? new AttendanceRecord();

        $record->fill([
            'employee_id' => $employee->id,
            'duty_roster_id' => $roster?->id,
            'shift_id' => $shift?->id,
            'attendance_date' => $date->toDateString(),
            'scheduled_in' => $scheduledIn,
            'scheduled_out' => $scheduledOut,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'break_minutes' => $breakMinutes,
            'status' => $status,
            'source' => $data['source'] ?? 'manual',
            'remarks' => $data['remarks'] ?? null,
            'approved_by' => $data['approved_by'] ?? $record->approved_by,
            'approved_at' => $data['approved_at'] ?? $record->approved_at,
            'created_by' => $record->exists ? $record->created_by : $userId,
            'updated_by' => $userId,
        ]);
        $record->save();

        return $record;
    }

    public function resolveShift(Employee $employee, Carbon $date, array $data): ?Shift
    {
        if (!empty($data['shift_id'])) {
            return Shift::find($data['shift_id']);
        }

        if (!empty($data['shift_code'])) {
            $shift = Shift::query()
                ->where('code', $data['shift_code'])
                ->orWhere('name', $data['shift_code'])
                ->first();
            if ($shift) {
                return $shift;
            }
        }

        $roster = DutyRoster::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('duty_date', $date)
            ->first();
        if ($roster?->shift) {
            return $roster->shift;
        }

        $assignment = $employee->shiftAssignments()
            ->with('shift')
            ->where('status', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->latest('effective_from')
            ->first();

        return $assignment?->shift ?? $employee->defaultShift;
    }

    private function scheduledDateTime(Carbon $date, $time): ?Carbon
    {
        if (!$time) {
            return null;
        }

        return Carbon::parse($date->toDateString().' '.substr((string) $time, 0, 8));
    }

    private function normaliseDateTime(Carbon $date, $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $parsed = Carbon::instance($value);
            if ($parsed->year <= 1901) {
                return Carbon::parse($date->toDateString().' '.$parsed->format('H:i:s'));
            }
            return $parsed;
        }

        $value = trim((string) $value);
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?\s*(AM|PM)?$/i', $value)) {
            return Carbon::parse($date->toDateString().' '.$value);
        }

        return Carbon::parse($value);
    }

    private function deriveStatus(?Carbon $checkIn, ?Carbon $checkOut, int $workedMinutes, int $lateMinutes, ?DutyRoster $roster, AttendanceRule $rule): string
    {
        if ($roster && in_array($roster->roster_status, ['off', 'holiday'], true)) {
            return $roster->roster_status;
        }
        if (!$checkIn) {
            return 'absent';
        }
        if (!$checkOut && $rule->require_checkout) {
            return 'missing_checkout';
        }
        if ($workedMinutes > 0 && $workedMinutes < (int) ($rule->half_day_minimum_minutes ?? 240)) {
            return 'half_day';
        }
        if ($lateMinutes > 0) {
            return 'late';
        }
        return 'present';
    }
}
