<?php

namespace App\Services\Admin;

use App\Services\Principal\Copilot\PrincipalCopilot;
use App\Services\Principal\Insights\Format;

/**
 * Admin Copilot: office questions first (fees desk, UPI, admissions, seats, certificates,
 * data health, staff), then the shared school Copilot (marks, attendance, students).
 * Returns null when it cannot answer — the page then asks the AI service.
 */
class AdminCopilot
{
    public function __construct(private AdminWorkspace $ws)
    {
    }

    /** @return array{answer:string, tab:?string, state:array}|null */
    public function answer(string $question): ?array
    {
        $q = ' ' . mb_strtolower(trim(preg_replace('/\s+/', ' ', $question))) . ' ';
        preg_match('/\b([6-9])\s*[- ]?\s*([ab])\b/', $q, $m);
        $section = $m ? $m[1] . '-' . strtoupper($m[2]) : null;
        preg_match('/class\s*([6-9])\b/', $q, $cm);
        $class = isset($cm[1]) ? (string) $cm[1] : (isset($m[1]) ? (string) $m[1] : null);

        if (preg_match('/^\s*(help|\?|what can you do|examples?)\s*$/', $q)) {
            return $this->r("Ask in plain words, for example:\n• fee defaulters in 8A · who should I call today · overdue more than 60 days\n• today's UPI payments · unmatched payments\n• applications ready to verify · seats left in class 7 · documents pending\n• certificates pending · is Rohan Das eligible for TC\n• who is absent today · staff on leave\n• data problems · same parent\n• tell me about Meera Nair");
        }

        // A student's name → open the 360 profile.
        foreach ($this->ws->studentsById() as $id => $s) {
            if (str_contains($q, ' ' . mb_strtolower($s['name']) . ' ') || str_contains($q, mb_strtolower($id))) {
                $p = $this->ws->student($id);
                $fee = $p['fee'] ? Format::inr($p['fee']['amount']) . " overdue ({$p['fee']['days']} days) → {$p['fee']['action']}" : 'No fees overdue';
                $tc = preg_match('/\btc\b|transfer/', $q) ? "\nTC: " . ($p['fee'] ? 'Not yet — clear ' . Format::inr($p['fee']['amount']) . ' first.' : 'Eligible. No fee dues.') : '';

                return $this->r("{$p['name']} · {$p['class']} · parent {$p['parent']} ({$p['phone']})\n• Attendance {$p['attendance']}%" . ($p['streak'] >= 2 ? " · absent {$p['streak']} days running" : '') . "\n• Average {$p['average']}%\n• {$fee}" . ($p['missing'] ? "\n• Record missing: " . implode(', ', $p['missing']) : '') . $tc, 'students', ['profileId' => $id]);
            }
        }

        if (preg_match('/upi|bank|reconcil|payment(s)? (today|received)|unmatched/', $q)) {
            $lines = $this->ws->upi();
            $want = str_contains($q, 'unmatched') ? array_filter($lines, fn ($l) => $l['status'] !== 'Auto-matched') : $lines;
            $txt = array_map(fn ($l) => "• {$l['time']} ₹" . number_format($l['amount']) . " from {$l['payer']} → " . ($l['student'] ?? 'no match') . " ({$l['confidence']}%, {$l['status']})", $want);

            return $this->r("Today's UPI lines: " . count($lines) . "\n" . implode("\n", $txt), 'fees', ['feeView' => 'upi']);
        }

        if (preg_match('/call|phone today|who should i (call|contact)/', $q) && preg_match('/fee|call today|should i call/', $q)) {
            $fd = $this->ws->feeDesk();
            $txt = array_map(fn ($r) => "• {$r['parent']} ({$r['name']}, {$r['class']}) {$r['phone']} — " . Format::inr($r['amount']) . ", {$r['days']} days", $fd['to_call']);

            return $this->r('Call these families (no payment this year): ' . count($txt) . "\n" . implode("\n", $txt) . ($fd['sensitive'] ? "\n\nHeld for the counsellor first: " . implode(', ', array_column($fd['sensitive'], 'name')) : ''), 'fees');
        }

        if (preg_match('/\bfees?\b|defaulter|overdue|dues|pending amount|unpaid/', $q)) {
            $fd = $this->ws->feeDesk();
            $rows = $fd['rows'];
            if ($section) {
                $rows = array_filter($rows, fn ($r) => $r['class'] === $section);
            } elseif ($class) {
                $rows = array_filter($rows, fn ($r) => str_starts_with($r['class'], $class . '-'));
            }
            if (preg_match('/(?:more than|over|above|>)\s*(\d+)\s*days?/', $q, $d)) {
                $rows = array_filter($rows, fn ($r) => $r['days'] > (int) $d[1]);
            }
            if (preg_match('/(?:more than|over|above|>)\s*(?:rs\.?|₹)?\s*(\d+)\s*(k|000)?\b(?!\s*days)/', $q, $a)) {
                $min = (int) $a[1] * (! empty($a[2]) ? 1000 : 1);
                $rows = $min >= 100 ? array_filter($rows, fn ($r) => $r['amount'] > $min) : $rows;
            }
            $rows = array_values($rows);
            $txt = array_map(fn ($r) => "• {$r['name']} ({$r['class']}) " . Format::inr($r['amount']) . " · {$r['days']} days → {$r['action']}", array_slice($rows, 0, 15));

            return $this->r(Format::plural(count($rows), 'family', 'families') . ' overdue' . ($section ? " in {$section}" : '') . ' · ' . Format::inr(array_sum(array_column($rows, 'amount'))) . "\n" . implode("\n", $txt) . (count($rows) > 15 ? "\n…and " . (count($rows) - 15) . ' more on the Fees tab.' : ''), 'fees', $section ? ['feeClass' => $section] : []);
        }

        if (preg_match('/seat|vacanc|capacity/', $q)) {
            $seats = $this->ws->admissions()['seats'];
            $seats = $class ? array_filter($seats, fn ($s) => $s['class'] === $class) : $seats;
            $txt = array_map(fn ($s) => "• Class {$s['class']}: {$s['seats']} seats · {$s['offers']} offers · " . ($s['left'] < 0 ? 'over-booked by ' . abs($s['left']) : "{$s['left']} left") . " · {$s['pipeline']} more in pipeline", $seats);

            return $this->r("Seats for {$this->ws->admissions()['year']}:\n" . implode("\n", $txt), 'admissions');
        }

        if (preg_match('/applica|admission|applicant|enquir|document/', $q)) {
            $a = $this->ws->admissions();
            if (preg_match('/document|docs|pending|stuck/', $q)) {
                $list = array_filter($a['applicants'], fn ($x) => $x['stage'] >= 0 && $x['stage'] <= 2 && $x['missing']);
                $txt = array_map(fn ($x) => "• {$x['name']} (Class {$x['class']}) — missing " . implode(', ', $x['missing']), array_slice($list, 0, 12));

                return $this->r('Applicants waiting for documents: ' . count($list) . "\n" . implode("\n", $txt), 'admissions', ['admFilter' => 'docs']);
            }
            if (preg_match('/ready|verify/', $q)) {
                $txt = array_map(fn ($x) => "• {$x['name']} (Class {$x['class']}) · {$x['confidence']}% sure", $a['ready']);

                return $this->r('Ready to verify: ' . count($a['ready']) . "\n" . implode("\n", $txt), 'admissions', ['admFilter' => 'ready']);
            }

            return $this->r("Admissions {$a['year']} (open since {$a['open_since']}): {$a['active']} active · +{$a['new_today']} today · {$a['last7']} this week\n" . implode("\n", array_map(fn ($f) => "• {$f['label']}: {$f['count']}", $a['funnel'])) . "\nConversion to offer: {$a['conversion']}%", 'admissions');
        }

        if (preg_match('/certificate|bonafide|\btc\b|request/', $q)) {
            $open = array_filter($this->ws->requests(), fn ($r) => $r['status'] !== 'Issued');
            $txt = array_map(fn ($r) => "• {$r['type']} · {$r['student']} ({$r['class']}) · {$r['status']}" . ($r['blocked'] ? " — {$r['blocked']}" : ''), $open);

            return $this->r('Open requests: ' . count($open) . "\n" . implode("\n", $txt), 'requests');
        }

        if (preg_match('/staff|teacher|leave|cover|substitut/', $q)) {
            $sd = $this->ws->staffDesk();

            return $this->r("Staff today: {$sd['present']} present · " . count($sd['on_leave']) . ' on leave' . ($sd['on_leave'] ? ' (' . implode(', ', array_column($sd['on_leave'], 'name')) . ')' : '') . "\nPeriods needing cover: " . count($sd['gaps']) . ($sd['unassigned'] ? ' · ' . count($sd['unassigned']) . ' with no free teacher' : '') . ($sd['leave_requests'] ? "\nUpcoming leave: " . implode(', ', array_map(fn ($l) => "{$l['name']} {$l['date']}", $sd['leave_requests'])) : ''), 'staff');
        }

        if (preg_match('/data|duplicate|sibling|same parent|missing|record/', $q)) {
            $h = $this->ws->dataHealth();

            return $this->r("Record completeness: {$h['score']}%\n• " . count($h['missing']) . " students with missing fields\n• " . count($h['siblings']) . " parents with more than one child (same phone)\n• " . count($h['duplicates']) . ' possible duplicate names', 'data');
        }

        // Shared school Copilot (attendance, marks, risk…). Admin may see fee lines.
        $shared = (new PrincipalCopilot($this->ws->engine()))->answer($question);
        if ($shared !== null) {
            $tab = match ($shared['tab']) {
                'fees' => 'fees',
                'students', 'risk', 'attendance', 'marks' => 'students',
                'timetable', 'substitutions' => 'staff',
                default => null,
            };

            return $this->r($shared['answer'], $tab);
        }

        return null;
    }

    private function r(string $answer, ?string $tab = null, array $state = []): array
    {
        return ['answer' => $answer, 'tab' => $tab, 'state' => $state];
    }
}
