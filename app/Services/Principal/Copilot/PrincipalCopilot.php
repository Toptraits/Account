<?php

namespace App\Services\Principal\Copilot;

use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\InsightEngine;

/**
 * Understands a principal's question and answers it from live school data.
 *
 *   "9A marks below 50%"            → class 9-A, marks, below 50
 *   "who is absent today in 7b"     → attendance, today, 7-B
 *   "fees pending more than 60 days"→ fees, older than 60 days
 *   "tell me about Rohan Sharma"    → one student's full profile
 *   "compare 6A and 6B"             → side-by-side
 *
 * Step 1 parse() pulls out class/section, subject, numbers, names.
 * Step 2 the first matching handler answers and says which tab/filters to open.
 * Returns null only when it truly does not understand (the page then asks the AI model).
 */
class PrincipalCopilot
{
    private const SUBJECTS = [
        'Mathematics' => '/\b(maths?|mathematics|algebra|geometry)\b/',
        'Science' => '/\b(science|sci|physics|chemistry|biology)\b/',
        'English' => '/\b(english|eng|grammar)\b/',
        'Social Studies' => '/\b(social(?: studies)?|sst|history|geography|civics)\b/',
        'Computer Science' => '/\b(computers?|cs|computer science|it)\b/',
    ];

    private array $e = [];

    public function __construct(private InsightEngine $engine)
    {
    }

