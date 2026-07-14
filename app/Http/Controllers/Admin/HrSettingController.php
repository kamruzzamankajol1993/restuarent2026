<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRule;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\PayrollSetting;
use App\Models\SalaryComponent;
use App\Models\Shift;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HrSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-setting-view', ['only' => ['index']]);
        $this->middleware('permission:hr-setting-manage', ['except' => ['index']]);
    }

    public function index(Request $request)
    {
        $allowedTabs = [
            'departments',
            'designations',
            'employment-types',
            'shifts',
            'leave-types',
            'holidays',
            'salary-components',
            'attendance-rules',
            'payroll-settings',
        ];
        $activeTab = in_array($request->get('tab'), $allowedTabs, true) ? $request->get('tab') : 'departments';

        $attendanceRules = AttendanceRule::query()->first()
            ?? new AttendanceRule(AttendanceRule::defaults());
        $payrollSettings = PayrollSetting::query()->first()
            ?? new PayrollSetting(PayrollSetting::defaults());

        return view('admin.hr.settings.index', [
            'activeTab' => $activeTab,
            'departments' => Department::with('manager')->withCount('employees')->orderBy('name')->get(),
            'designations' => Designation::with('department')->withCount('employees')->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::withCount('employees')->orderBy('name')->get(),
            'shifts' => Shift::withCount('defaultEmployees')->orderBy('name')->get(),
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'holidays' => Holiday::orderByDesc('holiday_date')->get(),
            'salaryComponents' => SalaryComponent::orderBy('component_type')->orderBy('name')->get(),
            'activeEmployees' => Employee::where('status', 'active')->orderBy('first_name')->get(),
            'attendanceRules' => $attendanceRules,
            'payrollSettings' => $payrollSettings,
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:departments,code',
            'manager_employee_id' => 'nullable|integer|exists:employees,id',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        Department::create([
            'name' => $data['name'],
            'code' => $this->uniqueCode(Department::class, $data['code'] ?? $data['name']),
            'manager_employee_id' => $data['manager_employee_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return $this->success('Department created successfully.', 'departments');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('departments', 'code')->ignore($department->id)],
            'manager_employee_id' => 'nullable|integer|exists:employees,id',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        $department->update([
            'name' => $data['name'],
            'code' => $data['code'] ?: $department->code,
            'manager_employee_id' => $data['manager_employee_id'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return $this->success('Department updated successfully.', 'departments');
    }

    public function destroyDepartment(Department $department)
    {
        if ($department->employees()->exists() || $department->designations()->exists()) {
            return $this->inUse('Department', 'departments');
        }

        return $this->deleteSetting($department, 'Department', 'departments');
    }

    public function storeDesignation(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:designations,code',
            'level' => 'nullable|integer|min:1|max:999',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        Designation::create([
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'code' => $this->uniqueCode(Designation::class, $data['code'] ?? $data['name']),
            'level' => $data['level'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return $this->success('Designation created successfully.', 'designations');
    }

    public function updateDesignation(Request $request, Designation $designation)
    {
        $data = $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('designations', 'code')->ignore($designation->id)],
            'level' => 'nullable|integer|min:1|max:999',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        $designation->update([
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?: $designation->code,
            'level' => $data['level'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return $this->success('Designation updated successfully.', 'designations');
    }

    public function destroyDesignation(Designation $designation)
    {
        if ($designation->employees()->exists()) {
            return $this->inUse('Designation', 'designations');
        }

        return $this->deleteSetting($designation, 'Designation', 'designations');
    }

    public function storeEmploymentType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:30|unique:employment_types,code',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        EmploymentType::create([
            'name' => $data['name'],
            'code' => $this->uniqueCode(EmploymentType::class, $data['code'] ?? $data['name']),
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return $this->success('Employment type created successfully.', 'employment-types');
    }

    public function updateEmploymentType(Request $request, EmploymentType $employmentType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('employment_types', 'code')->ignore($employmentType->id)],
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        $employmentType->update([
            'name' => $data['name'],
            'code' => $data['code'] ?: $employmentType->code,
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return $this->success('Employment type updated successfully.', 'employment-types');
    }

    public function destroyEmploymentType(EmploymentType $employmentType)
    {
        if ($employmentType->employees()->exists()) {
            return $this->inUse('Employment type', 'employment-types');
        }

        return $this->deleteSetting($employmentType, 'Employment type', 'employment-types');
    }

    public function storeShift(Request $request)
    {
        $data = $this->validateShift($request);
        Shift::create($this->shiftPayload($request, $data));

        return $this->success('Shift created successfully.', 'shifts');
    }

    public function updateShift(Request $request, Shift $shift)
    {
        $data = $this->validateShift($request, $shift);
        $shift->update($this->shiftPayload($request, $data, $shift));

        return $this->success('Shift updated successfully.', 'shifts');
    }

    public function destroyShift(Shift $shift)
    {
        if (
            $shift->defaultEmployees()->exists()
            || $shift->waiters()->exists()
            || $shift->employeeShiftAssignments()->exists()
            || $shift->dutyRosters()->exists()
            || $shift->attendanceRecords()->exists()
        ) {
            return $this->inUse('Shift', 'shifts');
        }

        return $this->deleteSetting($shift, 'Shift', 'shifts');
    }

    public function storeLeaveType(Request $request)
    {
        $data = $this->validateLeaveType($request);
        LeaveType::create($this->leaveTypePayload($request, $data));

        return $this->success('Leave type created successfully.', 'leave-types');
    }

    public function updateLeaveType(Request $request, LeaveType $leaveType)
    {
        $data = $this->validateLeaveType($request, $leaveType);
        $leaveType->update($this->leaveTypePayload($request, $data, $leaveType));

        return $this->success('Leave type updated successfully.', 'leave-types');
    }

    public function destroyLeaveType(LeaveType $leaveType)
    {
        if ($leaveType->balances()->exists() || $leaveType->leaveRequests()->exists()) {
            return $this->inUse('Leave type', 'leave-types');
        }

        return $this->deleteSetting($leaveType, 'Leave type', 'leave-types');
    }

    public function storeHoliday(Request $request)
    {
        $data = $this->validateHoliday($request);
        Holiday::create($this->holidayPayload($request, $data));

        return $this->success('Holiday created successfully.', 'holidays');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $data = $this->validateHoliday($request, $holiday);
        $holiday->update($this->holidayPayload($request, $data));

        return $this->success('Holiday updated successfully.', 'holidays');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        return $this->deleteSetting($holiday, 'Holiday', 'holidays');
    }

    public function storeSalaryComponent(Request $request)
    {
        $data = $this->validateSalaryComponent($request);
        SalaryComponent::create($this->salaryComponentPayload($request, $data));

        return $this->success('Salary component created successfully.', 'salary-components');
    }

    public function updateSalaryComponent(Request $request, SalaryComponent $salaryComponent)
    {
        $data = $this->validateSalaryComponent($request, $salaryComponent);
        $salaryComponent->update($this->salaryComponentPayload($request, $data, $salaryComponent));

        return $this->success('Salary component updated successfully.', 'salary-components');
    }

    public function destroySalaryComponent(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->employeeComponents()->exists() || $salaryComponent->payrollItems()->exists()) {
            return $this->inUse('Salary component', 'salary-components');
        }

        return $this->deleteSetting($salaryComponent, 'Salary component', 'salary-components');
    }

    public function updateAttendanceRules(Request $request)
    {
        $data = $request->validate([
            'default_grace_minutes' => 'required|integer|min:0|max:180',
            'full_day_minimum_minutes' => 'required|integer|min:1|max:1440',
            'half_day_minimum_minutes' => 'required|integer|min:1|max:1440|lte:full_day_minimum_minutes',
            'minimum_overtime_minutes' => 'required|integer|min:0|max:1440',
            'maximum_overtime_minutes' => 'nullable|integer|min:0|max:1440|gte:minimum_overtime_minutes',
            'missing_checkout_action' => ['required', Rule::in(['missing_checkout', 'half_day', 'absent'])],
            'auto_mark_absent' => 'nullable|boolean',
            'allow_manual_attendance' => 'nullable|boolean',
            'allow_attendance_adjustment' => 'nullable|boolean',
            'require_checkout' => 'nullable|boolean',
            'overtime_requires_approval' => 'nullable|boolean',
        ]);

        $settings = AttendanceRule::query()->first() ?? new AttendanceRule();
        $settings->fill([
            'default_grace_minutes' => $data['default_grace_minutes'],
            'full_day_minimum_minutes' => $data['full_day_minimum_minutes'],
            'half_day_minimum_minutes' => $data['half_day_minimum_minutes'],
            'minimum_overtime_minutes' => $data['minimum_overtime_minutes'],
            'maximum_overtime_minutes' => $data['maximum_overtime_minutes'] ?? null,
            'missing_checkout_action' => $data['missing_checkout_action'],
            'auto_mark_absent' => $request->boolean('auto_mark_absent'),
            'allow_manual_attendance' => $request->boolean('allow_manual_attendance'),
            'allow_attendance_adjustment' => $request->boolean('allow_attendance_adjustment'),
            'require_checkout' => $request->boolean('require_checkout'),
            'overtime_requires_approval' => $request->boolean('overtime_requires_approval'),
        ])->save();

        return $this->success('Attendance rules updated successfully.', 'attendance-rules');
    }

    public function updatePayrollSettings(Request $request)
    {
        $data = $request->validate([
            'payroll_frequency' => ['required', Rule::in(['monthly', 'biweekly', 'weekly'])],
            'period_start_day' => 'required|integer|min:1|max:28',
            'payment_day' => 'nullable|integer|min:1|max:31',
            'salary_calculation_basis' => ['required', Rule::in(['working_days', 'calendar_days', 'fixed_30_days'])],
            'currency' => 'required|string|max:10',
            'payslip_prefix' => 'required|string|max:30',
            'overtime_rate_multiplier' => 'required|numeric|min:0|max:10',
            'absent_deduction_method' => ['required', Rule::in(['per_day', 'fixed_amount', 'manual'])],
            'late_count_for_one_day_deduction' => 'required|integer|min:1|max:31',
            'net_salary_rounding' => ['required', Rule::in(['none', 'nearest', 'up', 'down'])],
            'overtime_enabled' => 'nullable|boolean',
            'attendance_deduction_enabled' => 'nullable|boolean',
            'late_deduction_enabled' => 'nullable|boolean',
            'salary_advance_auto_deduction' => 'nullable|boolean',
            'require_payroll_approval' => 'nullable|boolean',
            'lock_after_approval' => 'nullable|boolean',
            'include_paid_holidays' => 'nullable|boolean',
        ]);

        $settings = PayrollSetting::query()->first() ?? new PayrollSetting();
        $settings->fill([
            'payroll_frequency' => $data['payroll_frequency'],
            'period_start_day' => $data['period_start_day'],
            'payment_day' => $data['payment_day'] ?? null,
            'salary_calculation_basis' => $data['salary_calculation_basis'],
            'currency' => strtoupper($data['currency']),
            'payslip_prefix' => strtoupper($data['payslip_prefix']),
            'overtime_rate_multiplier' => $data['overtime_rate_multiplier'],
            'absent_deduction_method' => $data['absent_deduction_method'],
            'late_count_for_one_day_deduction' => $data['late_count_for_one_day_deduction'],
            'net_salary_rounding' => $data['net_salary_rounding'],
            'overtime_enabled' => $request->boolean('overtime_enabled'),
            'attendance_deduction_enabled' => $request->boolean('attendance_deduction_enabled'),
            'late_deduction_enabled' => $request->boolean('late_deduction_enabled'),
            'salary_advance_auto_deduction' => $request->boolean('salary_advance_auto_deduction'),
            'require_payroll_approval' => $request->boolean('require_payroll_approval'),
            'lock_after_approval' => $request->boolean('lock_after_approval'),
            'include_paid_holidays' => $request->boolean('include_paid_holidays'),
        ])->save();

        return $this->success('Payroll settings updated successfully.', 'payroll-settings');
    }

    private function validateShift(Request $request, ?Shift $shift = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('shifts', 'code')->ignore($shift?->id)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'required|integer|min:0|max:1440',
            'grace_minutes' => 'required|integer|min:0|max:180',
            'minimum_work_minutes' => 'nullable|integer|min:0|max:1440',
            'overtime_after_minutes' => 'nullable|integer|min:0|max:1440',
            'is_overnight' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);
    }

    private function shiftPayload(Request $request, array $data, ?Shift $shift = null): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'] ?: ($shift?->code ?: $this->uniqueCode(Shift::class, $data['name'])),
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'break_minutes' => $data['break_minutes'],
            'grace_minutes' => $data['grace_minutes'],
            'minimum_work_minutes' => $data['minimum_work_minutes'] ?? null,
            'overtime_after_minutes' => $data['overtime_after_minutes'] ?? null,
            'is_overnight' => $request->boolean('is_overnight'),
            'status' => $request->boolean('status'),
        ];
    }

    private function validateLeaveType(Request $request, ?LeaveType $leaveType = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('leave_types', 'code')->ignore($leaveType?->id)],
            'annual_limit' => 'required|numeric|min:0|max:365',
            'maximum_carry_forward' => 'nullable|numeric|min:0|max:365',
            'is_paid' => 'nullable|boolean',
            'carry_forward_allowed' => 'nullable|boolean',
            'requires_document' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);
    }

    private function leaveTypePayload(Request $request, array $data, ?LeaveType $leaveType = null): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'] ?: ($leaveType?->code ?: $this->uniqueCode(LeaveType::class, $data['name'])),
            'is_paid' => $request->boolean('is_paid'),
            'annual_limit' => $data['annual_limit'],
            'carry_forward_allowed' => $request->boolean('carry_forward_allowed'),
            'maximum_carry_forward' => $request->boolean('carry_forward_allowed') ? ($data['maximum_carry_forward'] ?? 0) : null,
            'requires_document' => $request->boolean('requires_document'),
            'status' => $request->boolean('status'),
        ];
    }

    private function validateHoliday(Request $request, ?Holiday $holiday = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => [
                'required', 'date',
                Rule::unique('holidays')->where(fn ($query) => $query->where('name', $request->name))->ignore($holiday?->id),
            ],
            'holiday_type' => ['required', Rule::in(['government', 'festival', 'restaurant', 'special'])],
            'is_paid' => 'nullable|boolean',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);
    }

    private function holidayPayload(Request $request, array $data): array
    {
        return [
            'name' => $data['name'],
            'holiday_date' => $data['holiday_date'],
            'holiday_type' => $data['holiday_type'],
            'is_paid' => $request->boolean('is_paid'),
            'description' => $data['description'] ?? null,
            'status' => $request->boolean('status'),
        ];
    }

    private function validateSalaryComponent(Request $request, ?SalaryComponent $salaryComponent = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('salary_components', 'code')->ignore($salaryComponent?->id)],
            'component_type' => ['required', Rule::in(['earning', 'deduction'])],
            'calculation_type' => ['required', Rule::in(['fixed', 'percentage', 'formula', 'manual'])],
            'default_amount' => 'nullable|numeric|min:0',
            'default_percentage' => 'nullable|numeric|min:0|max:100',
            'is_taxable' => 'nullable|boolean',
            'is_attendance_based' => 'nullable|boolean',
            'is_overtime_component' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);
    }

    private function salaryComponentPayload(Request $request, array $data, ?SalaryComponent $salaryComponent = null): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'] ?: ($salaryComponent?->code ?: $this->uniqueCode(SalaryComponent::class, $data['name'])),
            'component_type' => $data['component_type'],
            'calculation_type' => $data['calculation_type'],
            'default_amount' => $data['calculation_type'] === 'fixed' ? ($data['default_amount'] ?? 0) : ($data['default_amount'] ?? null),
            'default_percentage' => $data['calculation_type'] === 'percentage' ? ($data['default_percentage'] ?? 0) : ($data['default_percentage'] ?? null),
            'is_taxable' => $request->boolean('is_taxable'),
            'is_attendance_based' => $request->boolean('is_attendance_based'),
            'is_overtime_component' => $request->boolean('is_overtime_component'),
            'status' => $request->boolean('status'),
        ];
    }

    private function uniqueCode(string $modelClass, string $source): string
    {
        $base = strtoupper(Str::slug($source, '_')) ?: 'ITEM';
        $base = substr($base, 0, 24);
        $code = $base;
        $counter = 1;

        while ($modelClass::where('code', $code)->exists()) {
            $code = substr($base, 0, 24) . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    private function inUse(string $label, string $tab)
    {
        return redirect()->route('hr.settings.index', ['tab' => $tab])
            ->with('error', "{$label} is already in use. Make it inactive instead of deleting it.");
    }

    private function deleteSetting($model, string $label, string $tab)
    {
        try {
            $model->delete();
            return $this->success("{$label} deleted successfully.", $tab);
        } catch (QueryException $exception) {
            return redirect()->route('hr.settings.index', ['tab' => $tab])
                ->with('error', "{$label} is already in use and cannot be deleted.");
        } catch (Exception $exception) {
            return redirect()->route('hr.settings.index', ['tab' => $tab])
                ->with('error', "{$label} could not be deleted.");
        }
    }

    private function success(string $message, string $tab)
    {
        return redirect()->route('hr.settings.index', ['tab' => $tab])->with('success', $message);
    }
}
