<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmploymentType;
use App\Models\Shift;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Models\Waiter;
use App\Models\Zone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employee-view', ['only' => ['index', 'show']]);
        $this->middleware('permission:employee-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee-edit', ['only' => ['edit', 'update', 'updateStatus', 'storeDocument', 'storeSalaryComponent', 'updateSalaryComponent', 'destroySalaryComponent']]);
        $this->middleware('permission:employee-delete', ['only' => ['destroy', 'destroyDocument']]);
    }

    public function index(Request $request)
    {
        $query = Employee::with(['department', 'designation', 'employmentType', 'defaultShift', 'user', 'waiter'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->department_id);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', (int) $request->designation_id);
        }

        if ($request->filled('employment_type_id')) {
            $query->where('employment_type_id', (int) $request->employment_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->status);
        }

        $employees = $query->paginate(15)->withQueryString();
        $departments = Department::where('status', true)->orderBy('name')->get();
        $designations = Designation::where('status', true)->orderBy('name')->get();
        $employmentTypes = EmploymentType::where('status', true)->orderBy('name')->get();

        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'with_login' => Employee::whereNotNull('user_id')->count(),
            'waiters' => Employee::whereNotNull('waiter_id')->count(),
        ];

        return view('admin.hr.employees.index', compact(
            'employees', 'departments', 'designations', 'employmentTypes', 'stats'
        ));
    }

    public function create()
    {
        return view('admin.hr.employees.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $createUser = $request->boolean('create_user_account');
        $createWaiter = $request->boolean('create_waiter_profile');
        $validated = $this->validateEmployee($request, null, $createUser, $createWaiter);

        DB::beginTransaction();
        $imagePath = null;

        try {
            $employeeCode = $validated['employee_code'] ?: $this->generateEmployeeCode();
            $fullName = trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));
            $imagePath = $request->hasFile('image') ? $this->uploadEmployeeImage($request->file('image')) : null;

            $user = null;
            if ($createUser) {
                $user = $this->createUserAccount($validated, $fullName, $imagePath);
            }

            $waiter = null;
            if ($createWaiter) {
                $waiter = Waiter::create([
                    'user_id' => $user?->id,
                    'zone_id' => $validated['zone_id'],
                    'shift_id' => $validated['default_shift_id'],
                    'employee_id' => $this->uniqueWaiterCode($employeeCode),
                    'name' => $fullName,
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'image' => $imagePath,
                    'join_date' => $validated['join_date'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => in_array(($validated['status'] ?? 'active'), ['active', 'probation'], true),
                ]);
            }

            $employee = Employee::create($this->employeePayload(
                $validated,
                $employeeCode,
                $imagePath,
                $user?->id,
                $waiter?->id
            ));

            DB::commit();

            return redirect()->route('hr.employees.show', $employee)
                ->with('success', 'Employee created successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            if ($imagePath) {
                $this->deletePublicFile($imagePath);
            }
            Log::error('Employee create failed', ['error' => $exception->getMessage()]);

            return back()->withInput()->with('error', 'Employee could not be created: ' . $exception->getMessage());
        }
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department', 'designation', 'employmentType', 'defaultShift', 'supervisor',
            'user.roles', 'waiter.zone', 'documents', 'salaryComponents.salaryComponent',
        ]);

        $salaryComponents = SalaryComponent::where('status', true)
            ->orderBy('component_type')
            ->orderBy('name')
            ->get();

        return view('admin.hr.employees.show', compact('employee', 'salaryComponents'));
    }

    public function edit(Employee $employee)
    {
        $employee->load(['user.roles', 'waiter']);

        return view('admin.hr.employees.edit', array_merge(
            ['employee' => $employee],
            $this->formOptions($employee)
        ));
    }

    public function update(Request $request, Employee $employee)
    {
        $createUser = !$employee->user_id && $request->boolean('create_user_account');
        $createWaiter = !$employee->waiter_id && $request->boolean('create_waiter_profile');
        $validated = $this->validateEmployee($request, $employee, $createUser, $createWaiter);

        DB::beginTransaction();
        $oldImage = $employee->image;
        $newImagePath = null;

        try {
            if ($request->hasFile('image')) {
                $newImagePath = $this->uploadEmployeeImage($request->file('image'));
            }
            $imagePath = $newImagePath ?: $oldImage;
            $fullName = trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));

            $user = $employee->user;
            if (!$user && $createUser) {
                $user = $this->createUserAccount($validated, $fullName, $imagePath);
            } elseif ($user) {
                if (empty($user->user_id)) {
                    $user->user_id = $this->generateUserCode();
                    $user->save();
                }

                $user->update([
                    'name' => $fullName,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'] ?? null,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'image' => $imagePath,
                ]);

                if (!empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                    $user->save();
                }

                if (!empty($validated['account_role'])) {
                    $user->syncRoles([$validated['account_role']]);
                }
            }

            $waiter = $employee->waiter;
            if (!$waiter && $createWaiter) {
                $waiter = Waiter::create([
                    'user_id' => $user?->id,
                    'zone_id' => $validated['zone_id'],
                    'shift_id' => $validated['default_shift_id'],
                    'employee_id' => $this->uniqueWaiterCode($employee->employee_code),
                    'name' => $fullName,
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'image' => $imagePath,
                    'join_date' => $validated['join_date'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => in_array(($validated['status'] ?? 'active'), ['active', 'probation'], true),
                ]);
            } elseif ($waiter) {
                $waiter->update([
                    'user_id' => $user?->id ?: $waiter->user_id,
                    'zone_id' => $validated['zone_id'] ?? $waiter->zone_id,
                    'shift_id' => $validated['default_shift_id'] ?? $waiter->shift_id,
                    'name' => $fullName,
                    'phone' => $validated['phone'] ?? $waiter->phone,
                    'email' => $validated['email'] ?? null,
                    'image' => $imagePath,
                    'join_date' => $validated['join_date'],
                    'notes' => $validated['notes'] ?? null,
                    'status' => in_array(($validated['status'] ?? 'active'), ['active', 'probation'], true),
                ]);
            }

            $employee->update($this->employeePayload(
                $validated,
                $employee->employee_code,
                $imagePath,
                $user?->id ?: $employee->user_id,
                $waiter?->id ?: $employee->waiter_id,
                true
            ));

            DB::commit();

            if ($newImagePath && $oldImage && $oldImage !== $newImagePath) {
                $this->deletePublicFile($oldImage);
            }

            return redirect()->route('hr.employees.show', $employee)
                ->with('success', 'Employee updated successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            if ($newImagePath) {
                $this->deletePublicFile($newImagePath);
            }
            Log::error('Employee update failed', ['employee_id' => $employee->id, 'error' => $exception->getMessage()]);

            return back()->withInput()->with('error', 'Employee could not be updated: ' . $exception->getMessage());
        }
    }

    public function destroy(Employee $employee)
    {
        DB::beginTransaction();
        try {
            if ($employee->waiter) {
                $employee->waiter->update(['status' => false]);
            }

            $employee->delete();
            DB::commit();

            return redirect()->route('hr.employees.index')->with('success', 'Employee archived successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            return back()->with('error', 'Employee could not be archived.');
        }
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'probation', 'on_leave', 'resigned', 'terminated'])],
        ]);

        $employee->update([
            'status' => $validated['status'],
            'updated_by' => auth()->id(),
        ]);

        if ($employee->waiter) {
            $employee->waiter->update(['status' => in_array($validated['status'], ['active', 'probation'], true)]);
        }

        return back()->with('success', 'Employee status updated.');
    }

    public function storeDocument(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'document_notes' => 'nullable|string|max:1000',
        ]);

        $directory = public_path('uploads/employees/documents');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('document_file');
        $filename = 'employee_' . $employee->id . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'] ?? null,
            'file_path' => 'uploads/employees/documents/' . $filename,
            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['document_notes'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Employee document uploaded.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document)
    {
        abort_unless($document->employee_id === $employee->id, 404);
        $this->deletePublicFile($document->file_path);
        $document->delete();

        return back()->with('success', 'Employee document deleted.');
    }

    public function storeSalaryComponent(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployeeSalaryComponent($request, $employee);

        EmployeeSalaryComponent::create([
            'employee_id' => $employee->id,
            'salary_component_id' => $validated['salary_component_id'],
            'amount' => $validated['amount'] ?? null,
            'percentage' => $validated['percentage'] ?? null,
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return back()->with('success', 'Salary component assigned successfully.');
    }

    public function updateSalaryComponent(
        Request $request,
        Employee $employee,
        EmployeeSalaryComponent $employeeSalaryComponent
    ) {
        abort_unless($employeeSalaryComponent->employee_id === $employee->id, 404);
        $validated = $this->validateEmployeeSalaryComponent($request, $employee, $employeeSalaryComponent);

        $employeeSalaryComponent->update([
            'salary_component_id' => $validated['salary_component_id'],
            'amount' => $validated['amount'] ?? null,
            'percentage' => $validated['percentage'] ?? null,
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        return back()->with('success', 'Salary component updated successfully.');
    }

    public function destroySalaryComponent(
        Employee $employee,
        EmployeeSalaryComponent $employeeSalaryComponent
    ) {
        abort_unless($employeeSalaryComponent->employee_id === $employee->id, 404);
        $employeeSalaryComponent->delete();

        return back()->with('success', 'Salary component assignment deleted.');
    }

    private function validateEmployeeSalaryComponent(
        Request $request,
        Employee $employee,
        ?EmployeeSalaryComponent $assignment = null
    ): array {
        $validated = $request->validate([
            'salary_component_id' => [
                'required',
                'integer',
                'exists:salary_components,id',
                Rule::unique('employee_salary_components', 'salary_component_id')
                    ->where(fn ($query) => $query
                        ->where('employee_id', $employee->id)
                        ->where('effective_from', $request->effective_from))
                    ->ignore($assignment?->id),
            ],
            'amount' => 'nullable|numeric|min:0|max:999999999999.99',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'nullable|boolean',
        ]);

        $component = SalaryComponent::findOrFail($validated['salary_component_id']);
        if ($component->calculation_type === 'fixed' && !isset($validated['amount'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => 'Amount is required for a fixed salary component.',
            ]);
        }
        if ($component->calculation_type === 'percentage' && !isset($validated['percentage'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'percentage' => 'Percentage is required for a percentage salary component.',
            ]);
        }

        return $validated;
    }

    private function formOptions(?Employee $employee = null): array
    {
        return [
            'departments' => Department::where('status', true)->orderBy('name')->get(),
            'designations' => Designation::with('department')->where('status', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::where('status', true)->orderBy('name')->get(),
            'shifts' => Shift::where('status', true)->orderBy('name')->get(),
            'supervisors' => Employee::where('status', 'active')
                ->when($employee, fn ($query) => $query->where('id', '!=', $employee->id))
                ->orderBy('first_name')->get(),
            'zones' => Zone::where('status', true)->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
            'suggestedEmployeeCode' => $employee?->employee_code ?: $this->generateEmployeeCode(),
        ];
    }

    private function validateEmployee(Request $request, ?Employee $employee, bool $createUser, bool $createWaiter): array
    {
        $employeeId = $employee?->id;
        $userId = $employee?->user_id;
        $waiterId = $employee?->waiter_id;

        $emailRules = ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)];
        if ($createUser || $employee?->user_id) {
            $emailRules = ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId), Rule::unique('users', 'email')->ignore($userId)];
        }

        $phoneRules = ['nullable', 'string', 'max:30'];
        if ($createWaiter || $employee?->waiter_id) {
            $phoneRules = ['required', 'string', 'max:30'];
        }

        $passwordRules = ['nullable', 'string', 'min:8', 'confirmed'];
        if ($createUser) {
            $passwordRules = ['required', 'string', 'min:8', 'confirmed'];
        }

        $defaultShiftRules = ['nullable', 'integer', 'exists:shifts,id'];
        if ($createWaiter || (bool) $employee?->waiter_id) {
            $defaultShiftRules = ['required', 'integer', 'exists:shifts,id'];
        }

        $zoneRules = ['nullable', 'integer', 'exists:zones,id'];
        if ($createWaiter || (bool) $waiterId) {
            $zoneRules = ['required', 'integer', 'exists:zones,id'];
        }

        $accountRoleRules = ['nullable', 'string', 'exists:roles,name'];
        if ($createUser) {
            $accountRoleRules = ['required', 'string', 'exists:roles,name'];
        }

        $supervisorRules = ['nullable', 'integer', 'exists:employees,id'];
        if ($employeeId) {
            $supervisorRules[] = Rule::notIn([$employeeId]);
        }

        return $request->validate([
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => $phoneRules,
            'email' => $emailRules,
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation_id' => 'nullable|integer|exists:designations,id',
            'employment_type_id' => 'nullable|integer|exists:employment_types,id',
            'default_shift_id' => $defaultShiftRules,
            'supervisor_id' => $supervisorRules,
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'blood_group' => 'nullable|string|max:10',
            'nid_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'nid_number')->ignore($employeeId)],
            'passport_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'passport_number')->ignore($employeeId)],
            'present_address' => 'nullable|string|max:2000',
            'permanent_address' => 'nullable|string|max:2000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'join_date' => 'required|date',
            'confirmation_date' => 'nullable|date|after_or_equal:join_date',
            'resignation_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'payment_method' => ['nullable', Rule::in(['cash', 'bank', 'mobile_banking', 'cheque'])],
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'mobile_banking_type' => 'nullable|string|max:30',
            'mobile_banking_number' => 'nullable|string|max:30',
            'status' => ['required', Rule::in(['active', 'inactive', 'probation', 'on_leave', 'resigned', 'terminated'])],
            'notes' => 'nullable|string|max:3000',
            'create_user_account' => 'nullable|boolean',
            'account_role' => $accountRoleRules,
            'password' => $passwordRules,
            'create_waiter_profile' => 'nullable|boolean',
            'zone_id' => $zoneRules,
        ]);
    }

    private function createUserAccount(array $validated, string $fullName, ?string $imagePath): User
    {
        $user = User::create([
            'user_id' => $this->generateUserCode(),
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'image' => $imagePath,
            'password' => Hash::make($validated['password']),
        ]);

        $roleName = $validated['account_role'] ?? 'Employee';
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    private function employeePayload(
        array $validated,
        string $employeeCode,
        ?string $imagePath,
        ?int $userId,
        ?int $waiterId,
        bool $updating = false
    ): array {
        $payload = [
            'user_id' => $userId,
            'waiter_id' => $waiterId,
            'department_id' => $validated['department_id'] ?? null,
            'designation_id' => $validated['designation_id'] ?? null,
            'employment_type_id' => $validated['employment_type_id'] ?? null,
            'default_shift_id' => $validated['default_shift_id'] ?? null,
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            'employee_code' => $employeeCode,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'image' => $imagePath,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'nid_number' => $validated['nid_number'] ?? null,
            'passport_number' => $validated['passport_number'] ?? null,
            'present_address' => $validated['present_address'] ?? null,
            'permanent_address' => $validated['permanent_address'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relation' => $validated['emergency_contact_relation'] ?? null,
            'join_date' => $validated['join_date'],
            'confirmation_date' => $validated['confirmation_date'] ?? null,
            'resignation_date' => $validated['resignation_date'] ?? null,
            'termination_date' => $validated['termination_date'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'mobile_banking_type' => $validated['mobile_banking_type'] ?? null,
            'mobile_banking_number' => $validated['mobile_banking_number'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'updated_by' => auth()->id(),
        ];

        if (!$updating) {
            $payload['created_by'] = auth()->id();
        }

        return $payload;
    }

    private function generateEmployeeCode(): string
    {
        $number = ((int) Employee::withTrashed()->max('id')) + 1;
        do {
            $code = 'EMP-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (Employee::withTrashed()->where('employee_code', $code)->exists() || Waiter::where('employee_id', $code)->exists());

        return $code;
    }

    private function generateUserCode(): string
    {
        $number = ((int) User::max('id')) + 1;
        do {
            $code = 'PR-' . (1000 + $number);
            $number++;
        } while (User::where('user_id', $code)->exists());

        return $code;
    }

    private function uniqueWaiterCode(string $preferredCode): string
    {
        if (!Waiter::where('employee_id', $preferredCode)->exists()) {
            return $preferredCode;
        }

        $counter = 1;
        do {
            $code = $preferredCode . '-W' . $counter;
            $counter++;
        } while (Waiter::where('employee_id', $code)->exists());

        return $code;
    }

    private function uploadEmployeeImage($file): string
    {
        $directory = public_path('uploads/employees');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = 'employee_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/employees/' . $filename;
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (!$relativePath || str_starts_with($relativePath, 'http')) {
            return;
        }

        $path = public_path($relativePath);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
