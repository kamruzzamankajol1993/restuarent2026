<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmploymentType;
use App\Models\LeaveType;
use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class HrMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Management', 'code' => 'MANAGEMENT'],
            ['name' => 'Kitchen', 'code' => 'KITCHEN'],
            ['name' => 'Service', 'code' => 'SERVICE'],
            ['name' => 'Cash Counter', 'code' => 'CASH_COUNTER'],
            ['name' => 'Delivery', 'code' => 'DELIVERY'],
            ['name' => 'Cleaning', 'code' => 'CLEANING'],
            ['name' => 'Accounts', 'code' => 'ACCOUNTS'],
            ['name' => 'Human Resources', 'code' => 'HR'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(['code' => $department['code']], $department + ['status' => true]);
        }

        $designationMap = [
            ['name' => 'Restaurant Manager', 'code' => 'RESTAURANT_MANAGER', 'department' => 'MANAGEMENT', 'level' => 1],
            ['name' => 'Head Chef', 'code' => 'HEAD_CHEF', 'department' => 'KITCHEN', 'level' => 2],
            ['name' => 'Assistant Chef', 'code' => 'ASSISTANT_CHEF', 'department' => 'KITCHEN', 'level' => 3],
            ['name' => 'Waiter', 'code' => 'WAITER', 'department' => 'SERVICE', 'level' => 4],
            ['name' => 'Cashier', 'code' => 'CASHIER', 'department' => 'CASH_COUNTER', 'level' => 3],
            ['name' => 'Delivery Rider', 'code' => 'DELIVERY_RIDER', 'department' => 'DELIVERY', 'level' => 4],
            ['name' => 'Cleaner', 'code' => 'CLEANER', 'department' => 'CLEANING', 'level' => 4],
            ['name' => 'Accountant', 'code' => 'ACCOUNTANT', 'department' => 'ACCOUNTS', 'level' => 2],
            ['name' => 'HR Officer', 'code' => 'HR_OFFICER', 'department' => 'HR', 'level' => 2],
        ];

        foreach ($designationMap as $designation) {
            $departmentId = Department::where('code', $designation['department'])->value('id');
            Designation::firstOrCreate(
                ['code' => $designation['code']],
                [
                    'name' => $designation['name'],
                    'department_id' => $departmentId,
                    'level' => $designation['level'],
                    'status' => true,
                ]
            );
        }

        foreach ([
            ['name' => 'Permanent', 'code' => 'PERMANENT'],
            ['name' => 'Probation', 'code' => 'PROBATION'],
            ['name' => 'Contract', 'code' => 'CONTRACT'],
            ['name' => 'Part Time', 'code' => 'PART_TIME'],
            ['name' => 'Casual / Daily', 'code' => 'CASUAL_DAILY'],
            ['name' => 'Intern', 'code' => 'INTERN'],
        ] as $type) {
            EmploymentType::firstOrCreate(['code' => $type['code']], $type + ['status' => true]);
        }

        foreach ([
            ['name' => 'Casual Leave', 'code' => 'CL', 'annual_limit' => 10, 'is_paid' => true],
            ['name' => 'Sick Leave', 'code' => 'SL', 'annual_limit' => 14, 'is_paid' => true, 'requires_document' => true],
            ['name' => 'Annual Leave', 'code' => 'AL', 'annual_limit' => 15, 'is_paid' => true, 'carry_forward_allowed' => true, 'maximum_carry_forward' => 5],
            ['name' => 'Unpaid Leave', 'code' => 'UL', 'annual_limit' => 0, 'is_paid' => false],
        ] as $leaveType) {
            LeaveType::firstOrCreate(
                ['code' => $leaveType['code']],
                array_merge([
                    'status' => true,
                    'carry_forward_allowed' => false,
                    'requires_document' => false,
                ], $leaveType)
            );
        }

        foreach ([
            ['name' => 'Basic Salary', 'code' => 'BASIC', 'component_type' => 'earning', 'calculation_type' => 'fixed'],
            ['name' => 'Food Allowance', 'code' => 'FOOD_ALLOWANCE', 'component_type' => 'earning', 'calculation_type' => 'fixed'],
            ['name' => 'Transport Allowance', 'code' => 'TRANSPORT_ALLOWANCE', 'component_type' => 'earning', 'calculation_type' => 'fixed'],
            ['name' => 'Attendance Bonus', 'code' => 'ATTENDANCE_BONUS', 'component_type' => 'earning', 'calculation_type' => 'fixed', 'is_attendance_based' => true],
            ['name' => 'Overtime', 'code' => 'OVERTIME', 'component_type' => 'earning', 'calculation_type' => 'manual', 'is_overtime_component' => true],
            ['name' => 'Absent Deduction', 'code' => 'ABSENT_DEDUCTION', 'component_type' => 'deduction', 'calculation_type' => 'manual', 'is_attendance_based' => true],
            ['name' => 'Late Deduction', 'code' => 'LATE_DEDUCTION', 'component_type' => 'deduction', 'calculation_type' => 'manual', 'is_attendance_based' => true],
            ['name' => 'Loan Installment', 'code' => 'LOAN_INSTALLMENT', 'component_type' => 'deduction', 'calculation_type' => 'manual'],
        ] as $component) {
            SalaryComponent::firstOrCreate(
                ['code' => $component['code']],
                array_merge([
                    'status' => true,
                    'is_taxable' => false,
                    'is_attendance_based' => false,
                    'is_overtime_component' => false,
                ], $component)
            );
        }
    }
}
