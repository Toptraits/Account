<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentCommitment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    private function shape(Student $s): array
    {
        $today = now()->toDateString();

        $promise = PaymentCommitment::where('student_id', $s->id)
            ->where('status', 'open')
            ->where('outcome', 'promise')
            ->orderByDesc('promised_date')
            ->orderByDesc('id')
            ->first();

        $promiseToday = PaymentCommitment::where('student_id', $s->id)
            ->where('status', 'open')
            ->where('outcome', 'promise')
            ->whereDate('promised_date', $today)
            ->exists();

        $fees = $s->fees()->with('feeType')->get();
        $feeIds = $fees->pluck('id');
        $paidByFee = collect();

        if ($feeIds->isNotEmpty()) {
            $paidByFee = DB::table('fee_payment_items')
                ->join(
                    'fee_payments',
                    'fee_payments.id',
                    '=',
                    'fee_payment_items.fee_payment_id'
                )
                ->where('fee_payments.status', 'verified')
                ->whereIn('fee_payment_items.student_fee_id', $feeIds)
                ->select(
                    'fee_payment_items.student_fee_id',
                    DB::raw('SUM(fee_payment_items.amount) as paid_amount')
                )
                ->groupBy('fee_payment_items.student_fee_id')
                ->pluck('paid_amount', 'student_fee_id');
        }

        $outstanding = 0.0;
        $overdueAmount = 0.0;
        $overdueDays = 0;
        $feeRows = $fees->map(function ($f) use (
            $paidByFee, $today, &$outstanding, &$overdueAmount, &$overdueDays
        ) {
            $paid = (float) ($paidByFee[$f->id] ?? 0);
            $balance = round(max(0, (float) $f->amount - $paid), 2);
            $dueDate = $f->due_date?->toDateString();
            $isOverdue = $balance > 0 && $dueDate !== null && $dueDate < $today;
            $daysLate = 0;

            if ($isOverdue) {
                $daysLate = (int) \Carbon\Carbon::parse($dueDate)
                    ->startOfDay()
                    ->diffInDays(now()->startOfDay());
                $overdueAmount += $balance;
                $overdueDays = max($overdueDays, $daysLate);
            }

            $outstanding += $balance;

            return [
                'id' => $f->id,
                'fee_type' => $f->feeType?->name,
                'description' => $f->description,
                'amount' => $f->amount,
                'due_date' => $dueDate,
                'balance' => $balance,
                'is_overdue' => $isOverdue,
                'days_overdue' => $daysLate,
            ];
        });

        return [
            'id' => $s->id,
            'admission_number' => $s->admission_number,
            'name' => $s->name,
            'class_name' => $s->class_name,
            'parent_name' => $s->parent_name,
            'parent_mobile' => $s->parent_mobile,
            'total_fees' => $s->total_fees,
            'total_paid' => $s->total_paid,
            'outstanding' => round($outstanding, 2),
            'overdue_amount' => round($overdueAmount, 2),
            'overdue_days' => $overdueDays,
            'has_overdue' => $overdueAmount > 0,
            'promise_today' => $promiseToday,
            'school_date' => $today,
            'promised_date' => $promise?->promised_date?->toDateString(),
            'promised_amount' => $promise?->promised_amount,
            'fees' => $feeRows->values(),
        ];
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'class_name' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = Student::query()->where('is_active', true);

        if (!empty($data['search'])) {
            $term = $data['search'];

            $q->where(fn ($x) => $x
                ->where('name', 'ilike', '%' . $term . '%')
                ->orWhere('admission_number', 'ilike', '%' . $term . '%')
                ->orWhere('class_name', 'ilike', '%' . $term . '%'));
        }

        if (!empty($data['class_name'])) {
            $q->where('class_name', $data['class_name']);
        }

        $rows = $q->orderBy('name')->paginate($data['per_page'] ?? 25);
        $rows->getCollection()->transform(fn ($s) => $this->shape($s));

        return response()->json($rows);
    }

    public function show(Student $student)
    {
        return response()->json(['data' => $this->shape($student)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'admission_number' => [
                'required', 'string', 'max:40', 'unique:students,admission_number',
            ],
            'name' => ['required', 'string', 'max:150'],
            'class_name' => ['required', 'string', 'max:40'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_mobile' => [
                'nullable', 'regex:/^[6-9][0-9]{9}$/',
            ],
            'section' => ['nullable', 'string', 'max:40'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'religion' => ['nullable', 'string', 'max:100'],
            'caste' => ['nullable', 'string', 'max:100'],
            'aadhaar_number' => [
                'nullable', 'regex:/^[0-9]{12}$/',
            ],
        ]);

        $student = Student::create($data + ['is_active' => true]);

        return response()->json([
            'message' => 'Student created.',
            'data' => $this->shape($student),
        ], 201);
    }
}
