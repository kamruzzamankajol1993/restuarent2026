<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollSetting;
use App\Models\RestaurantSetting;
use App\Services\Hr\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:hr-dashboard-view')->only(['index', 'show', 'payslip']);
        $this->middleware('permission:hr-setting-manage')->except(['index', 'show', 'payslip']);
    }

    public function index(Request $request)
    {
        $periods = PayrollPeriod::query()
            ->withCount('payrolls')
            ->withSum('payrolls', 'net_salary')
            ->withSum('payrolls', 'paid_amount')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('start_date')->paginate(15)->withQueryString();

        $settings = PayrollSetting::query()->first() ?? new PayrollSetting(PayrollSetting::defaults());
        return view('admin.hr.payroll.index', compact('periods', 'settings'));
    }

    public function storePeriod(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'payment_date' => 'nullable|date|after_or_equal:end_date',
        ]);

        $overlap = PayrollPeriod::query()->whereDate('start_date', '<=', $data['end_date'])->whereDate('end_date', '>=', $data['start_date'])->exists();
        if ($overlap) return back()->withInput()->with('error', 'This payroll period overlaps an existing period.');

        $period = PayrollPeriod::create([...$data, 'status' => 'draft', 'generated_by' => auth()->id()]);
        return redirect()->route('hr.payroll.show', $period)->with('success', 'Payroll period created successfully.');
    }

    public function show(Request $request, PayrollPeriod $period)
    {
        $payrolls = Payroll::query()->with(['employee.department', 'employee.designation'])
            ->where('payroll_period_id', $period->id)
            ->when($request->employee_id, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->payment_status, fn ($q, $status) => $q->where('payment_status', $status))
            ->orderBy('employee_id')->paginate(25)->withQueryString();
        $employees = Employee::query()->where('status', 'active')->orderBy('first_name')->get();
        return view('admin.hr.payroll.show', compact('period', 'payrolls', 'employees'));
    }

    public function generate(Request $request, PayrollPeriod $period, PayrollService $service)
    {
        if (in_array($period->status, ['approved', 'locked'], true)) return back()->with('error', 'Approved/locked payroll periods cannot be regenerated.');
        $data = $request->validate(['employee_ids' => 'nullable|array', 'employee_ids.*' => 'exists:employees,id']);
        $employees = Employee::query()->where('status', 'active')->when($data['employee_ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', $ids))->get();
        $generated = 0; $errors = [];
        foreach ($employees as $employee) {
            try { $service->generate($period, $employee, auth()->id()); $generated++; }
            catch (\Throwable $e) { $errors[] = $employee->full_name.': '.$e->getMessage(); }
        }
        $period->update(['status' => 'generated', 'generated_by' => auth()->id()]);
        return back()->with('success', "{$generated} payroll(s) generated.")->with('payroll_errors', array_slice($errors, 0, 50));
    }

    public function regenerate(Payroll $payroll, PayrollService $service)
    {
        $service->generate($payroll->period, $payroll->employee, auth()->id());
        return back()->with('success', 'Payroll recalculated successfully.');
    }

    public function approve(PayrollPeriod $period)
    {
        DB::transaction(function () use ($period) {
            $period->payrolls()->whereNotIn('payroll_status', ['paid'])->update(['payroll_status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
            $settings = PayrollSetting::query()->first() ?? new PayrollSetting(PayrollSetting::defaults());
            $period->update([
                'status' => $settings->lock_after_approval ? 'locked' : 'approved',
                'approved_by' => auth()->id(), 'approved_at' => now(),
                'locked_at' => $settings->lock_after_approval ? now() : null,
            ]);
        });
        return back()->with('success', 'Payroll period approved successfully.');
    }

    public function payment(Request $request, Payroll $payroll)
    {
        $data = $request->validate([
            'payment_date' => 'required|date', 'amount' => 'required|numeric|min:0.01|max:'.$payroll->due_amount,
            'payment_method' => 'required|in:cash,bank,mobile_banking,cheque,other',
            'reference_number' => 'nullable|string|max:100', 'bank_account' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($payroll, $data) {
            PayrollPayment::create([...$data, 'payroll_id' => $payroll->id, 'paid_by' => auth()->id()]);
            $paid = (float) $payroll->payments()->sum('amount');
            $due = max(0, (float) $payroll->net_salary - $paid);
            $status = $due <= 0.009 ? 'paid' : 'partial';
            $payroll->update(['paid_amount' => $paid, 'due_amount' => $due, 'payment_status' => $status, 'payroll_status' => $status === 'paid' ? 'paid' : $payroll->payroll_status]);

            if ($status === 'paid') {
                foreach ($payroll->advanceInstallments()->with('salaryAdvance')->get() as $installment) {
                    $remaining = max(0, (float) $installment->amount - (float) $installment->paid_amount);
                    $installment->update(['paid_amount' => $installment->amount, 'paid_date' => $data['payment_date'], 'status' => 'paid']);
                    $advance = $installment->salaryAdvance;
                    if ($advance) {
                        $advancePaid = (float) $advance->installments()->sum('paid_amount');
                        $advanceRemaining = max(0, (float) $advance->amount - $advancePaid);
                        $advance->update(['paid_amount' => $advancePaid, 'remaining_amount' => $advanceRemaining, 'status' => $advanceRemaining <= 0.009 ? 'paid' : 'active']);
                    }
                }
            }
        });
        return back()->with('success', 'Payroll payment recorded successfully.');
    }

    public function payslip(Payroll $payroll)
    {
        $payroll->load(['period', 'employee.department', 'employee.designation', 'items', 'payments']);
        $restaurant = RestaurantSetting::query()->first();
        $settings = PayrollSetting::query()->first() ?? new PayrollSetting(PayrollSetting::defaults());
        $html = view('admin.hr.payroll.payslip', compact('payroll', 'restaurant', 'settings'))->render();
        $tempDir = storage_path('app/mpdf'); File::ensureDirectoryExists($tempDir);
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tempDir, 'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12]);
        $mpdf->WriteHTML($html);
        $filename = ($settings->payslip_prefix ?: 'PAY').'-'.$payroll->period->start_date->format('Ym').'-'.$payroll->employee->employee_code.'.pdf';
        return response($mpdf->Output($filename, Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
