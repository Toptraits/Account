<?php

namespace App\Services\Admin;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\InsightEngine;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * The admin office "brain".
 *
 * Uses the SAME school data and analyzers as the Principal and Teacher dashboards
 * (students, attendance, fee ledger, staff, timetable), and adds the office desks:
 * admissions, UPI payment matching, certificate requests, data health, smart messages.
 *
 * Everything is recalculated on every request, so each launch and each click shows
 * what is true right now. Admin-only demo data (applicants, bank lines, requests) is
 * generated from the date, like DemoSchoolDataSource — replace those methods with
 * database queries later; keep the array shapes.
 */
class AdminWorkspace
{
    /** Seats open for NEW admissions next academic year, per class. Backend: from the seat-matrix table. */
    public const SEATS = ['6' => 24, '7' => 10, '8' => 8, '9' => 6];

    public const STAGES = ['Enquiry', 'Applied', 'Docs check', 'Test / interview', 'Offer sent', 'Fee paid'];

    public const DOCS = ['Birth certificate', 'Previous report card', 'Transfer certificate', 'Address proof', 'Photo'];

    private const FIRST = ['Aadhya', 'Vivaan', 'Ira', 'Advik', 'Navya', 'Ayaan', 'Sara', 'Rudra', 'Prisha', 'Kiaan', 'Avni', 'Atharv', 'Mira', 'Shaurya', 'Kiara', 'Dhruv'];

    private const LAST = ['Iyer', 'Das', 'Verma', 'Patel', 'Nair', 'Rao', 'Sharma', 'Reddy', 'Gupta', 'Menon', 'Kulkarni', 'Singh', 'Joshi', 'Pillai', 'Bose', 'Khan'];

    private const PARENT_FIRST = ['Ravi', 'Suresh', 'Anil', 'Vikram', 'Priya', 'Lakshmi', 'Kiran', 'Manoj', 'Deepa', 'Sanjay', 'Rekha', 'Imran'];

    private const AREAS = ['Indiranagar', 'Jayanagar', 'Whitefield', 'HSR Layout', 'Malleshwaram', 'BTM Layout'];

    private array $memo = [];

    public function __construct(private InsightEngine $engine, private Cache $cache, private string $userKey = 'guest')
    {
    }

    public function data(): SchoolDataSource
    {
        return $this->engine->data();
    }

    public function engine(): InsightEngine
    {
        return $this->engine;
    }

    public function now(): CarbonImmutable
    {
        return $this->data()->now();
    }

    // ================================================================ state

    private function key(string $name, bool $daily = false): string
    {
        return "admin-ws:{$this->userKey}:{$name}" . ($daily ? ':' . $this->now()->toDateString() : '');
    }

    public function stateGet(string $name, bool $daily = false): array
    {
        return (array) $this->cache->get($this->key($name, $daily), []);
    }

    public function statePut(string $name, string $id, mixed $value, bool $daily = false): void
    {
        $all = $this->stateGet($name, $daily);
        $all[$id] = $value;
        $this->cache->put($this->key($name, $daily), $all, now()->addDays($daily ? 2 : 60));
    }

    // ============================================================== helpers

    private function roll(string $key): float
    {
        return hexdec(substr(md5($key), 0, 8)) / 0xFFFFFFFF;
    }

    private function pick(array $items, string $key): mixed
    {
        return $items[(int) floor($this->roll($key) * count($items)) % count($items)];
    }

    /** @return array<string, array> students keyed by id */
    public function studentsById(): array
    {
        return $this->memo['students'] ??= array_column($this->data()->students(), null, 'id');
    }

    public function attendance(): array
    {
        return $this->engine->attendance->summary($this->data());
    }

    public function fees(): array
    {
        return $this->engine->fees->summary($this->data());
    }

    /** @return array<string, array> risk roster keyed by student id */
    public function roster(): array
    {
        return $this->memo['roster'] ??= array_column($this->engine->students->roster($this->data()), null, 'id');
    }

    // ========================================================= student 360

    /** Everything the office knows about one student, in one place. */
    public function student(string $id): ?array
    {
        $s = $this->studentsById()[$id] ?? null;
        if (! $s) {
            return null;
        }
        $att = $this->attendance()['students'][$id] ?? null;
        $risk = $this->roster()[$id] ?? null;
        $fee = collect($this->feeDesk()['rows'])->firstWhere('id', $id);
        $paid = array_values(array_filter($this->data()->feeLedger(), fn ($r) => $r['student_id'] === $id && $r['paid_on']));
        usort($paid, fn ($a, $b) => strcmp($b['paid_on'], $a['paid_on']));
        $record = $this->records()[$id];
        $marks = $s['subject_marks'];

        return [
            'id' => $id,
            'name' => $s['name'],
            'class' => $s['class'],
            'phone' => $record['phone'],
            'parent' => $record['parent'],
            'area' => $record['area'],
            'dob' => $record['dob'],
            'missing' => $record['missing'],
            'attendance' => $att ? $att['pct'] : null,
            'streak' => $att['streak'] ?? 0,
            'today' => $att['today'] ?? null,
            'marks' => $marks,
            'average' => (int) round(array_sum($marks) / max(1, count($marks))),
            'risk' => $risk,
            'fee' => $fee,
            'last_payment' => $paid[0] ?? null,
            'siblings' => array_values(array_filter($this->dataHealth()['siblings'], fn ($g) => in_array($id, array_column($g['students'], 'id'), true)))[0]['students'] ?? [],
            'requests' => array_values(array_filter($this->requests(), fn ($r) => $r['student_id'] === $id)),
        ];
    }

