<?php

namespace App\Services\Principal\Insights;

use Carbon\CarbonImmutable;

/**
 * Data for the School Analytics, Approvals and Alerts & Circulars tabs.
 * Everything is derived from the same analyzers as Home, so the numbers always match.
 */
class WorkspaceData
{
    public function __construct(private InsightEngine $engine)
    {
    }

    // ============================================================ analytics

    public function analytics(InsightReport $report): array
    {
        $data = $this->engine->data();
        $att = $this->engine->attendance->summary($data);
        $fees = $this->engine->fees->summary($data);
        $roster = $this->engine->students->roster($data);
        $marks = $this->engine->marks->summary($data);
        $daily = array_values($att['daily']);
        $avg30 = $daily ? round(array_sum($daily) / count($daily), 1) : 0;
        $riskN = count(array_filter($roster, fn ($r) => $r['signal_type'] === 'risk'));
        $syllabusKpi = collect($report->kpis)->firstWhere('key', 'syllabus');
        $passRate = $marks['subject_rows'] ? round(array_sum(array_column($marks['subject_rows'], 'pass_rate')) / count($marks['subject_rows'])) : 0;

        // Section health: attendance 35% + marks 45% + fees 20%, minus at-risk students.
        $scorecard = [];
        foreach ($att['sections'] as $s) {
            $sec = $s['class'];
            $students = array_filter($att['students'], fn ($x) => $x['class'] === $sec);
            $attPct = $students ? round(array_sum(array_column($students, 'pct')) / count($students), 1) : 0;
            $f = array_filter($fees['defaulters'], fn ($d) => $d['class'] === $sec);
            $risk = count(array_filter($roster, fn ($r) => $r['class'] === $sec && $r['signal_type'] === 'risk'));
            $mk = $marks['heatmap'][$sec]['Overall'] ?? 0;
            $scorecard[] = [
                'section' => $sec,
                'attendance' => $attPct,
                'marks' => $mk,
                'fee_count' => count($f),
                'fee_amount' => array_sum(array_column($f, 'amount')),
                'risk' => $risk,
                'health' => (int) round($attPct * 0.35 + $mk * 0.45 + max(0, 100 - count($f) * 8) * 0.2 - $risk * 2),
            ];
        }
        usort($scorecard, fn ($a, $b) => $a['health'] <=> $b['health']);

        // "What changed" — built from the live analysis, not typed text.
        $changes = [];
        $drops = $att['sections'];
        usort($drops, fn ($a, $b) => $b['drop'] <=> $a['drop']);
        if ($drops && $drops[0]['drop'] >= 3) {
            $changes[] = ['text' => "{$drops[0]['class']} attendance is {$drops[0]['rate']}% today (usually {$drops[0]['usual']}%)", 'badge' => '▼ ' . $drops[0]['drop'] . ' pts', 'tone' => 'risk', 'tab' => 'attendance'];
        } else {
            $changes[] = ['text' => "Attendance steady at {$att['latest_rate']}% today", 'badge' => 'Normal', 'tone' => 'ok', 'tab' => 'attendance'];
        }
        $over60 = count(array_filter($fees['defaulters'], fn ($d) => $d['days'] > 60));
        $changes[] = ['text' => count($fees['defaulters']) . " families overdue · {$over60} for 60+ days", 'badge' => "{$fees['rate']}% collected", 'tone' => $fees['rate'] >= 90 ? 'ok' : 'warn', 'tab' => 'fees'];
        $weak = null;
        foreach ($marks['heatmap'] as $sec => $row) {
            foreach ($row as $sub => $v) {
                if ($sub !== 'Overall' && ($weak === null || $v < $weak[2])) {
                    $weak = [$sec, $sub, $v];
                }
            }
        }
        if ($weak) {
            $changes[] = ['text' => "{$weak[0]} {$weak[1]} is the weakest subject-section", 'badge' => "{$weak[2]}%", 'tone' => $weak[2] < 50 ? 'risk' : 'warn', 'tab' => 'marks'];
        }
        $next = $this->engine->exams->nextExam($data);
        if ($next) {
            $behind = count(array_filter($data->syllabus(), fn ($s) => $s['status'] === 'Needs review'));
            $changes[] = ['text' => "{$next['exam']} in {$next['days']} days · {$behind} class-subjects behind syllabus", 'badge' => $behind > 0 ? 'Plan' : 'Ready', 'tone' => $behind > 0 ? 'info' : 'ok', 'tab' => 'syllabus'];
        }

        return [
            'kpis' => [
                ['label' => 'Attendance today', 'value' => $att['latest_rate'] . '%', 'foot' => "30-day average {$avg30}%", 'tone' => $att['latest_rate'] >= 93 ? 'ok' : 'warn', 'tab' => 'attendance'],
                ['label' => 'Fee collection', 'value' => $fees['rate'] . '%', 'foot' => Format::inr($fees['overdue']) . ' overdue', 'tone' => $fees['rate'] >= 90 ? 'ok' : 'warn', 'tab' => 'fees'],
                ['label' => 'Marks average', 'value' => $marks['school_average'] . '%', 'foot' => "Pass rate {$passRate}%", 'tone' => 'info', 'tab' => 'marks'],
                ['label' => 'Students at risk', 'value' => (string) $riskN, 'foot' => 'of ' . count($roster) . ' students', 'tone' => $riskN > 3 ? 'risk' : 'warn', 'tab' => 'risk'],
                ['label' => 'Syllabus on pace', 'value' => $syllabusKpi['value'] ?? '—', 'foot' => $syllabusKpi['foot'] ?? '', 'tone' => 'warn', 'tab' => 'syllabus'],
            ],
            'daily' => $att['daily'],
            'avg30' => $avg30,
            'changes' => $changes,
            'scorecard' => $scorecard,
        ];
    }

