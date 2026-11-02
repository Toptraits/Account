<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\FeeReminder;
use App\Models\PaymentCommitment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show()
    {
        $today = now()->toDateString();
        $target = (float) config(
            'school.daily_collection_target',
            450000
        );

        // Today's actual verified collection.
        $collection = (float) FeePayment::query()
            ->whereDate('received_at', $today)
            ->where('status', 'verified')
            ->sum('amount');

        // Count all receipts issued today.
        $receiptsToday = FeePayment::query()
            ->whereDate('received_at', $today)
            ->count();

        // The payment controller creates receipt numbers from payment IDs.
        $nextPaymentId = ((int) DB::table('fee_payments')->max('id')) + 1;

        $nextReceiptNumber = 'R-' . str_pad(
            (string) $nextPaymentId,
            6,
            '0',
            STR_PAD_LEFT
        );

        // Sum amounts promised for today, rather than counting promises.
        $promisedToday = (float) PaymentCommitment::query()
            ->whereDate('promised_date', $today)
            ->where('status', 'open')
            ->sum('promised_amount');

        // Calculate verified allocations against each individual fee.
        $paidByFee = DB::table('fee_payment_items')
            ->join(
                'fee_payments',
                'fee_payments.id',
                '=',
                'fee_payment_items.fee_payment_id'
            )
            ->where('fee_payments.status', 'verified')
            ->select(
                'fee_payment_items.student_fee_id',
                DB::raw('SUM(fee_payment_items.amount) as paid_amount')
            )
            ->groupBy('fee_payment_items.student_fee_id')
            ->pluck('paid_amount', 'student_fee_id');

        $students = Student::query()
            ->where('is_active', true)
            ->with('fees')
            ->get();

        $totalOutstanding = 0.0;
        $aging = [
            'not_yet_due' => [
                'amount' => 0.0,
                'students' => [],
            ],
            'overdue_0_7_days' => [
                'amount' => 0.0,
                'students' => [],
            ],
            'overdue_8_30_days' => [
                'amount' => 0.0,
                'students' => [],
            ],
            'overdue_over_30_days' => [
                'amount' => 0.0,
                'students' => [],
            ],
        ];

        foreach ($students as $student) {
            foreach ($student->fees as $fee) {
                $paid = (float) ($paidByFee[$fee->id] ?? 0);
                $balance = max(0, (float) $fee->amount - $paid);

                if ($balance <= 0) {
                    continue;
                }

                $totalOutstanding += $balance;
                $dueDate = $fee->due_date->toDateString();

                // Today and future dates are not overdue.
                if ($dueDate >= $today) {
                    $bucket = 'not_yet_due';
                } else {
                    $daysOverdue = (int) \Carbon\Carbon::parse($dueDate)
    ->startOfDay()
    ->diffInDays(now()->startOfDay());

                    if ($daysOverdue <= 7) {
                        $bucket = 'overdue_0_7_days';
                    } elseif ($daysOverdue <= 30) {
                        $bucket = 'overdue_8_30_days';
                    } else {
                        $bucket = 'overdue_over_30_days';
                    }
                }

                $aging[$bucket]['amount'] += $balance;
                $aging[$bucket]['students'][$student->id] = true;
            }
        }

        foreach ($aging as &$bucket) {
            $bucket['amount'] = round($bucket['amount'], 2);
            $bucket['student_count'] = count($bucket['students']);
            unset($bucket['students']);
        }
        unset($bucket);

        $overdueOutstanding =
            $aging['overdue_0_7_days']['amount']
            + $aging['overdue_8_30_days']['amount']
            + $aging['overdue_over_30_days']['amount'];

        return response()->json([
            'data' => [
                'date' => $today,
                'today_collection' => round($collection, 2),
                'daily_target' => round($target, 2),
                'still_to_collect' => round(
                    max(0, $target - $collection),
                    2
                ),
                'collection_progress_percent' => $target > 0
                    ? round(($collection / $target) * 100, 2)
                    : 0,

                'receipts_today' => $receiptsToday,
                'next_receipt_number' => $nextReceiptNumber,
                'promised_amount_today' => round($promisedToday, 2),

                'total_outstanding' => round($totalOutstanding, 2),
                'overdue_outstanding' => round(
                    $overdueOutstanding,
                    2
                ),
                'overdue_percent' => $totalOutstanding > 0
                    ? round(
                        ($overdueOutstanding / $totalOutstanding) * 100,
                        2
                    )
                    : 0,

                'aging' => $aging,
                'active_students' => $students->count(),

                'commitments_due_today' => PaymentCommitment::query()
                    ->whereDate('promised_date', $today)
                    ->where('status', 'open')
                    ->count(),

                'pending_reminders' => FeeReminder::query()
                    ->where('status', 'queued')
                    ->count(),

                'attention' => [
                    'unmatched_bank_credits' => 0,
                    'payments_pending_verification' =>
                        FeePayment::where('status', 'pending')->count(),
                    'backdated_entries_pending' => 0,
                    'missed_promises' => PaymentCommitment::query()
                        ->where('status', 'missed')
                        ->count(),
                ],
            ],
        ]);
    }
}