    /** Office record fields the shared data source does not have (DOB, parent name, locality, documents). */
    public function records(): array
    {
        return $this->memo['records'] ??= (function () {
            $out = [];
            foreach ($this->studentsById() as $id => $s) {
                $grade = (int) explode('-', $s['class'])[0];
                $surname = explode(' ', $s['name'])[1] ?? '';
                $missing = [];
                foreach (['Date of birth' => 0.04, 'Photo' => 0.07, 'Aadhaar' => 0.06, 'Blood group' => 0.1] as $field => $p) {
                    if ($this->roll("rec|{$id}|{$field}") < $p) {
                        $missing[] = $field;
                    }
                }
                $age = $grade + 5 + (int) floor($this->roll("age|{$id}") * 2);
                $out[$id] = [
                    'parent' => $this->pick(self::PARENT_FIRST, "par|{$id}") . ' ' . $surname,
                    'phone' => $s['parent_phone'],
                    'area' => $this->pick(self::AREAS, "area|{$id}"),
                    'dob' => in_array('Date of birth', $missing, true) ? null : $this->now()->subYears($age)->subDays((int) ($this->roll("dob|{$id}") * 360))->format('d M Y'),
                    'missing' => $missing,
                ];
            }

            // Demo only: a few families have two children here, so the office record of the
            // younger child carries the SAME parent name and phone. Real data already has this.
            $ids = array_keys($out);
            $taken = [];
            foreach ($ids as $a) {
                foreach ($ids as $b) {
                    $sa = $this->studentsById()[$a];
                    $sb = $this->studentsById()[$b];
                    if ($a >= $b || isset($taken[$a]) || isset($taken[$b]) || $sa['class'][0] === $sb['class'][0]
                        || (explode(' ', $sa['name'])[1] ?? '') !== (explode(' ', $sb['name'])[1] ?? '')
                        || $this->roll("family|{$a}|{$b}") > 0.1) {
                        continue;
                    }
                    $out[$b]['parent'] = $out[$a]['parent'];
                    $out[$b]['phone'] = $out[$a]['phone'];
                    $out[$b]['area'] = $out[$a]['area'];
                    $taken[$a] = $taken[$b] = true;
                }
            }

            return $out;
        })();
    }

    // ============================================================ admissions

    /** Admissions for NEXT academic year. Opens 1 October; grows every day. */
    public function admissions(): array
    {
        if (isset($this->memo['admissions'])) {
            return $this->memo['admissions'];
        }
        $now = $this->now()->startOfDay();
        $open = CarbonImmutable::create($now->month >= 10 ? $now->year : $now->year - 1, 10, 1, 0, 0, 0, $now->getTimezone());
        $year = ($open->year + 1) . '–' . substr((string) ($open->year + 2), 2);
        $days = max(1, (int) $open->diffInDays($now));
        $count = (int) min(170, 18 + $days * 1.6);
        $verified = $this->stateGet('verified');
        $students = array_values($this->studentsById());
        $apps = [];

        for ($i = 0; $i < $count; $i++) {
            $id = 'APP' . substr((string) ($open->year + 1), 2) . sprintf('%03d', $i + 1);
            $applied = $open->addDays((int) round($i / max(1, $count - 1) * $days));
            $age = (int) $applied->diffInDays($now);
            $class = $this->pick(['6', '6', '6', '6', '7', '7', '8', '9'], "cls|{$id}");
            $stage = min(5, (int) floor($age / (4 + $this->roll("pace|{$id}") * 7)));
            if ($this->roll("stuck|{$id}") < 0.18) {
                $stage = min($stage, 2);
            }
            $withdrawn = $age > 12 && $this->roll("wd|{$id}") < 0.06;

            $first = $this->pick(self::FIRST, "afn|{$id}");
            $last = $this->pick(self::LAST, "aln|{$id}");
            $phone = '+91 9' . sprintf('%09d', (int) ($this->roll("aph|{$id}") * 999999999));
            $issues = [];
            $notes = [];

            // Sibling already studying here → same surname + same phone, discount applies.
            if ($this->roll("sib|{$id}") < 0.12) {
                $sib = $students[(int) floor($this->roll("sibof|{$id}") * count($students))];
                $last = explode(' ', $sib['name'])[1] ?? $last;
                $phone = $sib['parent_phone'];
                $notes[] = "Sibling {$sib['name']} ({$sib['class']}) studies here · sibling discount applies";
            }
            $name = "{$first} {$last}";

            $missing = [];
            if ($stage <= 2) {
                foreach (self::DOCS as $doc) {
                    if ($doc === 'Transfer certificate' && $class === '6' && $this->roll("tcok|{$id}") < 0.5) {
                        continue; // many Class 6 joiners submit TC later
                    }
                    if ($this->roll("doc|{$id}|{$doc}") < ($stage < 2 ? 0.35 : 0.14)) {
                        $missing[] = $doc;
                    }
                }
            }

            $childAge = (int) $class + 5 + ($this->roll("cage|{$id}") < 0.05 ? 3 : (int) floor($this->roll("cage2|{$id}") * 2));
            if ($childAge >= (int) $class + 8) {
                $issues[] = "Age {$childAge} is high for Class {$class} — confirm previous school records";
            }
            if ($this->roll("nm|{$id}") < 0.07) {
                $variant = preg_replace('/[aeiou](?=[^aeiou]*$)/i', '', $first, 1);
                $issues[] = "Birth certificate says “{$variant} {$last}”, form says “{$name}”";
            }
            $prev = (int) round(42 + $this->roll("prev|{$id}") * 55);
            if ($prev < 50) {
                $notes[] = "Previous marks {$prev}% — entrance test recommended";
            }

            $status = $withdrawn ? 'Withdrawn' : self::STAGES[$stage];
            $ready = ! $withdrawn && $stage === 2 && $missing === [] && $issues === [];
            if (isset($verified[$id]) && ! $withdrawn && $stage <= 2) {
                $status = self::STAGES[3];
                $stage = 3;
                $ready = false;
                $notes[] = 'Verified by office on ' . $verified[$id];
            }

            $apps[] = [
                'id' => $id,
                'name' => $name,
                'class' => $class,
                'phone' => $phone,
                'applied' => $applied->toDateString(),
                'applied_label' => $applied->format('d M'),
                'age_days' => $age,
                'stage' => $withdrawn ? -1 : $stage,
                'status' => $status,
                'missing' => $missing,
                'issues' => $issues,
                'notes' => $notes,
                'previous_marks' => $prev,
                'ready' => $ready,
                'confidence' => $ready ? (int) round(88 + $this->roll("conf|{$id}") * 11) : null,
                'stuck' => ! $withdrawn && $stage === 2 && $missing !== [] && $age >= 7,
                'no_contact' => ! $withdrawn && $stage === 0 && $age >= 3,
            ];
        }

        $active = array_filter($apps, fn ($a) => $a['stage'] >= 0);
        $funnel = [];
        foreach (self::STAGES as $k => $label) {
            $funnel[] = ['label' => $label, 'count' => count(array_filter($active, fn ($a) => $a['stage'] >= $k))];
        }

        $seats = [];
        foreach (self::SEATS as $class => $total) {
            $class = (string) $class; // PHP turns '6' array keys into int 6
            $offers = count(array_filter($active, fn ($a) => $a['class'] === $class && $a['stage'] >= 4));
            $paid = count(array_filter($active, fn ($a) => $a['class'] === $class && $a['stage'] === 5));
            $inPipe = count(array_filter($active, fn ($a) => $a['class'] === $class && $a['stage'] < 4));
            $seats[$class] = [
                'class' => $class,
                'seats' => $total,
                'offers' => $offers,
                'paid' => $paid,
                'pipeline' => $inPipe,
                'left' => $total - $offers,
                'state' => $offers > $total ? 'over' : ($offers >= $total * 0.85 ? 'full' : 'open'),
            ];
        }

        $newToday = count(array_filter($apps, fn ($a) => $a['applied'] === $now->toDateString()));
        $last7 = count(array_filter($apps, fn ($a) => $a['age_days'] < 7));

        return $this->memo['admissions'] = [
            'year' => $year,
            'open_since' => $open->format('d M'),
            'applicants' => array_reverse($apps), // newest first
            'total' => count($apps),
            'active' => count($active),
            'new_today' => $newToday,
            'last7' => $last7,
            'funnel' => $funnel,
            'seats' => $seats,
            'ready' => array_values(array_filter($apps, fn ($a) => $a['ready'])),
            'stuck' => array_values(array_filter($apps, fn ($a) => $a['stuck'])),
            'no_contact' => array_values(array_filter($apps, fn ($a) => $a['no_contact'])),
            'issues' => array_values(array_filter($apps, fn ($a) => $a['issues'] !== [] && $a['stage'] >= 0 && $a['stage'] <= 2)),
            'conversion' => count($active) ? round(($funnel[4]['count'] ?? 0) / count($active) * 100) : 0,
        ];
    }

