<?php

namespace App\Imports;

use App\Models\Employee;
use App\Services\Hr\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class AttendanceImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    public int $imported = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];

    public function __construct(private readonly ?int $userId = null)
    {
    }

    public function collection(Collection $rows): void
    {
        $service = app(AttendanceService::class);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            try {
                $employeeCode = trim((string) ($row['employee_code'] ?? $row['employee_id'] ?? ''));
                if ($employeeCode === '') {
                    throw new \InvalidArgumentException('employee_code is required.');
                }

                $employee = Employee::query()
                    ->where('employee_code', $employeeCode)
                    ->orWhere('id', ctype_digit($employeeCode) ? (int) $employeeCode : 0)
                    ->first();
                if (!$employee) {
                    throw new \InvalidArgumentException("Employee {$employeeCode} was not found.");
                }

                $dateValue = $row['attendance_date'] ?? $row['date'] ?? null;
                $date = $this->parseDate($dateValue);
                if (!$date) {
                    throw new \InvalidArgumentException('attendance_date is invalid.');
                }

                $checkIn = $this->parseTime($row['check_in'] ?? $row['in_time'] ?? null, $date);
                $checkOut = $this->parseTime($row['check_out'] ?? $row['out_time'] ?? null, $date);
                $existing = $employee->attendanceRecords()->whereDate('attendance_date', $date)->exists();

                $service->save($employee, $date, [
                    'shift_code' => trim((string) ($row['shift_code'] ?? $row['shift'] ?? '')) ?: null,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'break_minutes' => $row['break_minutes'] ?? null,
                    'status' => $this->normaliseStatus($row['status'] ?? null),
                    'remarks' => $row['remarks'] ?? $row['note'] ?? null,
                    'source' => 'excel_import',
                ], $this->userId);

                $existing ? $this->updated++ : $this->imported++;
            } catch (Throwable $exception) {
                $this->skipped++;
                if (count($this->errors) < 100) {
                    $this->errors[] = "Row {$rowNumber}: {$exception->getMessage()}";
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function parseDate($value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
        }
        if (!$value) {
            return null;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim((string) $value))->startOfDay();
            } catch (Throwable) {
            }
        }

        return Carbon::parse($value)->startOfDay();
    }

    private function parseTime($value, Carbon $date): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($date->toDateString().' '.$value->format('H:i:s'));
        }
        if (is_numeric($value)) {
            $dateTime = ExcelDate::excelToDateTimeObject((float) $value);
            return Carbon::parse($date->toDateString().' '.$dateTime->format('H:i:s'));
        }

        return Carbon::parse($date->toDateString().' '.trim((string) $value));
    }

    private function normaliseStatus($status): ?string
    {
        $status = strtolower(str_replace([' ', '-'], '_', trim((string) $status)));
        return in_array($status, ['present', 'late', 'absent', 'half_day', 'leave', 'holiday', 'off', 'missing_checkout'], true)
            ? $status
            : null;
    }
}