    // ============================================================ approvals

    /**
     * @param  array<string, array{decision:string, at:int}>  $decided  ids already approved/declined
     */
    public function approvals(array $decided = []): array
    {
        $data = $this->engine->data();
        $today = $data->now()->startOfDay();
        $marks = $this->engine->marks->summary($data);
        $roster = array_column($this->engine->students->roster($data), null, 'id');
        $items = [];

        // 1. Result sign-off — papers that finished moderation.
        foreach ($data->examinations() as $e) {
            if ($e['status'] !== 'Moderation') {
                continue;
            }
            $avg = $marks['heatmap'][$e['class']][$e['subject']] ?? 0;
            $low = $avg < $marks['school_average'] - 8;
            $items[] = [
                'id' => 'result:' . md5($e['exam'] . $e['class'] . $e['subject']),
                'type' => 'Result sign-off', 'group' => 'results', 'tone' => 'info',
                'title' => "{$e['exam']} · {$e['class']} {$e['subject']}",
                'context' => "{$e['teacher']} · class average {$avg}% · school " . round($marks['school_average']) . '% · moderated',
                'waiting' => (int) CarbonImmutable::parse($e['date'], $today->getTimezone())->diffInDays($today),
                'ai' => $low ? 'Average is well below the school — check a few scripts before approving' : 'In line with the school average — safe to approve',
                'ai_tone' => $low ? 'warn' : 'ok',
                'tab' => 'examinations',
            ];
        }

        // 2. Fee concession requests — families 60+ days overdue (demo: request raised by office).
        foreach ($this->engine->fees->summary($data)['defaulters'] as $d) {
            if ($d['days'] <= 60) {
                continue;
            }
            $r = $roster[$d['id']];
            $pct = [20, 25, 30][hexdec(substr(md5($d['id']), 0, 2)) % 3];
            $items[] = [
                'id' => 'fee:' . $d['id'],
                'type' => 'Fee concession', 'group' => 'fees', 'tone' => 'warn',
                'title' => "{$d['name']} · {$d['class']} · {$pct}% concession requested",
                'context' => 'Parent via office · ' . Format::inr($d['amount']) . " overdue {$d['days']} days · attendance {$r['attendance']}% · marks {$r['marks']}%",
                'waiting' => 1 + hexdec(substr(md5($d['id']), 2, 2)) % 6,
                'ai' => $r['signal_type'] !== 'ok'
                    ? 'Also at risk academically — suggest 15% + 3-month instalment plan and a counsellor call'
                    : 'Good attendance and marks — suggest an instalment plan; concession optional',
                'ai_tone' => 'info',
                'tab' => 'fees',
            ];
        }

        // 3. Staff leave for today, with the cover plan ready.
        $sub = $this->engine->substitution->gaps($data);
        foreach ($sub['on_leave'] as $t) {
            $gaps = array_filter($sub['gaps'], fn ($g) => $g['teacher_id'] === $t['id']);
            $uncovered = count(array_filter($gaps, fn ($g) => $g['suggested'] === null));
            $items[] = [
                'id' => 'leave:' . $t['id'] . ':' . $today->toDateString(),
                'type' => 'Leave', 'group' => 'leave', 'tone' => 'info',
                'title' => "{$t['name']} · leave · today",
                'context' => "{$t['designation']} · " . Format::plural(count($gaps), 'remaining period') . ' affected',
                'waiting' => 0,
                'ai' => match (true) {
                    count($gaps) === 0 => 'No classes affected today — safe to approve',
                    $uncovered > 0 => Format::plural($uncovered, 'period') . ' ' . Format::verb($uncovered, 'has', 'have') . ' no free teacher — plan a combined class',
                    default => 'Cover is ready for every period — approve and send the substitution plan',
                },
                'ai_tone' => $uncovered ? 'warn' : 'ok',
                'tab' => 'timetable',
            ];
        }

        $pending = array_values(array_filter($items, fn ($i) => ! isset($decided[$i['id']])));
        usort($pending, fn ($a, $b) => $b['waiting'] <=> $a['waiting']);
        $weekAgo = $data->now()->subDays(7)->timestamp;

        return [
            'items' => $pending,
            'counts' => array_count_values(array_column($pending, 'group')),
            'oldest' => $pending ? max(array_column($pending, 'waiting')) : 0,
            'decided_week' => count(array_filter($decided, fn ($d) => ($d['at'] ?? 0) >= $weekAgo)),
        ];
    }