    // ================================================================ fees

    /**
     * Fee follow-up desk: who to remind, who to CALL, and who NOT to chase yet.
     * Uses each family's payment history and the student's attendance/marks.
     */
    public function feeDesk(): array
    {
        if (isset($this->memo['feeDesk'])) {
            return $this->memo['feeDesk'];
        }
        $s = $this->fees();
        $ledger = $this->data()->feeLedger();
        $today = $this->now()->startOfDay();
        $reminded = $this->stateGet('reminded');
        $roster = $this->roster();

        $history = [];
        foreach ($ledger as $r) {
            if ($r['paid_on']) {
                $d = (int) CarbonImmutable::parse($r['due_date'])->diffInDays(CarbonImmutable::parse($r['paid_on']), false);
                $history[$r['student_id']][] = $d;
            }
        }

        $rows = [];
        foreach ($s['defaulters'] as $d) {
            $id = $d['id'];
            $paidCount = count($history[$id] ?? []);
            $avgDelay = $paidCount ? (int) round(array_sum($history[$id]) / $paidCount) : null;
            $r = $roster[$id] ?? ['attendance' => 100, 'marks' => 100];
            $sensitive = $d['days'] > 30 && ($r['attendance'] < 85 || $r['marks'] < 55);
            $lastReminder = $reminded[$id] ?? null;
            $recent = $lastReminder && CarbonImmutable::parse($lastReminder)->diffInDays($today) < 3;

            [$action, $why, $tone] = match (true) {
                $sensitive => ['Counsellor first', "Attendance {$r['attendance']}% · average {$r['marks']}% · fees {$d['days']} days late. Check for family difficulty before any reminder.", 'risk'],
                $paidCount === 0 => ['Call parent', 'No payment at all this year. A phone call works better than SMS here.', 'risk'],
                $avgDelay !== null && $avgDelay > 20 => ['Gentle reminder', "Usually pays about {$avgDelay} days late, but always pays. A polite reminder is enough.", 'warn'],
                default => ['Reminder', 'Normally pays on time — probably forgot. Send one reminder.', 'warn'],
            };
            $likely = (int) max(5, min(95, 72 - $d['days'] * 0.45 + ($paidCount ? 12 : -30) - ($sensitive ? 15 : 0) + ($avgDelay !== null && $avgDelay < 10 ? 10 : 0)));

            $rows[] = ['phone' => $this->records()[$id]['phone'] ?? $d['phone']] + $d + [
                'parent' => $this->records()[$id]['parent'] ?? '',
                'action' => $action,
                'why' => $why,
                'tone' => $tone,
                'likely' => $likely,
                'avg_delay' => $avgDelay,
                'paid_count' => $paidCount,
                'sensitive' => $sensitive,
                'reminded' => $lastReminder,
                'recently_reminded' => $recent,
            ];
        }

        // Class-wise collection (only instalments that are already due).
        $classes = [];
        $students = $this->studentsById();
        foreach ($ledger as $r) {
            if ($r['due_date'] > $today->toDateString()) {
                continue;
            }
            $c = $students[$r['student_id']]['class'];
            $classes[$c] ??= ['class' => $c, 'expected' => 0, 'collected' => 0];
            $classes[$c]['expected'] += $r['amount'];
            $classes[$c]['collected'] += $r['paid_on'] ? $r['amount'] : 0;
        }
        foreach ($classes as &$c) {
            $c['rate'] = $c['expected'] ? round($c['collected'] / $c['expected'] * 100, 1) : 100;
        }
        unset($c);
        ksort($classes);

        // How many families normally pay within a week of the due date → forecast for the next 7 days.
        $onTime = $total = 0;
        foreach ($history as $delays) {
            foreach ($delays as $dl) {
                $total++;
                $onTime += $dl <= 7 ? 1 : 0;
            }
        }
        $onTimeRate = $total ? $onTime / $total : 0.6;

        return $this->memo['feeDesk'] = [
            'summary' => $s,
            'rows' => $rows,
            'to_remind' => array_values(array_filter($rows, fn ($r) => ! $r['sensitive'] && ! $r['recently_reminded'] && $r['action'] !== 'Call parent')),
            'to_call' => array_values(array_filter($rows, fn ($r) => $r['action'] === 'Call parent')),
            'sensitive' => array_values(array_filter($rows, fn ($r) => $r['sensitive'])),
            'classes' => array_values($classes),
            'forecast_7' => (int) round($s['due_next_7'] * $onTimeRate),
            'on_time_rate' => (int) round($onTimeRate * 100),
        ];
    }

