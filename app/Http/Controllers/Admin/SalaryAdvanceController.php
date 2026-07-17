<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryAdvanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view')->only(['index']);
        $this->middleware('permission:hr-setting-manage')->except(['index']);
    }

    public function index(Request $request)
    {
        $advances = SalaryAdvance::query()
            ->with(['employee.department', 'approver', 'installments'])
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->department_id, fn ($q, $id) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $id)))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->advance_type, fn ($q, $type) => $q->where('advance_type', $type))
            ->latest('request_date')->paginate(20)->withQueryString();

        $employees = Employee::query()->where('status', 'active')->orderBy('first_name')->get();
        $departments = Department::query()->where('status', true)->orderBy('name')->get();
        return view('admin.hr.advances.index', compact('advances', 'employees', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'advance_type' => 'required|in:salary_advance,loan',
            'request_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'number_of_installments' => 'required|integer|min:1|max:120',
            'installment_amount' => 'nullable|numeric|min:0.01',
            'deduction_start_date' => 'required|date',
            'reason' => 'nullable|string|max:3000',
        ]);

        $installment = $data['installment_amount'] ?? ceil(((float) $data['amount'] / (int) $data['number_of_installments']) * 100) / 100;
        SalaryAdvance::create([
            ...$data,
            'installment_amount' => $installment,
            'remaining_amount' => $data['amount'],
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Salary advance / loan request created successfully.');
    }

    public function approve(SalaryAdvance $advance)
    {
        if ($advance->status !== 'pending') return back()->with('error', 'Only pending requests can be approved.');

        DB::transaction(function () use ($advance) {
            $advance->installments()->delete();
            $remaining = (float) $advance->amount;
            $installmentAmount = (float) ($advance->installment_amount ?: ($advance->amount / max(1, $advance->number_of_installments)));
            $dueDate = Carbon::parse($advance->deduction_start_date ?: now()->startOfMonth()->addMonth());

            for ($i = 1; $i <= (int) $advance->number_of_installments && $remaining > 0.009; $i++) {
                $amount = min($remaining, $installmentAmount);
                $advance->installments()->create([
                    'due_date' => $dueDate->copy()->addMonthsNoOverflow($i - 1)->toDateString(),
                    'amount' => round($amount, 2),
                    'paid_amount' => 0,
                    'status' => 'pending',
                ]);
                $remaining -= $amount;
            }

            $advance->update([
                'approved_date' => now()->toDateString(),
                'approved_by' => auth()->id(),
                'remaining_amount' => $advance->amount,
                'status' => 'active',
            ]);
        });

        return back()->with('success', 'Request approved and installment schedule generated.');
    }

    public function reject(Request $request, SalaryAdvance $advance)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:3000']);
        if ($advance->status !== 'pending') return back()->with('error', 'Only pending requests can be rejected.');
        $advance->update(['status' => 'rejected', 'reason' => trim(($advance->reason ? $advance->reason."\n" : '').'Rejection: '.($data['reason'] ?? 'Not specified')), 'approved_by' => auth()->id()]);
        return back()->with('success', 'Request rejected.');
    }

    public function repayment(Request $request, SalaryAdvance $advance)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:'.$advance->remaining_amount, 'paid_date' => 'required|date']);
        DB::transaction(function () use ($advance, $data) {
            $amountLeft = (float) $data['amount'];
            foreach ($advance->installments()->whereIn('status', ['pending', 'partial'])->orderBy('due_date')->lockForUpdate()->get() as $installment) {
                if ($amountLeft <= 0.009) break;
                $due = max(0, (float) $installment->amount - (float) $installment->paid_amount);
                $pay = min($due, $amountLeft);
                $newPaid = (float) $installment->paid_amount + $pay;
                $installment->update([
                    'paid_amount' => $newPaid,
                    'paid_date' => $data['paid_date'],
                    'status' => $newPaid + 0.009 >= (float) $installment->amount ? 'paid' : 'partial',
                ]);
                $amountLeft -= $pay;
            }
            $paid = (float) $advance->installments()->sum('paid_amount');
            $remaining = max(0, (float) $advance->amount - $paid);
            $advance->update(['paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $remaining <= 0.009 ? 'paid' : 'active']);
        });
        return back()->with('success', 'Repayment recorded successfully.');
    }

    public function destroy(SalaryAdvance $advance)
    {
        if ($advance->status !== 'pending') return back()->with('error', 'Only pending requests can be deleted.');
        $advance->delete();
        return back()->with('success', 'Request deleted.');
    }
}