    // ============================================================ circulars

    /**
     * Drafts one notice for each alert that parents should hear about.
     *
     * @param  array<string, array{status:string, at:int, title?:string, audience?:int}>  $handled
     */
    public function circulars(InsightReport $report, array $handled = []): array
    {
        $data = $this->engine->data();
        $att = $this->engine->attendance->summary($data);
        $fees = $this->engine->fees->summary($data);
        $drafts = [];

        // Attendance drop in a section
        $drops = array_values(array_filter($att['sections'], fn ($s) => $s['drop'] >= 6));
        usort($drops, fn ($a, $b) => $b['drop'] <=> $a['drop']);
        foreach (array_slice($drops, 0, 2) as $s) {
            $drafts[] = [
                'id' => 'att-drop:' . $s['class'] . ':' . $data->now()->toDateString(),
                'title' => "Attendance concern · {$s['class']} parents",
                'why' => "{$s['class']} attendance {$s['rate']}% today (usually {$s['usual']}%)",
                'audience' => $s['strength'], 'tone' => 'risk',
                'message' => "Dear Parent, we noticed lower attendance in Class {$s['class']} today. If your child is unwell, please inform the class teacher. Regular attendance helps your child stay on track.",
                'channels' => ['WhatsApp', 'App'],
            ];
        }

        // Absence streaks — wellbeing check
        if ($att['streaks']) {
            $drafts[] = [
                'id' => 'streaks:' . md5(implode(',', array_column($att['streaks'], 'id'))),
                'title' => 'Wellbeing check · absent 3+ days',
                'why' => count($att['streaks']) . ' students absent several days in a row: ' . implode(', ', array_column(array_slice($att['streaks'], 0, 3), 'name')),
                'audience' => count($att['streaks']), 'tone' => 'risk',
                'message' => "Dear Parent, {student} has been absent for {days} days. We hope everything is fine. Please let the class teacher know if the school can help in any way.",
                'channels' => ['SMS', 'WhatsApp'],
            ];
        }

        // Fee reminders
        $over60 = array_filter($fees['defaulters'], fn ($d) => $d['days'] > 60);
        if ($over60) {
            $drafts[] = [
                'id' => 'fee60:' . md5(implode(',', array_column($over60, 'id'))),
                'title' => 'Fee reminder · 60+ days overdue',
                'why' => Format::inr(array_sum(array_column($over60, 'amount'))) . ' overdue from ' . count($over60) . ' families',
                'audience' => count($over60), 'tone' => 'warn',
                'message' => 'Dear Parent, fees of {amount} for {student} are overdue since {date}. Please pay at the office or online, or contact us to discuss an instalment plan.',
                'channels' => ['SMS', 'WhatsApp'],
            ];
        }
        if ($fees['new_this_week']) {
            $drafts[] = [
                'id' => 'feenew:' . md5(implode(',', array_column($fees['new_this_week'], 'id'))),
                'title' => 'Gentle fee reminder · new this week',
                'why' => count($fees['new_this_week']) . ' families became overdue this week',
                'audience' => count($fees['new_this_week']), 'tone' => 'info',
                'message' => 'Dear Parent, this is a friendly reminder that {amount} for {student} was due on {date}. Please ignore if already paid.',
                'channels' => ['SMS'],
            ];
        }

        // Upcoming exam
        $next = $this->engine->exams->nextExam($data);
        if ($next && $next['days'] <= 30) {
            $date = CarbonImmutable::parse($next['date'])->format('j F');
            $drafts[] = [
                'id' => 'exam:' . md5($next['exam']),
                'title' => "{$next['exam']} date sheet",
                'why' => "Exams start {$date} · {$next['days']} days to go",
                'audience' => count($data->students()), 'tone' => 'info',
                'message' => "The {$next['exam']} begins on {$date}. Please find your child's date sheet attached. Revision classes start one week before.",
                'channels' => ['App', 'Email'],
            ];
        }

        // Weakest section-subject → remedial classes
        $weak = collect($report->all)->firstWhere('key', 'academics.weak-sections');
        if ($weak && preg_match('/^(\d-[AB]) (.+?) averages/', $weak->title, $m)) {
            $low = array_filter($data->students(), fn ($s) => $s['class'] === $m[1] && ($s['subject_marks'][$m[2]] ?? 100) < 50);
            if ($low) {
                $drafts[] = [
                    'id' => 'remedial:' . $m[1] . $m[2],
                    'title' => "Remedial classes · {$m[1]} {$m[2]}",
                    'why' => $weak->title,
                    'audience' => count($low), 'tone' => 'info',
                    'message' => "Your child has been selected for extra {$m[2]} support twice a week for 4 weeks, starting Monday. There is no extra fee.",
                    'channels' => ['WhatsApp'],
                ];
            }
        }

        $weekAgo = $data->now()->subDays(7)->timestamp;
        $sent = array_filter($handled, fn ($h) => $h['status'] === 'sent' && $h['at'] >= $weekAgo);
        uasort($sent, fn ($a, $b) => $b['at'] <=> $a['at']);

        return [
            'drafts' => array_values(array_filter($drafts, fn ($d) => ! isset($handled[$d['id']]))),
            'alerts' => array_slice($report->insights, 0, 8),
            'sent' => array_values($sent),
        ];
    }
}