    /**
     * Today's UPI / bank lines and the invoice each one most likely pays.
     * Score = amount match + surname match + what the parent typed in the remark.
     */
    public function upi(): array
    {
        if (isset($this->memo['upi'])) {
            return $this->memo['upi'];
        }
        $date = $this->now()->toDateString();
        $students = $this->studentsById();
        $decided = $this->stateGet('upi', true);

        $open = array_values(array_filter($this->data()->feeLedger(), fn ($r) => $r['paid_on'] === null && $r['due_date'] <= $this->now()->addDays(10)->toDateString()));
        usort($open, fn ($a, $b) => $this->roll("upi|{$date}|{$a['student_id']}|{$a['head']}") <=> $this->roll("upi|{$date}|{$b['student_id']}|{$b['head']}"));
        $picked = [];
        foreach ($open as $r) {
            if (! isset($picked[$r['student_id']])) {
                $picked[$r['student_id']] = $r;
            }
            if (count($picked) >= 4 + (int) floor($this->roll("upicount|{$date}") * 4)) {
                break;
            }
        }

        $lines = [];
        $n = 0;
        foreach ($picked as $sid => $r) {
            $n++;
            $s = $students[$sid];
            [$first, $surname] = array_pad(explode(' ', $s['name'], 2), 2, '');
            $k = "{$date}|{$sid}";
            $v = $this->roll("payer|{$k}");
            $payer = match (true) {
                $v < 0.5 => $this->records()[$sid]['parent'],
                $v < 0.8 => strtoupper($surname) . ' ' . substr($this->pick(self::PARENT_FIRST, "pi|{$k}"), 0, 1),
                default => $this->pick(self::PARENT_FIRST, "pu|{$k}") . ' ' . $this->pick(self::LAST, "pl|{$k}"),
            };
            $rm = $this->roll("rem|{$k}");
            $remark = match (true) {
                $rm < 0.35 => "{$sid} fees",
                $rm < 0.65 => "{$s['class']} {$first} school fee",
                $rm < 0.8 => 'school fee',
                default => '',
            };
            $amount = $this->roll("amt|{$k}") < 0.85 ? $r['amount'] : $r['amount'] - 200;
            $lines[] = ['ref' => 'UPI' . substr(md5($k), 0, 10), 'time' => sprintf('%02d:%02d', 7 + $n, (int) ($this->roll("min|{$k}") * 59)), 'payer' => $payer, 'remark' => $remark, 'amount' => $amount];
        }
        // one line that matches nobody
        $lines[] = ['ref' => 'UPI' . substr(md5("x|{$date}"), 0, 10), 'time' => '18:42', 'payer' => 'GPAY USER', 'remark' => 'payment', 'amount' => 2350];

        $result = [];
        foreach ($lines as $line) {
            $best = null;
            foreach ($open as $r) {
                $s = $students[$r['student_id']];
                [$first, $surname] = array_pad(explode(' ', $s['name'], 2), 2, '');
                $score = 0;
                $why = [];
                if ($line['amount'] === $r['amount']) {
                    $score += 45;
                    $why[] = 'amount matches exactly';
                } elseif (abs($line['amount'] - $r['amount']) <= $r['amount'] * 0.1) {
                    $score += 20;
                    $why[] = 'amount is close (part payment?)';
                }
                if ($surname && stripos($line['payer'], $surname) !== false) {
                    $score += 25;
                    $why[] = "payer surname is {$surname}";
                }
                if (stripos($line['remark'], $r['student_id']) !== false) {
                    $score += 30;
                    $why[] = 'remark has the admission number';
                } elseif ($first && stripos($line['remark'], $first) !== false) {
                    $score += 18;
                    $why[] = "remark mentions {$first}";
                    if (stripos($line['remark'], $s['class']) !== false) {
                        $score += 8;
                    }
                }
                if (! $best || $score > $best['score']) {
                    $best = ['score' => $score, 'row' => $r, 'student' => $s, 'why' => $why];
                }
            }
            $conf = (int) min(99, $best['score'] ?? 0);
            $state = $decided[$line['ref']] ?? null;
            $result[] = $line + [
                'student_id' => $conf >= 40 ? $best['row']['student_id'] : null,
                'student' => $conf >= 40 ? "{$best['student']['name']} · {$best['student']['class']}" : null,
                'head' => $conf >= 40 ? $best['row']['head'] : null,
                'due' => $conf >= 40 ? $best['row']['amount'] : null,
                'confidence' => $conf,
                'why' => $conf >= 40 ? ucfirst(implode(', ', $best['why'])) : 'No invoice has this amount and the payer name matches no family.',
                'status' => $state ?? ($conf >= 85 ? 'Auto-matched' : ($conf >= 55 ? 'Check' : 'Unmatched')),
                'decided' => $state !== null,
            ];
        }

        return $this->memo['upi'] = $result;
    }

    // ============================================================ requests

    /** Certificate & service requests with automatic eligibility checks and a ready-made draft. */
    public function requests(): array
    {
        if (isset($this->memo['requests'])) {
            return $this->memo['requests'];
        }
        $issued = $this->stateGet('issued');
        $students = array_values($this->studentsById());
        $defaulters = array_column($this->fees()['defaulters'], null, 'id');
        $types = ['Bonafide certificate', 'Bonafide certificate', 'Fee receipt copy', 'Transfer certificate', 'Character certificate', 'ID card reissue', 'Study certificate (passport)'];
        $out = [];

        for ($back = 9; $back >= 0; $back--) {
            $day = $this->now()->subDays($back);
            if ($day->isSunday()) {
                continue;
            }
            $count = (int) floor($this->roll('rq|' . $day->toDateString()) * 3.4);
            for ($i = 0; $i < $count; $i++) {
                $k = $day->toDateString() . "|{$i}";
                $id = 'REQ' . $day->format('md') . $i;
                $s = $students[(int) floor($this->roll("rqs|{$k}") * count($students))];
                $type = $this->pick($types, "rqt|{$k}");
                $closedEarlier = $back > 2 && $this->roll("rqc|{$k}") < 0.65;
                $blocked = null;
                $check = 'All checks passed';
                if ($type === 'Transfer certificate' && isset($defaulters[$s['id']])) {
                    $blocked = Format::inr($defaulters[$s['id']]['amount']) . ' fees overdue — clear dues before TC';
                } elseif ($type === 'ID card reissue' && $this->roll("rqp|{$k}") < 0.5) {
                    $blocked = 'Photo on file is older than 2 years — ask parent for a new photo';
                } elseif ($type === 'Transfer certificate') {
                    $check = 'No fee dues · no library dues · ready';
                } elseif ($type === 'Fee receipt copy') {
                    $check = 'Payments found in ledger · receipt can be reprinted';
                }
                $done = isset($issued[$id]) || $closedEarlier;
                $out[] = [
                    'id' => $id,
                    'type' => $type,
                    'student_id' => $s['id'],
                    'student' => $s['name'],
                    'class' => $s['class'],
                    'requested' => $day->format('d M'),
                    'age' => $back,
                    'status' => $done ? 'Issued' : ($blocked ? 'Blocked' : 'Ready to issue'),
                    'blocked' => $done ? null : $blocked,
                    'check' => $done ? 'Closed' : ($blocked ?? $check),
                    'overdue' => ! $done && $back >= 3,
                    'issued_on' => $issued[$id] ?? null,
                ];
            }
        }

        usort($out, fn ($a, $b) => [$a['status'] === 'Issued', -$a['age']] <=> [$b['status'] === 'Issued', -$b['age']]);

        return $this->memo['requests'] = $out;
    }