    /** @return array{answer:string, tab:?string, state:array<string,mixed>}|null */
    public function answer(string $question): ?array
    {
        $q = ' ' . mb_strtolower(trim(preg_replace('/\s+/', ' ', $question))) . ' ';
        $this->e = $this->parse($q);

        foreach ([
            'help', 'briefing', 'compare', 'student', 'teacher', 'substitution', 'timetable',
            'fees', 'attendance', 'marks', 'risk', 'syllabus', 'exams', 'counts', 'staffList',
        ] as $handler) {
            $result = $this->{$handler}($q);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    // ================================================================= parsing

    private function parse(string $q): array
    {
        $data = $this->engine->data();

        // Sections: "9a", "9-a", "9 b", "class 9a", "grade 8 section b"
        preg_match_all('/\b([6-9])\s*[- ]?\s*(?:section\s*)?([ab])\b/', $q, $m, PREG_SET_ORDER);
        $sections = array_values(array_unique(array_map(fn ($x) => $x[1] . '-' . strtoupper($x[2]), $m)));
        $class = null;
        if ($sections === [] && preg_match('/\b(?:class|grade|std|standard)\s*([6-9])\b/', $q, $c)) {
            $class = $c[1];
        }

        $subject = null;
        foreach (self::SUBJECTS as $name => $pattern) {
            if (preg_match($pattern, $q)) {
                $subject = $name;
                break;
            }
        }

        // "below 50%", "less than 40", "under 75 %", "above 80", "more than 60 days", "over 20k"
        $cmp = null;
        if (preg_match('/(below|under|less than|lower than|fewer than|<|not more than)\s*(?:rs\.?|₹)?\s*(\d+(?:\.\d+)?)\s*(k|%|percent|days?)?/', $q, $n)) {
            $cmp = ['op' => 'lt', 'n' => $this->num($n[2], $n[3] ?? ''), 'unit' => $this->unit($n[3] ?? '')];
        } elseif (preg_match('/(above|over|more than|greater than|higher than|>|at least|min(?:imum)?)\s*(?:rs\.?|₹)?\s*(\d+(?:\.\d+)?)\s*(k|%|percent|days?)?/', $q, $n)) {
            $cmp = ['op' => 'gt', 'n' => $this->num($n[2], $n[3] ?? ''), 'unit' => $this->unit($n[3] ?? '')];
        }

        $limit = preg_match('/\b(?:top|bottom|best|worst|first|last)\s*(\d{1,2})\b/', $q, $l) ? (int) $l[1] : null;

        // Student: by ID, by full name, then by a unique first name.
        $student = null;
        $candidates = [];
        foreach ($data->students() as $s) {
            if (str_contains($q, mb_strtolower($s['id'])) || str_contains($q, ' ' . mb_strtolower($s['name']) . ' ') || str_contains($q, mb_strtolower($s['name']))) {
                $student = $s;
                break;
            }
        }

        // Teacher: by full name, or "ms rao", "mr das", or first name with a teacher word.
        $teacher = null;
        $teacherWord = (bool) preg_match('/\b(teacher|sir|madam|ma\'?am|mr|mrs|ms|miss|staff|faculty)\b/', $q);
        foreach ($data->staff() as $t) {
            $full = mb_strtolower($t['name']);
            [$first, $last] = array_pad(explode(' ', $full, 2), 2, '');
            if (str_contains($q, $full)
                || preg_match('/\b(mr|mrs|ms|miss)\.?\s+' . preg_quote($last, '/') . '\b/', $q)
                || ($teacherWord && preg_match('/\b(' . preg_quote($first, '/') . '|' . preg_quote($last, '/') . ')\b/', $q))) {
                $teacher = $t;
                break;
            }
        }

        if ($student === null && $teacher === null) {
            foreach ($data->students() as $s) {
                $first = mb_strtolower(explode(' ', $s['name'])[0]);
                if (preg_match('/\b' . preg_quote($first, '/') . '\b/', $q)) {
                    $candidates[] = $s;
                }
            }
            if (count($candidates) === 1 && $this->wantsSingleStudent($q)) {
                $student = $candidates[0];
            }
        }

        return [
            'sections' => $sections,
            'section' => $sections[0] ?? null,
            'class' => $class,
            'scope' => $sections[0] ?? $class ?? 'all',
            'subject' => $subject,
            'cmp' => $cmp,
            'limit' => $limit,
            'student' => $student,
            'candidates' => $candidates,
            'teacher' => $teacher,
            'today' => (bool) preg_match('/\b(today|now|right now|currently|this morning)\b/', $q),
        ];
    }

    private function num(string $n, string $unit): float
    {
        return str_starts_with($unit, 'k') ? (float) $n * 1000 : (float) $n;
    }

    private function unit(string $u): string
    {
        return match (true) {
            str_starts_with($u, 'day') => 'days',
            $u === '%' || $u === 'percent' => '%',
            $u === 'k' => 'amount',
            default => '',
        };
    }

    private function wantsSingleStudent(string $q): bool
    {
        return (bool) preg_match('/\b(about|profile|details?|how is|how\'s|show|tell|report of|info|doing)\b/', $q)
            || str_word_count(trim($q)) <= 3;
    }

    private function scopeLabel(): string
    {
        $s = $this->e['scope'];

        return $s === 'all' ? 'the whole school' : (str_contains($s, '-') ? "Class {$s}" : "Class {$s} (both sections)");
    }

    private function inScope(string $class): bool
    {
        $s = $this->e['scope'];

        return $s === 'all' || $class === $s || (! str_contains($s, '-') && str_starts_with($class, $s . '-'));
    }

    private function result(string $answer, ?string $tab = null, array $state = []): array
    {
        return ['answer' => trim($answer), 'tab' => $tab, 'state' => $state];
    }

    private function bullets(array $lines, int $max = 12): string
    {
        $more = count($lines) - $max;

        return implode("\n", array_slice($lines, 0, $max)) . ($more > 0 ? "\n…and {$more} more (open the tab for the full list)" : '');
    }

    private function sectionState(): array
    {
        $s = $this->e['scope'];
        if ($s === 'all') {
            return [];
        }
        [$class, $sec] = array_pad(explode('-', $s), 2, 'all');

        return ['class' => $class, 'section' => $sec];
    }

    /** Shown when nothing matched and the AI model is not reachable. */
    public function notUnderstood(string $question): string
    {
        return "I couldn't find school data for “{$question}”. I can answer about marks, attendance, fees, students, teachers, timetable, syllabus and exams. Try:\n"
            . "• 9A marks below 50%\n• who is absent today in 7B\n• fees pending more than 60 days\n• tell me about <student name>\n• who is on leave today\n• compare 6A and 6B\n\nType “help” for more examples.";
    }

    // ================================================================ handlers

    private function help(string $q): ?array
    {
        if (! preg_match('/^\s*(help|\?|what can you do|what can i ask|examples?)\s*$/', $q)) {
            return null;
        }

        return $this->result(<<<TXT
        You can ask in plain words. For example:
        • 9A marks below 50%  ·  top 5 in maths  ·  which section is weakest in science
        • who is absent today in 7B  ·  attendance below 80% in class 8  ·  absent 3 days in a row
        • fee defaulters in 6A  ·  fees pending more than 60 days  ·  which class has most dues
        • tell me about Rohan Sharma  ·  how is Ms Rao  ·  who is on leave today
        • who is teaching now  ·  free teachers now  ·  timetable for 8B
        • syllabus behind in 6B  ·  when is the next exam  ·  marks entry pending
        • compare 6A and 6B  ·  at-risk students in 9B  ·  what should I focus on today
        TXT);
    }

    private function briefing(string $q): ?array
    {
        if (! preg_match('/what should i|my focus|brief|summary|summari[sz]e|priorit|what(?:\'s| is) (?:happening|important|urgent)|how is (?:the )?school|overview/', $q)) {
            return null;
        }
        $r = $this->engine->report('copilot');
        $lines = array_map(fn ($l, $i) => ($i + 1) . '. ' . $l, $r->briefing, array_keys($r->briefing));
        $kpis = array_map(fn ($k) => "• {$k['label']}: {$k['value']} ({$k['foot']})", $r->kpis);

        return $this->result("Today's priorities:\n" . implode("\n", $lines) . "\n\nAt a glance:\n" . implode("\n", $kpis), 'overview');
    }

    private function compare(string $q): ?array
    {
        if (count($this->e['sections']) < 2 || ! preg_match('/compare|vs\.?|versus|difference|between/', $q)) {
            return null;
        }
        $data = $this->engine->data();
        $att = $this->engine->attendance->summary($data)['students'];
        $fees = $this->engine->fees->summary($data)['defaulters'];
        $roster = $this->engine->students->roster($data);
        $out = [];

        foreach (array_slice($this->e['sections'], 0, 3) as $sec) {
            $m = $this->engine->marks->summary($data, $sec, 40);
            $a = array_filter($att, fn ($s) => $s['class'] === $sec);
            $attPct = $a ? round(array_sum(array_column($a, 'pct')) / count($a), 1) : 0;
            $f = array_filter($fees, fn ($d) => $d['class'] === $sec);
            $risk = count(array_filter($roster, fn ($r) => $r['class'] === $sec && $r['signal_type'] === 'risk'));
            $subjects = implode(', ', array_map(fn ($r) => "{$r['subject']} {$r['average']}%", $m['subject_rows']));

            $out[] = "{$sec}\n  Marks average {$m['scope_average']}% (rank {$m['rank']} of {$m['section_count']}) · {$subjects}\n"
                . "  Attendance (30 days) {$attPct}% · Low-marks students " . count($m['low_students']) . "\n"
                . '  Fee defaulters ' . count($f) . ' (' . Format::inr(array_sum(array_column($f, 'amount'))) . ") · At-risk students {$risk}";
        }

        return $this->result("Comparison:\n\n" . implode("\n\n", $out), 'marks');
    }

    private function student(string $q): ?array
    {
        $s = $this->e['student'];
        if ($s === null) {
            // A first name shared by several students → ask which one.
            $c = $this->e['candidates'];
            if (count($c) > 1 && $this->wantsSingleStudent($q) && $this->e['teacher'] === null) {
                return $this->result('Several students match that name — which one?' . "\n" . implode("\n", array_map(fn ($x) => "• {$x['name']} ({$x['class']}, {$x['id']})", $c)));
            }

            return null;
        }

        $data = $this->engine->data();
        $r = collect($this->engine->students->roster($data))->firstWhere('id', $s['id']);
        $a = $this->engine->attendance->summary($data)['students'][$s['id']];
        $fee = collect($this->engine->fees->summary($data)['defaulters'])->firstWhere('id', $s['id']);
        $marks = implode(' · ', array_map(fn ($sub, $mk) => "{$sub} {$mk}%", array_keys($s['subject_marks']), $s['subject_marks']));
        $today = match ($a['today']) { 'P' => 'present', 'L' => 'present (late)', 'A' => 'absent', default => 'register not taken yet' };

        $text = "{$s['name']} · {$s['class']} · {$s['id']}\n"
            . "Marks: {$marks} → average {$r['marks']}% (grade {$r['grade']})\n"
            . "Attendance: {$a['pct']}% over 30 school days · {$a['absences']} absences · {$a['lates']} late · today {$today}"
            . ($a['streak'] >= 2 ? " · absent {$a['streak']} days in a row" : '') . "\n"
            . 'Fees: ' . ($fee ? Format::inr($fee['amount']) . " overdue for {$fee['days']} days ({$fee['heads']})" : 'no overdue fees') . "\n"
            . "Risk: {$r['risk_score']}/100 — " . ($r['reasons'] ? implode(', ', $r['reasons']) : 'on track') . "\n"
            . "Parent: {$s['parent_phone']}\n\n"
            . "Suggested plan: {$r['strategy']}";

        return $this->result($text, 'students', ['student' => $s['id']]);
    }

    private function teacher(string $q): ?array
    {
        $t = $this->e['teacher'];
        if ($t === null) {
            return null;
        }
        $data = $this->engine->data();
        $periods = array_values(array_filter($data->timetableToday(), fn ($p) => $p['teacher_id'] === $t['id']));
        usort($periods, fn ($x, $y) => strcmp($x['start'], $y['start']));
        $pending = array_filter($data->examinations(), fn ($e) => $e['teacher_id'] === $t['id'] && $e['status'] === 'Marks entry');
        $gaps = array_filter($this->engine->substitution->gaps($data)['gaps'], fn ($g) => $g['teacher_id'] === $t['id']);

        $text = "{$t['name']} · {$t['designation']} · {$t['department']}\n"
            . "Today: {$t['status']}\nClasses: {$t['classes']}\n";
        if ($periods) {
            $text .= "Today's periods:\n" . implode("\n", array_map(fn ($p) => "• {$p['time']} {$p['class']} {$p['subject']} ({$p['status']})", $periods)) . "\n";
        }
        if ($gaps) {
            $text .= "Cover needed:\n" . implode("\n", array_map(fn ($g) => "• {$g['time']} {$g['class']} → " . ($g['suggested'] ?? 'no one free'), $gaps)) . "\n";
        }
        $text .= $pending ? 'Marks entry pending: ' . count($pending) . ' papers (' . implode(', ', array_map(fn ($e) => "{$e['class']} {$e['subject']}", array_slice(array_values($pending), 0, 6))) . ')' : 'No marks entry pending.';

        return $this->result($text, 'staff', ['staffSearch' => $t['name']]);
    }

    private function substitution(string $q): ?array
    {
        if (! preg_match('/substitut|\bcover\b|on leave|leave today|who is (?:absent|not here|missing) (?:among|from) (?:staff|teachers)|teachers? absent|absent teachers?/', $q)) {
            return null;
        }
        $s = $this->engine->substitution->gaps($this->engine->data());
        if ($s['on_leave'] === []) {
            return $this->result('All teachers are present today. No cover needed.', 'staff');
        }
        $names = implode(', ', array_column($s['on_leave'], 'name'));
        $lines = array_map(fn ($g) => "• {$g['time']} {$g['class']} {$g['subject']} ({$g['absent_teacher']}) → " . ($g['suggested'] ? "cover: {$g['suggested']}" . ($g['same_subject'] ? ' (same subject)' : '') : 'no one free') . ($g['status'] === 'In progress' ? ' — happening now' : ''), $s['gaps']);

        return $this->result("On leave today: {$names}.\n" . ($lines ? 'Periods needing cover (' . count($lines) . "):\n" . $this->bullets($lines) : 'Their remaining periods are already over.'), 'timetable');
    }

    private function timetable(string $q): ?array
    {
        $data = $this->engine->data();
        $rows = $data->timetableToday();

        if (preg_match('/free (?:teachers?|staff)|who is free|available teachers?/', $q)) {
            $now = array_filter($rows, fn ($p) => $p['status'] === 'In progress');
            $busy = array_column($now, 'teacher_id');
            $free = array_filter($data->staff(), fn ($t) => $t['status'] === 'Present' && str_contains($t['designation'], 'Teacher') && ! in_array($t['id'], $busy, true));

            if ($now === []) {
                $free = array_filter($data->staff(), fn ($t) => $t['status'] === 'Present' && str_contains($t['designation'], 'Teacher'));

                return $this->result("No period is running right now, so every present teacher is free:\n" . implode("\n", array_map(fn ($t) => "• {$t['name']} ({$t['department']})", $free)), 'timetable');
            }

            return $this->result('Free right now (' . reset($now)['time'] . "):\n" . ($free ? implode("\n", array_map(fn ($t) => "• {$t['name']} ({$t['department']})", $free)) : 'Nobody — every present teacher is in a class.'), 'timetable');
        }

        if (preg_match('/teaching now|going on now|current period|happening now|in class now|who is teaching/', $q)) {
            $now = array_values(array_filter($rows, fn ($p) => $p['status'] === 'In progress' && $this->inScope($p['class'])));

            if ($now === []) {
                $upcoming = array_filter($rows, fn ($p) => $p['status'] === 'Upcoming' && $this->inScope($p['class']));
                $nextStart = $upcoming ? min(array_column($upcoming, 'start')) : null;
                $next = array_filter($upcoming, fn ($p) => $p['start'] === $nextStart);

                return $this->result($nextStart === null
                    ? 'Classes are over for today.'
                    : "No period is running right now (break). Next period at {$nextStart}:\n" . implode("\n", array_map(fn ($p) => "• {$p['class']} — {$p['subject']} with {$p['teacher']}", $next)), 'timetable');
            }

            return $this->result('Running now (' . $now[0]['time'] . "):\n" . implode("\n", array_map(fn ($p) => "• {$p['class']} — {$p['subject']} with {$p['teacher']} in {$p['room']}", $now)), 'timetable');
        }

        if (! preg_match('/time ?table|schedule|periods?|next class|next period/', $q) || preg_match('/exam/', $q)) {
            return null;
        }
        $sec = $this->e['section'] ?? '6-A';
        $list = array_values(array_filter($rows, fn ($p) => $p['class'] === $sec));
        [$c, $s] = explode('-', $sec);

        return $this->result($list === [] ? "No classes for {$sec} today." : "Today's timetable for {$sec}:\n" . implode("\n", array_map(fn ($p) => "• {$p['time']} {$p['subject']} — {$p['teacher']} ({$p['status']})", $list)), 'timetable', ['timetableClass' => $c, 'timetableSection' => $s]);
    }

    private function fees(string $q): ?array
    {
        if (! preg_match('/\bfees?\b|defaulter|not paid|unpaid|outstanding|\bdues?\b|pending (?:amount|payment)|payment|collection|collected|tuition|transport/', $q)) {
            return null;
        }
        $f = $this->engine->fees->summary($this->engine->data());
        $list = array_values(array_filter($f['defaulters'], fn ($d) => $this->inScope($d['class'])));
        $cmp = $this->e['cmp'];
        $filterText = '';
        if ($cmp && ($cmp['unit'] === 'days' || ($cmp['unit'] === '' && $cmp['n'] <= 365 && preg_match('/days?|old|since/', $q)))) {
            $list = array_values(array_filter($list, fn ($d) => $cmp['op'] === 'gt' ? $d['days'] > $cmp['n'] : $d['days'] < $cmp['n']));
            $filterText = ($cmp['op'] === 'gt' ? ' overdue more than ' : ' overdue less than ') . $cmp['n'] . ' days';
        } elseif ($cmp && ($cmp['unit'] === 'amount' || $cmp['n'] >= 1000)) {
            $list = array_values(array_filter($list, fn ($d) => $cmp['op'] === 'gt' ? $d['amount'] > $cmp['n'] : $d['amount'] < $cmp['n']));
            $filterText = ($cmp['op'] === 'gt' ? ' owing more than ' : ' owing less than ') . Format::inr($cmp['n']);
        }
        if (preg_match('/new|this week|recent/', $q)) {
            $list = array_values(array_filter($list, fn ($d) => $d['is_new']));
            $filterText .= ' (new this week)';
        }

        // "which class has most dues"
        if (preg_match('/which (?:class|section)|most (?:dues|pending|defaulters)|class[- ]?wise|by class|section[- ]?wise/', $q)) {
            $by = [];
            foreach ($f['defaulters'] as $d) {
                $by[$d['class']]['amount'] = ($by[$d['class']]['amount'] ?? 0) + $d['amount'];
                $by[$d['class']]['n'] = ($by[$d['class']]['n'] ?? 0) + 1;
            }
            uasort($by, fn ($a, $b) => $b['amount'] <=> $a['amount']);

            return $this->result("Overdue fees by section (highest first):\n" . implode("\n", array_map(fn ($c, $v) => "• {$c}: " . Format::inr($v['amount']) . " from {$v['n']} " . ($v['n'] === 1 ? 'family' : 'families'), array_keys($by), $by)), 'fees');
        }

        if (preg_match('/collect|collection|how much.*(?:received|paid)|total/', $q) && $list === $f['defaulters']) {
            return $this->result("Fees due so far: " . Format::inr($f['expected']) . "\nCollected: " . Format::inr($f['collected']) . " ({$f['rate']}%)\nOverdue: " . Format::inr($f['overdue']) . ' from ' . count($f['defaulters']) . " families\nFalling due in next 7 days: " . Format::inr($f['due_next_7']), 'fees');
        }

        $total = array_sum(array_column($list, 'amount'));
        $lines = array_map(fn ($d) => "• {$d['name']} ({$d['class']}) — " . Format::inr($d['amount']) . ", {$d['days']} days · {$d['heads']} · {$d['phone']}", $list);

        return $this->result(
            'Fee defaulters in ' . $this->scopeLabel() . $filterText . ': ' . count($list) . ' ' . (count($list) === 1 ? 'family' : 'families') . ' · ' . Format::inr($total) . "\n"
            . ($lines ? "Most urgent first (amount × days):\n" . $this->bullets($lines) : 'Nobody in this group. 🎉'),
            'fees',
            ['feeFilter' => str_contains($filterText, 'new') ? 'new' : 'all'],
        );
    }

    private function attendance(string $q): ?array
    {
        if (! preg_match('/attendance|absent|absentee|present|late (?:comers?|arrivals?)|came late|\blate\b|bunk|days in a row|continuous(?:ly)? absent|not (?:come|coming)/', $q)) {
            return null;
        }
        $a = $this->engine->attendance->summary($this->engine->data());
        $students = array_values(array_filter($a['students'], fn ($s) => $this->inScope($s['class'])));
        $label = $this->scopeLabel();

        if (preg_match('/days in a row|continuous|streak|long(?:er)? absen|several days|not (?:come|coming)/', $q)) {
            $list = array_values(array_filter($students, fn ($s) => $s['streak'] >= ($this->e['cmp']['n'] ?? 3)));
            usort($list, fn ($x, $y) => $y['streak'] <=> $x['streak']);

            return $this->result("Absent several days in a row in {$label}: " . count($list) . "\n" . ($list ? $this->bullets(array_map(fn ($s) => "• {$s['name']} ({$s['class']}) — {$s['streak']} days · parent {$s['phone']}", $list)) : 'Nobody. 🎉'), 'attendance', ['attendanceFilter' => 'streaks']);
        }

        if (preg_match('/\blate\b|came late|late comers?|late arrivals?/', $q)) {
            $list = array_values(array_filter($students, fn ($s) => $s['today'] === 'L'));

            return $this->result("Late today in {$label}: " . count($list) . "\n" . implode("\n", array_map(fn ($s) => "• {$s['name']} ({$s['class']}) — {$s['lates']} late arrivals this month", $list)), 'attendance');
        }

        if (($this->e['today'] || preg_match('/who (?:is|are) absent|absentees|absent list/', $q)) && ! $this->e['cmp']) {
            if (! $a['register_taken']) {
                return $this->result("Today's register opens at 09:30. Ask again after that.", 'attendance');
            }
            $absent = array_values(array_filter($students, fn ($s) => $s['today'] === 'A'));
            $present = count(array_filter($students, fn ($s) => $s['today'] !== 'A'));
            $rate = $students ? round($present / count($students) * 100, 1) : 0;

            return $this->result("Today in {$label}: {$present} of " . count($students) . " present ({$rate}%).\nAbsent (" . count($absent) . "):\n" . ($absent ? implode("\n", array_map(fn ($s) => "• {$s['name']} ({$s['class']})" . ($s['streak'] >= 2 ? " — {$s['streak']} days in a row" : '') . " · parent {$s['phone']}", $absent)) : 'Nobody. 🎉'), 'attendance');
        }

        if (preg_match('/which (?:class|section)|lowest|highest|best|worst|class[- ]?wise|section[- ]?wise|ranking/', $q) && $this->e['scope'] === 'all') {
            $sections = $a['sections'];
            $low = (bool) preg_match('/lowest|worst|poor|least/', $q);
            usort($sections, fn ($x, $y) => $low ? $x['rate'] <=> $y['rate'] : $y['rate'] <=> $x['rate']);

            return $this->result('Attendance by section today (' . ($low ? 'lowest' : 'highest') . " first):\n" . implode("\n", array_map(fn ($s) => "• {$s['class']}: {$s['rate']}% ({$s['absent']} absent) · usually {$s['usual']}%", $sections)), 'attendance');
        }

        $cmp = $this->e['cmp'] ?? ['op' => 'lt', 'n' => (float) config('principal-insights.attendance.defaulter_below', 75)];
        $list = array_values(array_filter($students, fn ($s) => $cmp['op'] === 'lt' ? $s['pct'] < $cmp['n'] : $s['pct'] > $cmp['n']));
        usort($list, fn ($x, $y) => $cmp['op'] === 'lt' ? $x['pct'] <=> $y['pct'] : $y['pct'] <=> $x['pct']);
        $avg = $students ? round(array_sum(array_column($students, 'pct')) / count($students), 1) : 0;

        return $this->result(
            "Attendance in {$label} (last 30 school days): average {$avg}%.\n"
            . 'Students ' . ($cmp['op'] === 'lt' ? 'below ' : 'above ') . "{$cmp['n']}%: " . count($list) . "\n"
            . ($list ? $this->bullets(array_map(fn ($s) => "• {$s['name']} ({$s['class']}) — {$s['pct']}% · {$s['absences']} absences" . ($s['streak'] >= 2 ? " · absent {$s['streak']} days running" : ''), $list)) : 'Nobody. 🎉'),
            'attendance',
            ['attendanceFilter' => 'defaulters'],
        );
    }

    private function marks(string $q): ?array
    {
        if (! preg_match('/marks?|score|percentage|%|fail|pass|topper|top \d|top student|best student|rank|perform|grade|weak|strong|average|maths?|science|english|social|sst/', $q)
            || preg_match('/marks entry|not entered|enter(?:ed|ing)? marks|date ?sheet|exam (?:date|schedule)|next exam/', $q)) {
            return null;
        }
        // "% below 75 attendance" was already handled; here % means marks.
        $data = $this->engine->data();
        $subject = $this->e['subject'];
        $label = $this->scopeLabel() . ($subject ? " · {$subject}" : '');
        $students = array_values(array_filter($data->students(), fn ($s) => $this->inScope($s['class'])));
        $score = fn ($s) => $subject ? ($s['subject_marks'][$subject] ?? 0) : round(array_sum($s['subject_marks']) / count($s['subject_marks']), 1);
        $state = $this->sectionState();
        $marksState = ['marksClass' => $state['class'] ?? 'all', 'marksSection' => $state['section'] ?? 'all'];

        // Which section is best / weakest
        if (preg_match('/which (?:class|section)|(?:best|weakest|worst|top|lowest|highest) (?:class|section)|class[- ]?wise|section[- ]?wise|rank (?:the )?(?:classes|sections)/', $q)) {
            $heat = $this->engine->marks->summary($data)['heatmap'];
            $key = $subject ?? 'Overall';
            $low = (bool) preg_match('/weak|worst|lowest|poor|least/', $q);
            uasort($heat, fn ($x, $y) => $low ? $x[$key] <=> $y[$key] : $y[$key] <=> $x[$key]);

            return $this->result(($subject ?? 'Overall') . ' average by section (' . ($low ? 'weakest' : 'strongest') . " first):\n" . implode("\n", array_map(fn ($sec, $row) => "• {$sec}: {$row[$key]}%", array_keys($heat), $heat)), 'marks', ['marksClass' => 'all', 'marksSection' => 'all']);
        }

        // Toppers
        if (preg_match('/\b(top|topper|toppers|best|highest|rank holders?|first rank)\b/', $q) && ! preg_match('/lowest|weak|bottom/', $q)) {
            usort($students, fn ($x, $y) => $score($y) <=> $score($x));
            $n = $this->e['limit'] ?? 5;

            return $this->result("Top {$n} in {$label}:\n" . implode("\n", array_map(fn ($s, $i) => ($i + 1) . ". {$s['name']} ({$s['class']}) — " . $score($s) . '%', array_slice($students, 0, $n), array_keys(array_slice($students, 0, $n)))), 'marks', $marksState);
        }

        // Bottom N
        if (preg_match('/\b(bottom|lowest|weakest|last)\b/', $q) && ! $this->e['cmp']) {
            usort($students, fn ($x, $y) => $score($x) <=> $score($y));
            $n = $this->e['limit'] ?? 5;

            return $this->result("Lowest {$n} in {$label}:\n" . implode("\n", array_map(fn ($s, $i) => ($i + 1) . ". {$s['name']} ({$s['class']}) — " . $score($s) . '%', array_slice($students, 0, $n), array_keys(array_slice($students, 0, $n)))), 'marks', $marksState);
        }

        // Threshold list: "below 50%", "above 80", "failing" (= below pass mark)
        $cmp = $this->e['cmp'];
        if ($cmp === null && preg_match('/fail/', $q)) {
            $cmp = ['op' => 'lt', 'n' => (float) config('principal-insights.marks.pass_mark', 40)];
        }
        $summary = $this->engine->marks->summary($data, $this->e['scope'], 40);
        $subjectLines = implode("\n", array_map(fn ($r) => "• {$r['subject']}: {$r['average']}% (school {$r['school_average']}%)", $summary['subject_rows']));

        if ($cmp === null) {
            return $this->result("Marks for {$this->scopeLabel()}: average {$summary['scope_average']}%" . ($summary['rank'] ? " (rank {$summary['rank']} of {$summary['section_count']} sections)" : '') . ".\n{$subjectLines}\n\nTip: ask “{$this->e['scope']} marks below 50%” or “top 5 in maths”.", 'marks', $marksState);
        }

        $n = $cmp['n'];
        if ($cmp['op'] === 'lt') {
            // Below: with a subject → that subject; without → any subject below (shows which).
            $rows = [];
            foreach ($students as $s) {
                $weak = $subject
                    ? (($s['subject_marks'][$subject] ?? 100) < $n ? [$subject => $s['subject_marks'][$subject]] : [])
                    : array_filter($s['subject_marks'], fn ($m) => $m < $n);
                if ($weak) {
                    asort($weak);
                    $rows[] = ['s' => $s, 'weak' => $weak, 'avg' => $score($s)];
                }
            }
            usort($rows, fn ($x, $y) => count($y['weak']) <=> count($x['weak']) ?: $x['avg'] <=> $y['avg']);
            $lines = array_map(fn ($r) => "• {$r['s']['name']} ({$r['s']['class']}) — " . implode(', ', array_map(fn ($sub, $m) => "{$sub} {$m}%", array_keys($r['weak']), $r['weak'])) . ($subject ? '' : " · average {$r['avg']}%"), $rows);
            $head = 'Students in ' . $this->scopeLabel() . ' scoring below ' . $n . '% ' . ($subject ? "in {$subject}" : 'in any subject') . ': ' . count($rows) . ' of ' . count($students);
        } else {
            $rows = array_values(array_filter($students, fn ($s) => $score($s) > $n));
            usort($rows, fn ($x, $y) => $score($y) <=> $score($x));
            $lines = array_map(fn ($s) => "• {$s['name']} ({$s['class']}) — " . $score($s) . '%', $rows);
            $head = 'Students in ' . $this->scopeLabel() . " scoring above {$n}% " . ($subject ? "in {$subject}" : 'on average') . ': ' . count($rows) . ' of ' . count($students);
        }

        return $this->result(
            $head . "\n" . ($lines ? $this->bullets($lines, 15) : 'Nobody. 🎉') . "\n\nSubject averages for {$this->scopeLabel()}:\n{$subjectLines}",
            'marks',
            $marksState + ($cmp['op'] === 'lt' ? ['marksThreshold' => (int) round($n)] : []),
        );
    }

    private function risk(string $q): ?array
    {
        if (! preg_match('/risk|struggl|need(?:s|ing)? (?:help|support|attention)|drop ?out|concern|worr/', $q)) {
            return null;
        }
        $rows = array_values(array_filter($this->engine->students->roster($this->engine->data()), fn ($r) => $r['signal_type'] !== 'ok' && $this->inScope($r['class'])));
        $lines = array_map(fn ($r) => "• {$r['name']} ({$r['class']}) — risk {$r['risk_score']}: " . implode(', ', $r['reasons']), $rows);

        return $this->result('Students needing attention in ' . $this->scopeLabel() . ': ' . count($rows) . "\n" . ($lines ? $this->bullets($lines) : 'Nobody flagged. 🎉') . "\n\nAsk “tell me about <name>” for a full plan.", 'risk');
    }

    private function syllabus(string $q): ?array
    {
        if (! preg_match('/syllabus|portion|chapter|curriculum|lesson|\bunits?\b|behind|coverage|completed/', $q)) {
            return null;
        }
        $rows = array_values(array_filter($this->engine->data()->syllabus(), fn ($r) => $this->inScope($r['class']) && (! $this->e['subject'] || $r['subject'] === $this->e['subject'])));
        if (preg_match('/behind|lag|slow|delay|pending/', $q)) {
            $rows = array_values(array_filter($rows, fn ($r) => $r['status'] === 'Needs review'));
        }
        usort($rows, fn ($x, $y) => ($y['expected'] - $y['completion']) <=> ($x['expected'] - $x['completion']));
        $lines = array_map(fn ($r) => "• {$r['class']} {$r['subject']}: {$r['completion']}% done (expected {$r['expected']}%) · {$r['chapter']}", $rows);
        $state = $this->sectionState();

        return $this->result('Syllabus in ' . $this->scopeLabel() . ($this->e['subject'] ? " · {$this->e['subject']}" : '') . ":\n" . ($lines ? $this->bullets($lines) : 'Everything is on pace. 🎉'), 'syllabus', isset($state['section']) && $state['section'] !== 'all' ? ['syllabusClass' => $state['class'], 'syllabusSection' => $state['section']] : []);
    }

    private function exams(string $q): ?array
    {
        if (! preg_match('/exam|test|assessment|date ?sheet|half[- ]?yearly|annual|periodic|marks entry|result|moderat|publish/', $q)) {
            return null;
        }
        $data = $this->engine->data();
        $exams = $data->examinations();

        if (preg_match('/marks entry|not entered|pending|overdue|late/', $q)) {
            $p = array_values(array_filter($exams, fn ($e) => $e['status'] === 'Marks entry' && $this->inScope($e['class'])));
            $by = [];
            foreach ($p as $e) {
                $by[$e['teacher']][] = "{$e['class']} {$e['subject']}";
            }

            return $this->result('Marks entry pending: ' . count($p) . " papers\n" . implode("\n", array_map(fn ($t, $papers) => "• {$t}: " . implode(', ', $papers), array_keys($by), $by)), 'examinations', ['examinationView' => 'marks']);
        }

        if (preg_match('/result|moderat|publish|sign/', $q)) {
            $ready = array_filter($exams, fn ($e) => $e['status'] === 'Moderation' && $this->inScope($e['class']));
            $pub = array_filter($exams, fn ($e) => $e['status'] === 'Published' && $this->inScope($e['class']));

            return $this->result('Waiting for your sign-off: ' . count($ready) . "\n" . implode("\n", array_map(fn ($e) => "• {$e['exam']} · {$e['class']} {$e['subject']}", $ready)) . "\nPublished so far: " . count($pub) . ' papers.', 'examinations', ['examinationView' => 'results']);
        }

        $next = $this->engine->exams->nextExam($data);
        if ($this->e['section']) {
            $name = $next['exam'] ?? $exams[0]['exam'];
            $rows = array_filter($exams, fn ($e) => $e['class'] === $this->e['section'] && $e['exam'] === $name);
            [$c, $s] = explode('-', $this->e['section']);

            return $this->result("{$name} — {$this->e['section']} date sheet:\n" . implode("\n", array_map(fn ($e) => "• {$e['date']} {$e['start']}–{$e['end']} · {$e['subject']} · {$e['room']} · {$e['status']}", $rows)), 'examinations', ['examinationClass' => $c, 'examinationSection' => $s, 'examinationName' => $name, 'examinationView' => 'schedule']);
        }

        return $this->result($next ? "Next exam: {$next['exam']} starts {$next['date']} (" . ($next['days'] === 0 ? 'today' : "in {$next['days']} days") . ').' . "\nAsk “date sheet for 8A” for a class schedule." : 'No upcoming exams this year.', 'examinations');
    }

    private function counts(string $q): ?array
    {
        if (! preg_match('/how many|count|number of|total|strength/', $q)) {
            return null;
        }
        $data = $this->engine->data();
        if (preg_match('/teacher|staff|faculty/', $q)) {
            $staff = $data->staff();

            return $this->result(count($staff) . ' staff · ' . count(array_filter($staff, fn ($s) => str_contains($s['designation'], 'Teacher'))) . ' teachers · ' . count(array_filter($staff, fn ($s) => $s['status'] === 'On leave')) . ' on leave today.', 'staff');
        }
        $students = array_filter($data->students(), fn ($s) => $this->inScope($s['class']));
        $by = array_count_values(array_column($students, 'class'));
        ksort($by);

        return $this->result(count($students) . ' students in ' . $this->scopeLabel() . ":\n" . implode("\n", array_map(fn ($c, $n) => "• {$c}: {$n}", array_keys($by), $by)), 'students');
    }

    private function staffList(string $q): ?array
    {
        if (! preg_match('/\b(staff|teachers?|faculty)\b/', $q)) {
            return null;
        }
        $staff = array_filter($this->engine->data()->staff(), fn ($t) => ! $this->e['subject'] || $t['department'] === $this->e['subject']);

        return $this->result('Staff' . ($this->e['subject'] ? " · {$this->e['subject']}" : '') . ":\n" . implode("\n", array_map(fn ($t) => "• {$t['name']} — {$t['designation']} · {$t['classes']} · {$t['status']}", $staff)), 'staff');
    }
}
