<?php

namespace App\Services\Hr;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\SalaryAdvanceInstallment;
use App\Models\SalaryComponent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function generate(PayrollPeriod $period, Employee $employee, ?int $userId = null): Payroll
    {
        return DB::transaction(function () use ($period, $employee, $userId) {
            $setting = PayrollSetting::query()->first() ?? new PayrollSetting(PayrollSetting::defaults());
            $stats = $this->attendanceStats($period, $employee);

            $payroll = Payroll::query()->firstOrNew([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
            ]);
            if ($payroll->exists && in_array($payroll->payroll_status, ['approved', 'locked'], true)) {
                throw new \RuntimeException("Payroll for {$employee->full_name} is already approved/locked.");
            }

            SalaryAdvanceInstallment::query()->where('payroll_id', $payroll->id ?: 0)->update(['payroll_id' => null]);
            $payroll->items()->delete();

            $employeeComponents = $employee->salaryComponents()
                ->with('salaryComponent')
                ->where('status', true)
                ->whereDate('effective_from', '<=', $period->end_date)
                ->where(function ($q) use ($period) {
                    $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $period->start_date);
                })->get();

            $basicComponent = $employeeComponents->first(fn ($item) => $item->salaryComponent?->code === 'BASIC');
            $basicSalary = (float) ($basicComponent?->amount ?? $basicComponent?->salaryComponent?->default_amount ?? 0);
            $items = [];
            $earnings = 0.0;
            $deductions = 0.0;

            foreach ($employeeComponents as $employeeComponent) {
                $component = $employeeComponent->salaryComponent;
                if (!$component || !$component->status || $component->is_overtime_component) continue;
                if ($component->is_attendance_based && $component->component_type === 'earning' && ($stats['absent_days'] > 0 || $stats['late_days'] > 0)) continue;

                $amount = $this->componentAmount($employeeComponent, $component, $basicSalary);
                if ($amount <= 0) continue;
                $items[] = $this->item($component, $amount, 1, $amount);
                $component->component_type === 'earning' ? $earnings += $amount : $deductions += $amount;
            }

            if (!$employeeComponents->contains(fn ($item) => $item->salaryComponent?->code === 'BASIC') && $basicSalary > 0) {
                $basic = SalaryComponent::query()->where('code', 'BASIC')->first();
                $items[] = $this->item($basic, $basicSalary, 1, $basicSalary, 'Basic Salary');
                $earnings += $basicSalary;
            }

            $dailyRate = $stats['working_days'] > 0 ? $basicSalary / $stats['working_days'] : 0;
            if ($setting->attendance_deduction_enabled) {
                $deductionDays = $stats['absent_days'] + $stats['unpaid_leave_days'];
                if ($deductionDays > 0 && $dailyRate > 0) {
                    $amount = $dailyRate * $deductionDays;
                    $component = SalaryComponent::query()->where('code', 'ABSENT_DEDUCTION')->first();
                    $items[] = $this->item($component, $amount, $deductionDays, $dailyRate, 'Absent / unpaid leave deduction');
                    $deductions += $amount;
                }
            }

            if ($setting->late_deduction_enabled && $setting->late_count_for_one_day_deduction > 0 && $stats['late_days'] > 0 && $dailyRate > 0) {
                $days = intdiv($stats['late_days'], (int) $setting->late_count_for_one_day_deduction);
                if ($days > 0) {
                    $amount = $dailyRate * $days;
                    $component = SalaryComponent::query()->where('code', 'LATE_DEDUCTION')->first();
                    $items[] = $this->item($component, $amount, $days, $dailyRate, 'Late attendance deduction');
                    $deductions += $amount;
                }
            }

            if ($setting->overtime_enabled && $stats['overtime_minutes'] > 0 && $basicSalary > 0) {
                $hourlyRate = $stats['working_days'] > 0 ? $basicSalary / ($stats['working_days'] * 8) : 0;
                $hours = $stats['overtime_minutes'] / 60;
                $rate = $hourlyRate * (float) $setting->overtime_rate_multiplier;
                $amount = $hours * $rate;
                $component = SalaryComponent::query()->where('code', 'OVERTIME')->first();
                $items[] = $this->item($component, $amount, $hours, $rate, 'Approved attendance overtime');
                $earnings += $amount;
            }

            $loanInstallments = collect();
            if ($setting->salary_advance_auto_deduction) {
                $availableForLoan = max(0, $earnings - $deductions);
                $dueInstallments = SalaryAdvanceInstallment::query()
                    ->with('salaryAdvance')
                    ->whereHas('salaryAdvance', fn ($q) => $q->where('employee_id', $employee->id)->whereIn('status', ['approved', 'active']))
                    ->whereIn('status', ['pending', 'partial'])
                    ->where(function ($q) use ($period) {
                        $q->whereNull('due_date')->orWhereDate('due_date', '<=', $period->end_date);
                    })->orderBy('due_date')->get();

                $loanAmount = 0.0;
                foreach ($dueInstallments as $installment) {
                    $due = max(0, (float) $installment->amount - (float) $installment->paid_amount);
                    if ($due <= 0 || $loanAmount + $due > $availableForLoan + 0.009) {
                        continue;
                    }
                    $loanInstallments->push($installment);
                    $loanAmount += $due;
                }

                if ($loanAmount > 0) {
                    $component = SalaryComponent::query()->where('code', 'LOAN_INSTALLMENT')->first();
                    $items[] = $this->item($component, $loanAmount, $loanInstallments->count(), $loanAmount / max(1, $loanInstallments->count()), 'Salary advance / loan installment');
                    $deductions += $loanAmount;
                }
            }

            $gross = $earnings;
            $net = max(0, $gross - $deductions);
            $net = $this->roundNet($net, $setting->net_salary_rounding);

            $payroll->fill([
                ...$stats,
                'basic_salary' => $basicSalary,
                'total_earnings' => round($earnings, 2),
                'total_deductions' => round($deductions, 2),
                'gross_salary' => round($gross, 2),
                'net_salary' => round($net, 2),
                'paid_amount' => $payroll->paid_amount ?? 0,
                'due_amount' => max(0, round($net - (float) ($payroll->paid_amount ?? 0), 2)),
                'payment_status' => (float) ($payroll->paid_amount ?? 0) > 0 ? 'partial' : 'unpaid',
                'payroll_status' => 'generated',
                'generated_by' => $userId,
            ]);
            $payroll->save();

            foreach ($items as $item) $payroll->items()->create($item);
            foreach ($loanInstallments as $installment) $installment->update(['payroll_id' => $payroll->id]);

            return $payroll->load(['employee', 'period', 'items', 'payments']);
        });
    }

    private function attendanceStats(PayrollPeriod $period, Employee $employee): array
    {
        $dates = collect(CarbonPeriod::create($period->start_date, $period->end_date))->map(fn ($d) => $d->copy());
        $holidays = Holiday::query()->where('status', true)->whereBetween('holiday_date', [$period->start_date, $period->end_date])->pluck('holiday_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();
        $offDates = $employee->dutyRosters()->whereBetween('duty_date', [$period->start_date, $period->end_date])->whereIn('roster_status', ['off', 'holiday'])->pluck('duty_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();
        $attendance = AttendanceRecord::query()->where('employee_id', $employee->id)->whereBetween('attendance_date', [$period->start_date, $period->end_date])->get()->keyBy(fn ($r) => $r->attendance_date->toDateString());
        $leaves = LeaveRequest::with('leaveType')->where('employee_id', $employee->id)->where('status', 'approved')->whereDate('start_date', '<=', $period->end_date)->whereDate('end_date', '>=', $period->start_date)->get();

        $working = $present = $absent = $leaveDays = $paidLeave = $unpaidLeave = $holidayDays = 0.0;
        $lateDays = 0;
        foreach ($dates as $date) {
            $key = $date->toDateString();
            if ($holidays->has($key) || $offDates->has($key)) { $holidayDays++; continue; }
            $working++;
            $leave = $leaves->first(fn ($l) => $date->betweenIncluded($l->start_date, $l->end_date));
            if ($leave) {
                $leaveDays++;
                $leave->leaveType?->is_paid ? $paidLeave++ : $unpaidLeave++;
                continue;
            }
            $record = $attendance->get($key);
            if (!$record) { $absent++; continue; }
            if ($record->status === 'half_day') { $present += .5; $absent += .5; }
            elseif (in_array($record->status, ['present', 'late'], true)) { $present++; }
            elseif ($record->status === 'leave') { $leaveDays++; }
            elseif (!in_array($record->status, ['holiday', 'off'], true)) { $absent++; }
            if ($record->status === 'late' || $record->late_minutes > 0) $lateDays++;
        }

        return [
            'working_days' => $working, 'present_days' => $present, 'absent_days' => $absent,
            'leave_days' => $leaveDays, 'paid_leave_days' => $paidLeave, 'unpaid_leave_days' => $unpaidLeave,
            'holiday_days' => $holidayDays, 'late_days' => $lateDays,
            'overtime_minutes' => (int) $attendance->sum('overtime_minutes'),
        ];
    }

    private function componentAmount($employeeComponent, $component, float $basicSalary): float
    {
        if ($component->calculation_type === 'percentage') {
            return $basicSalary * ((float) ($employeeComponent->percentage ?? $component->default_percentage ?? 0) / 100);
        }
        return (float) ($employeeComponent->amount ?? $component->default_amount ?? 0);
    }

    private function item($component, float $amount, float $quantity, float $rate, ?string $name = null): array
    {
        return [
            'salary_component_id' => $component?->id,
            'component_name' => $name ?? $component?->name ?? 'Salary Component',
            'component_type' => $component?->component_type ?? 'earning',
            'calculation_type' => $component?->calculation_type ?? 'manual',
            'quantity' => round($quantity, 4), 'rate' => round($rate, 4), 'amount' => round($amount, 2),
        ];
    }

    private function roundNet(float $amount, string $method): float
    {
        return match ($method) { 'up' => ceil($amount), 'down' => floor($amount), 'none' => $amount, default => round($amount) };
    }
}