    public function certificateDraft(string $requestId): ?string
    {
        $r = collect($this->requests())->firstWhere('id', $requestId);
        if (! $r) {
            return null;
        }
        $rec = $this->records()[$r['student_id']];
        $year = $this->now()->month >= 4 ? $this->now()->year . '–' . substr((string) ($this->now()->year + 1), 2) : ($this->now()->year - 1) . '–' . substr((string) $this->now()->year, 2);

        return match (true) {
            str_starts_with($r['type'], 'Transfer') => "TRANSFER CERTIFICATE\n\nCertified that {$r['student']}, child of {$rec['parent']}, admission no. {$r['student_id']}, date of birth " . ($rec['dob'] ?? '__________') . ", studied in Class {$r['class']} during {$year}. All dues to the school have been paid. Conduct: Good.\n\nDate: {$this->now()->format('d M Y')}                Principal",
            str_starts_with($r['type'], 'Fee receipt') => "DUPLICATE FEE RECEIPT\n\n{$r['student']} · {$r['student_id']} · Class {$r['class']}\nLast payment: " . (($p = $this->student($r['student_id'])['last_payment'] ?? null) ? "{$p['head']} · ₹" . number_format($p['amount']) . ' · paid on ' . CarbonImmutable::parse($p['paid_on'])->format('d M Y') : 'none') . "\n\nIssued on {$this->now()->format('d M Y')} (duplicate copy)",
            default => strtoupper(preg_replace('/\s*\(.*\)/', '', $r['type'])) . "\n\nThis is to certify that {$r['student']}, child of {$rec['parent']}, admission no. {$r['student_id']}, is a bonafide student of this school studying in Class {$r['class']} in the academic year {$year}." . ($rec['dob'] ? " Date of birth as per school records: {$rec['dob']}." : '') . "\n\nDate: {$this->now()->format('d M Y')}                Principal",
        };
    }

    // ================================================================ staff

    public function staffDesk(): array
    {
        $staff = $this->data()->staff();
        $gaps = $this->engine->substitution->gaps($this->data());
        $leaves = [];
        foreach ($staff as $m) {
            for ($d = 1; $d <= 14; $d++) {
                $day = $this->now()->addDays($d);
                if (! $day->isSunday() && $m['id'] !== 'STF007' && $this->roll("lv|{$m['id']}|{$day->toDateString()}") < 0.035) {
                    $leaves[] = ['name' => $m['name'], 'department' => $m['department'], 'date' => $day->format('D d M'), 'type' => $this->pick(['Casual leave', 'Medical leave', 'Earned leave'], "lvt|{$m['id']}|{$d}"), 'days_away' => $d];
                }
            }
        }
        usort($leaves, fn ($a, $b) => $a['days_away'] <=> $b['days_away']);
        $sortedGaps = $gaps['gaps'];
        usort($sortedGaps, fn ($a, $b) => [$a['start'], $a['class']] <=> [$b['start'], $b['class']]);
        $compliance = [];
        foreach ($staff as $m) {
            $due = (int) floor($this->roll("cmp|{$m['id']}|" . $this->now()->format('Y-m')) * 90) - 20;
            if ($due <= 30) {
                $compliance[] = ['name' => $m['name'], 'item' => $this->pick(['Police verification renewal', 'First-aid training', 'Contract renewal', 'Medical fitness certificate'], "cmpi|{$m['id']}"), 'due' => $due, 'label' => $due < 0 ? 'Overdue by ' . abs($due) . ' days' : "Due in {$due} days"];
            }
        }
        usort($compliance, fn ($a, $b) => $a['due'] <=> $b['due']);

        return [
            'staff' => $staff,
            'present' => count(array_filter($staff, fn ($s) => $s['status'] === 'Present')),
            'on_leave' => $gaps['on_leave'],
            'gaps' => $sortedGaps,
            'unassigned' => array_values(array_filter($sortedGaps, fn ($g) => ! $g['suggested'])),
            'leave_requests' => array_slice($leaves, 0, 6),
            'compliance' => array_slice($compliance, 0, 6),
        ];
    }

    // ========================================================== data health

    public function dataHealth(): array
    {
        if (isset($this->memo['health'])) {
            return $this->memo['health'];
        }
        $students = $this->studentsById();
        $records = $this->records();
        $fields = 0;
        $filled = 0;
        $missing = [];
        foreach ($students as $id => $s) {
            $fields += 8;
            $filled += 8 - count($records[$id]['missing']);
            if ($records[$id]['missing']) {
                $missing[] = ['id' => $id, 'name' => $s['name'], 'class' => $s['class'], 'missing' => $records[$id]['missing']];
            }
        }

        // Same name in the school → possible duplicate record.
        $byName = [];
        foreach ($students as $s) {
            $byName[mb_strtolower($s['name'])][] = $s;
        }
        $dupes = array_values(array_filter($byName, fn ($g) => count($g) > 1));

        // Same parent → one family. Very sure: same parent phone. Check: same parent name + same locality.
        $digits = fn ($p) => substr(preg_replace('/\D/', '', (string) $p), -10);
        $byPhone = [];
        foreach ($students as $id => $s) {
            $byPhone[$digits($records[$id]['phone'])][] = $id;
        }
        $families = [];
        $grouped = [];
        foreach ($byPhone as $phone => $ids) {
            if (count($ids) > 1) {
                $families[] = ['key' => "phone:{$phone}", 'ids' => $ids, 'sure' => 'strong', 'reason' => 'Same parent phone ' . $records[$ids[0]]['phone']];
                $grouped += array_flip($ids);
            }
        }
        $byName = [];
        foreach ($students as $id => $s) {
            if (! isset($grouped[$id])) {
                $byName[mb_strtolower($records[$id]['parent'] . '|' . $records[$id]['area'])][] = $id;
            }
        }
        foreach ($byName as $key => $ids) {
            if (count($ids) > 1) {
                $families[] = ['key' => 'name:' . $key, 'ids' => $ids, 'sure' => 'check', 'reason' => 'Same parent name and locality, different phone numbers — confirm with the parent'];
            }
        }
        $linked = $this->stateGet('siblings');
        $siblings = [];
        foreach ($families as $f) {
            if (isset($linked[$f['key']])) {
                continue;
            }
            $first = $records[$f['ids'][0]];
            $siblings[] = [
                'key' => $f['key'],
                'parent' => $first['parent'],
                'phone' => $first['phone'],
                'area' => $first['area'],
                'sure' => $f['sure'],
                'reason' => $f['reason'],
                'students' => array_map(fn ($id) => ['id' => $id, 'name' => $students[$id]['name'], 'class' => $students[$id]['class']], $f['ids']),
            ];
        }
        usort($siblings, fn ($x, $y) => [$x['sure'] !== 'strong', $x['parent']] <=> [$y['sure'] !== 'strong', $y['parent']]);

        return $this->memo['health'] = [
            'score' => $fields ? round($filled / $fields * 100, 1) : 100,
            'missing' => $missing,
            'duplicates' => array_map(fn ($g) => array_map(fn ($s) => ['id' => $s['id'], 'name' => $s['name'], 'class' => $s['class']], $g), $dupes),
            'siblings' => $siblings,
        ];
    }

    // ======================================================== smart audiences

    /** Who a message should go to — computed from today's data, with families that should NOT get it removed. */
    public function audiences(): array
    {
        $fees = $this->feeDesk();
        $att = $this->attendance();
        $adm = $this->admissions();
        $students = $this->studentsById();
        $absentToday = array_values(array_filter($att['students'], fn ($s) => $s['today'] === 'A'));
        $low = $att['defaulters'];
        $docs = array_values(array_filter($adm['applicants'], fn ($a) => $a['stage'] >= 0 && $a['stage'] <= 2 && $a['missing']));

        $toRecipient = fn (array $s, array $extra = []) => [
            'id' => $s['id'], 'student' => $s['name'], 'class' => $s['class'],
            'parent' => $this->records()[$s['id']]['parent'] ?? 'Parent', 'phone' => $this->records()[$s['id']]['phone'] ?? ($s['phone'] ?? ''),
        ] + $extra;

        return [
            'fees' => [
                'label' => 'Parents · fees overdue',
                'recipients' => array_map(fn ($r) => $toRecipient($r, ['amount' => Format::inr($r['amount']), 'days' => $r['days']]), array_merge($fees['to_remind'])),
                'excluded' => array_map(fn ($r) => "{$r['name']} ({$r['class']}) — " . ($r['sensitive'] ? 'counsellor first' : ($r['recently_reminded'] ? 'reminded on ' . $r['reminded'] : 'call instead')), array_merge($fees['sensitive'], $fees['to_call'], array_filter($fees['rows'], fn ($r) => $r['recently_reminded'] && ! $r['sensitive']))),
                'best_time' => 'Best sent 7–8 pm, when parents are home',
                'template' => "Dear {parent}, this is a gentle reminder that {amount} school fee for {student} ({class}) is pending for {days} days. Please pay via the parent app or at the office. Ignore if already paid. – School Office",
            ],
            'absent' => [
                'label' => 'Parents · absent today',
                'recipients' => array_map(fn ($r) => $toRecipient($r), $absentToday),
                'excluded' => [],
                'best_time' => 'Best sent before 11 am',
                'template' => 'Dear {parent}, {student} ({class}) is marked absent today, {date}. If this is planned leave, please inform the class teacher. – School Office',
            ],
            'low_attendance' => [
                'label' => 'Parents · attendance below 75%',
                'recipients' => array_map(fn ($r) => $toRecipient($r, ['pct' => $r['pct']]), $low),
                'excluded' => [],
                'best_time' => 'Best sent on a weekday evening',
                'template' => "Dear {parent}, {student}'s attendance is {pct}% this month, below the 75% required. Please meet the class teacher this week. – School Office",
            ],
            'applicants' => [
                'label' => 'Applicants · documents pending',
                'recipients' => array_map(fn ($a) => ['id' => $a['id'], 'student' => $a['name'], 'class' => $a['class'], 'parent' => 'Parent', 'phone' => $a['phone'], 'missing' => implode(', ', $a['missing'])], $docs),
                'excluded' => [],
                'best_time' => 'Any time today',
                'template' => "Dear Parent, {student}'s admission to Class {class} is waiting for: {missing}. Please upload them in the admissions portal or bring them to the office. – Admissions Office",
            ],
            'staff' => [
                'label' => 'All staff',
                'recipients' => array_map(fn ($m) => ['id' => $m['id'], 'student' => $m['name'], 'class' => $m['department'], 'parent' => $m['name'], 'phone' => ''], $this->data()->staff()),
                'excluded' => [],
                'best_time' => 'Best sent before 8:30 am',
                'template' => 'Dear {parent}, ',
            ],
        ];
    }

    public static function fill(string $template, array $r, string $date): string
    {
        $map = ['{parent}' => $r['parent'] ?? 'Parent', '{student}' => $r['student'] ?? '', '{class}' => $r['class'] ?? '', '{amount}' => $r['amount'] ?? '', '{days}' => (string) ($r['days'] ?? ''), '{pct}' => (string) ($r['pct'] ?? ''), '{missing}' => $r['missing'] ?? '', '{date}' => $date];

        return strtr($template, $map);
    }

    // ========================================================= visit & pulse

    /** The numbers we compare between visits. */
    public function pulse(): array
    {
        $f = $this->fees();
        $a = $this->admissions();

        return [
            'collected' => $f['collected'],
            'overdue' => $f['overdue'],
            'defaulters' => count($f['defaulters']),
            'applicants' => $a['total'],
            'requests' => count(array_filter($this->requests(), fn ($r) => $r['status'] !== 'Issued')),
        ];
    }

    /** Call on page open. A new visit starts after 30 minutes away. */
    public function beginVisit(): void
    {
        $last = $this->cache->get($this->key('visit'));
        $now = time();
        if (! $last || $now - ($last['at'] ?? 0) > 1800) {
            $this->cache->put($this->key('baseline'), $last ?: ['at' => $now, 'pulse' => $this->pulse()], now()->addDays(30));
        }
        $this->cache->put($this->key('visit'), ['at' => $now, 'pulse' => $this->pulse()], now()->addDays(30));
    }

    public function sinceLastVisit(): ?string
    {
        $base = $this->cache->get($this->key('baseline'));
        if (! $base || ($base['at'] ?? 0) >= time() - 60) {
            return null;
        }
        $p = $this->pulse();
        $b = $base['pulse'];
        $parts = [];
        if (($d = $p['applicants'] - $b['applicants']) > 0) {
            $parts[] = '+' . Format::plural($d, 'new application');
        }
        if (($d = $p['collected'] - $b['collected']) > 0) {
            $parts[] = Format::inr($d) . ' collected';
        }
        if (($d = $p['defaulters'] - $b['defaulters']) !== 0) {
            $parts[] = ($d > 0 ? '+' : '−') . Format::plural(abs($d), 'family', 'families') . ' overdue';
        }
        if (($d = $p['requests'] - $b['requests']) > 0) {
            $parts[] = '+' . Format::plural($d, 'request');
        }
        $when = CarbonImmutable::createFromTimestamp($base['at'])->setTimezone($this->now()->getTimezone());
        $label = $when->isToday() ? 'today ' . $when->format('g:i a') : ($when->isYesterday() ? 'yesterday ' . $when->format('g:i a') : $when->format('D d M'));

        return 'Since your last visit (' . $label . '): ' . ($parts ? implode(' · ', $parts) : 'nothing important changed');
    }

    // ============================================================ the brain

    public function greeting(): string
    {
        $h = (int) $this->now()->format('G');

        return $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
    }

    /** One line that tells the office what kind of day it is. */
    public function focus(): string
    {
        $now = $this->now();
        $h = (int) $now->format('G');
        $f = $this->fees();
        $bits = [];
        if ($now->day <= 10) {
            $bits[] = 'Fee week — instalments fall due on the 5th and 10th';
        }
        if (in_array($now->month, [10, 11, 12, 1, 2, 3], true)) {
            $a = $this->admissions();
            $bits[] = "Admissions {$a['year']} open · {$a['last7']} applications this week";
        }
        if ($h < 11) {
            $bits[] = 'Morning: registers, absent-today messages and staff cover first';
        } elseif ($h >= 16) {
            $bits[] = 'Evening: best time for fee reminders (parents are home)';
        }
        if ($now->isSaturday()) {
            $bits[] = 'Saturday: weekly data clean-up';
        }

        return implode(' · ', $bits) ?: 'Quiet day — good time to fix data gaps.';
    }

    /**
     * "Needs your decision": built from every desk, ranked by urgency, time of day and day of month.
     * Items you complete disappear for today — and come back only if the situation changes.
     */
    public function decisions(): array
    {
        $now = $this->now();
        $h = (int) $now->format('G');
        $done = $this->stateGet('done', true);
        $adm = $this->admissions();
        $fd = $this->feeDesk();
        $upi = $this->upi();
        $req = $this->requests();
        $att = $this->attendance();
        $staff = $this->staffDesk();
        $health = $this->dataHealth();
        $items = [];

        $auto = array_filter($upi, fn ($l) => $l['status'] === 'Auto-matched' && ! $l['decided']);
        $check = array_filter($upi, fn ($l) => $l['status'] === 'Check' && ! $l['decided']);
        if ($auto || $check) {
            $items[] = ['key' => 'upi', 'score' => 70 + ($h >= 15 ? 8 : 0), 'tone' => 'ok', 'icon' => '₹',
                'title' => Format::plural(count($auto), 'UPI payment') . ' matched to invoices' . ($check ? ' · ' . count($check) . ' to check' : ''),
                'detail' => Format::inr(array_sum(array_column($auto, 'amount'))) . ' received today. Matched on amount, payer surname and remark.',
                'confidence' => $auto ? (int) round(array_sum(array_column($auto, 'confidence')) / count($auto)) . '% sure' : 'needs a look',
                'button' => $auto ? 'Confirm ' . count($auto) : 'Review', 'action' => 'confirm-upi', 'primary' => count($auto) > 0];
        }

        if ($adm['ready']) {
            $items[] = ['key' => 'verify', 'score' => 62 + (in_array($now->month, [11, 12, 1], true) ? 10 : 0), 'tone' => 'info', 'icon' => '♙',
                'title' => Format::plural(count($adm['ready']), 'application') . ' ready to verify',
                'detail' => 'All documents received and they match the forms. Moves them to test / interview.',
                'confidence' => (int) round(array_sum(array_column($adm['ready'], 'confidence')) / count($adm['ready'])) . '% sure',
                'button' => 'Verify all', 'action' => 'verify-all', 'primary' => false];
        }

        $over = array_filter($adm['seats'], fn ($s) => $s['state'] === 'over');
        foreach ($over as $s) {
            $items[] = ['key' => "seats.{$s['class']}", 'score' => 80, 'tone' => 'risk', 'icon' => '!',
                'title' => "Class {$s['class']}: {$s['offers']} offers for {$s['seats']} seats",
                'detail' => 'Over-booked by ' . ($s['offers'] - $s['seats']) . '. Pause new offers or move late applicants to the wait-list.',
                'confidence' => 'rule', 'button' => 'Open seats', 'action' => 'tab:admissions', 'primary' => false];
        }

        if ($fd['to_remind'] || $fd['to_call']) {
            $sum = array_sum(array_column($fd['to_remind'], 'amount'));
            $items[] = ['key' => 'fees', 'score' => 55 + ($now->day <= 12 ? 12 : 0) + ($h >= 16 ? 15 : 0), 'tone' => 'warn', 'icon' => '₹',
                'title' => Format::plural(count($fd['to_remind']), 'family', 'families') . ' to remind · ' . Format::plural(count($fd['to_call']), 'to call'),
                'detail' => Format::inr($sum) . ' pending. ' . ($fd['sensitive'] ? count($fd['sensitive']) . ' held back for the counsellor (falling attendance or marks). ' : '') . ($h >= 16 ? 'Good time to send now.' : 'Best sent after 7 pm.'),
                'confidence' => 'from payment history', 'button' => 'Prepare reminders', 'action' => 'msg:fees', 'primary' => $h >= 16];
        }

        $absent = $att['absent_today'];
        if ($att['register_taken'] && $absent > 0 && $h < 13) {
            $items[] = ['key' => 'absent', 'score' => 66 + ($h < 11 ? 14 : 0), 'tone' => 'warn', 'icon' => '✉',
                'title' => Format::plural($absent, 'student') . ' absent today — inform parents',
                'detail' => 'One SMS per family. Parents of streak absentees should get a call from the class teacher instead.',
                'confidence' => 'register', 'button' => 'Prepare message', 'action' => 'msg:absent', 'primary' => $h < 11];
        } elseif (! $att['register_taken'] && ! $now->isSunday() && $h >= 9 && $h < 12) {
            $items[] = ['key' => 'registers', 'score' => 75, 'tone' => 'warn', 'icon' => '✓',
                'title' => 'Attendance registers are not in yet',
                'detail' => 'It is after 9 am. Remind class teachers so absence messages can go out before 11.',
                'confidence' => 'clock', 'button' => 'Remind teachers', 'action' => 'msg:staff', 'primary' => false];
        }

        $readyReq = array_filter($req, fn ($r) => $r['status'] === 'Ready to issue');
        if ($readyReq) {
            $late = count(array_filter($readyReq, fn ($r) => $r['overdue']));
            $items[] = ['key' => 'requests', 'score' => 50 + $late * 8, 'tone' => $late ? 'warn' : 'info', 'icon' => '▤',
                'title' => Format::plural(count($readyReq), 'certificate') . ' ready to issue' . ($late ? " · {$late} waiting 3+ days" : ''),
                'detail' => 'Eligibility checked (fees, records). Drafts are filled in — review and print.',
                'confidence' => 'checked', 'button' => 'Open requests', 'action' => 'tab:requests', 'primary' => false];
        }

        if ($staff['gaps']) {
            $items[] = ['key' => 'cover', 'score' => 58 + ($h < 11 ? 12 : 0) + count($staff['unassigned']) * 5, 'tone' => $staff['unassigned'] ? 'risk' : 'info', 'icon' => '◷',
                'title' => Format::plural(count($staff['on_leave']), 'teacher') . ' on leave · ' . Format::plural(count($staff['gaps']), 'period') . ' need cover',
                'detail' => $staff['unassigned'] ? Format::plural(count($staff['unassigned']), 'period') . ' with no free teacher — tell the principal.' : 'A free teacher is suggested for every period. Principal assigns.',
                'confidence' => 'timetable', 'button' => 'See staff', 'action' => 'tab:staff', 'primary' => false];
        }

        if ($adm['stuck'] || $adm['no_contact']) {
            $items[] = ['key' => 'adm-followup', 'score' => 45, 'tone' => 'info', 'icon' => '♙',
                'title' => Format::plural(count($adm['stuck']), 'applicant') . ' stuck on documents · ' . count($adm['no_contact']) . ' enquiries not called',
                'detail' => 'Stuck for 7+ days. A nudge now keeps them from joining another school.',
                'confidence' => 'pipeline', 'button' => 'Nudge parents', 'action' => 'msg:applicants', 'primary' => false];
        }

        if ($health['siblings'] || $health['duplicates']) {
            $items[] = ['key' => 'data', 'score' => 30 + ($now->isSaturday() ? 25 : 0), 'tone' => 'info', 'icon' => '⚙',
                'title' => Format::plural(count($health['siblings']), 'parent') . ' with more than one child here' . ($health['duplicates'] ? ' · ' . Format::plural(count($health['duplicates']), 'possible duplicate') : ''),
                'detail' => 'Found by the same parent phone. Linking them applies the sibling discount and sends one message per parent.',
                'confidence' => 'data scan', 'button' => 'Review data', 'action' => 'tab:data', 'primary' => false];
        }

        // Hide what was done today — unless the situation changed (fingerprint = key + title).
        $items = array_values(array_filter($items, fn ($i) => ($done[$i['key']] ?? null) !== md5($i['title'])));
        usort($items, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $items;
    }

    public function markDone(string $key, string $title): void
    {
        $this->statePut('done', $key, md5($title), true);
    }

    /** Headline numbers with a short "why it matters" each. */
    public function kpis(): array
    {
        $att = $this->attendance();
        $f = $this->fees();
        $adm = $this->admissions();
        $risk = array_filter($this->roster(), fn ($r) => $r['signal_type'] === 'risk');
        $req = array_filter($this->requests(), fn ($r) => $r['status'] !== 'Issued');
        $h = (int) $this->now()->format('G');

        return [
            ['label' => 'Attendance today', 'tab' => 'students',
                'value' => $att['register_taken'] ? $att['latest_rate'] . '%' : '—',
                'note' => $att['register_taken'] ? "{$att['absent_today']} absent · {$att['late_today']} late" : ($h < 9 ? 'Registers open at 9:00' : 'Registers not in yet'),
                'tone' => ! $att['register_taken'] ? 'warn' : ($att['latest_rate'] < 90 ? 'warn' : 'ok')],
            ['label' => 'Fees collected', 'tab' => 'fees',
                'value' => Format::inr($f['collected']), 'sub' => 'of ' . Format::inr($f['expected']), 'progress' => $f['rate'],
                'note' => "{$f['rate']}% · " . Format::inr($f['overdue']) . ' overdue', 'tone' => $f['rate'] < 85 ? 'warn' : 'ok'],
            ['label' => 'Students to watch', 'tab' => 'students',
                'value' => (string) count($risk), 'sub' => 'students',
                'note' => 'Attendance, marks or fees together', 'tone' => count($risk) > 6 ? 'warn' : 'ok'],
            ['label' => 'Admissions ' . $adm['year'], 'tab' => 'admissions',
                'value' => (string) $adm['active'], 'sub' => '· ' . count($adm['ready']) . ' to verify',
                'note' => "+{$adm['new_today']} today · {$adm['last7']} this week · " . count($req) . ' open requests', 'tone' => 'ok'],
        ];
    }
}
