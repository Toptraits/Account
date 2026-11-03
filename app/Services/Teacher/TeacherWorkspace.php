<?php

namespace App\Services\Teacher;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\InsightEngine;
use Carbon\CarbonImmutable;

/**
 * The teacher's "brain": the same school data as the Principal dashboard,
 * but only for this teacher — their periods, their sections, their students.
 */
class TeacherWorkspace
{
    /** Demo: which section each class teacher looks after. Backend: read from the class-teacher table. */
    private const CLASS_TEACHER_OF = ['STF001' => '8-A', 'STF004' => '7-B', 'STF012' => '9-A'];

    private array $teacher;

    private array $memo = [];

    public function __construct(private InsightEngine $engine, ?string $userName = null, ?string $fallbackTeacherId = 'STF001')
    {
        $staff = $this->data()->staff();
        $match = null;
        if ($userName) {
            $needle = mb_strtolower(trim(preg_replace('/^(mr|mrs|ms|miss|dr)\.?\s+/i', '', $userName)));
            foreach ($staff as $s) {
                if (mb_strtolower($s['name']) === $needle) {
                    $match = $s;
                }
            }
        }
        $match ??= collect($staff)->firstWhere('id', $fallbackTeacherId) ?? $staff[0];
        $this->teacher = $match;
    }

    public function data(): SchoolDataSource
    {
        return $this->engine->data();
    }

    public function engine(): InsightEngine
    {
        return $this->engine;
    }

    // =============================================================== identity

    public function teacher(): array
    {
        return $this->teacher;
    }

    public function subject(): string
    {
        return $this->teacher['department'];
    }

    /** @return list<string> sections this teacher teaches, e.g. ['6-A','7-B','8-A','9-B'] */
    public function sections(): array
    {
        preg_match_all('/\b[6-9]-[AB]\b/', $this->teacher['classes'], $m);

        return $m[0];
    }

    public function classTeacherOf(): ?string
    {
        $section = self::CLASS_TEACHER_OF[$this->teacher['id']] ?? null;

        return $section ?? (str_contains($this->teacher['designation'], 'Class Teacher') ? ($this->sections()[0] ?? null) : null);
    }

    // ================================================================== my day

    /** This teacher's periods for a date, plus cover duties and the live "now / next". */
    public function day(?string $date = null): array
    {
        $now = $this->data()->now();
        $date ??= $now->toDateString();
        $isToday = $date === $now->toDateString();
        $all = $this->data()->timetableForDate($date);

        $mine = array_values(array_filter($all, fn ($p) => $p['teacher_id'] === $this->teacher['id']));
        $cover = [];
        if ($isToday) {
            foreach ($this->engine->substitution->gaps($this->data())['gaps'] as $g) {
                if ($g['suggested_id'] === $this->teacher['id']) {
                    $cover[] = $g + ['is_cover' => true];
                }
            }
        }
        $periods = array_merge($mine, $cover);
        usort($periods, fn ($a, $b) => strcmp($a['start'], $b['start']));

        $current = $isToday ? collect($periods)->first(fn ($p) => $p['status'] === 'In progress') : null;
        $next = $isToday ? collect($periods)->first(fn ($p) => $p['status'] === 'Upcoming') : null;

        return [
            'date' => $date,
            'is_today' => $isToday,
            'periods' => $periods,
            'current' => $current,
            'next' => $next,
            'done' => count(array_filter($periods, fn ($p) => $p['status'] === 'Completed')),
            'cover' => $cover,
            'on_leave' => $this->teacher['status'] === 'On leave' && $isToday,
        ];
    }

    /** Monday–Saturday grid of this teacher's periods. */
    public function week(string $date): array
    {
        $start = CarbonImmutable::parse($date, $this->data()->now()->getTimezone())->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $week = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $start->addDays($i)->toDateString();
            $periods = array_column(array_filter($this->data()->timetableForDate($d), fn ($p) => $p['teacher_id'] === $this->teacher['id']), null, 'start');
            if ($d === $this->data()->now()->toDateString()) {
                foreach ($this->day($d)['cover'] as $c) {
                    $periods[$c['start']] = $c; // today's cover duty shows in the week too
                }
            }
            $week[$d] = ['label' => $start->addDays($i)->format('D d M'), 'periods' => $periods];
        }

        return $week;
    }

    // ========================================================= needs attention

    /**
     * Today's to-do list, built from live data and ranked by urgency.
     *
     * @param  array<string, array>  $registers  attendance already submitted today, keyed by section
     */
    public function attention(array $registers = []): array
    {
        $data = $this->data();
        $now = $data->now();
        $items = [];
        $att = $this->engine->attendance->summary($data);
        $subject = $this->subject();
        $ct = $this->classTeacherOf();
        $day = $this->day();

        if ($day['on_leave']) {
            $items[] = ['level' => 'info', 'title' => 'You are marked on leave today', 'detail' => 'Your periods have been given to substitutes.', 'action' => 'My timetable', 'tab' => 'timetable', 'rank' => 99];
        }

        // 1. Attendance register for the class-teacher section
        if ($ct && ! $now->isSunday()) {
            if (isset($registers[$ct])) {
                $r = $registers[$ct];
                $absent = array_keys(array_filter($r['marks'], fn ($m) => $m === 'absent'));
                $names = array_map(fn ($id) => $this->studentName($id), $absent);
                $items[] = $absent
                    ? ['level' => 'warn', 'title' => "{$ct} register submitted · " . count($absent) . ' absent', 'detail' => 'Absent: ' . Format::list($names) . '. Inform their parents.', 'action' => 'Notify parents', 'tab' => 'attendance', 'rank' => 60]
                    : ['level' => 'ok', 'title' => "{$ct} register submitted · everyone present", 'detail' => 'Submitted at ' . date('H:i', $r['at']), 'action' => 'View register', 'tab' => 'attendance', 'rank' => 5];
            } elseif ($now->format('H:i') >= '08:30' && $now->format('H:i') <= '15:00') {
                $items[] = ['level' => 'risk', 'title' => "{$ct} attendance not submitted yet", 'detail' => $now->format('H:i') < '09:30' ? 'Take it before the first period ends (09:45).' : 'It is ' . $now->format('H:i') . ' — the office is waiting for it.', 'action' => 'Take attendance', 'tab' => 'attendance', 'rank' => 95];
            }
        }

        // 2. Long absences in my sections
        $streaks = array_values(array_filter($att['streaks'], fn ($s) => in_array($s['class'], $this->sections(), true)));
        foreach (array_slice($streaks, 0, 2) as $s) {
            $mineCt = $s['class'] === $ct;
            $items[] = ['level' => 'risk', 'title' => "{$s['name']} ({$s['class']}) absent {$s['streak']} days in a row", 'detail' => $mineCt ? "You are the class teacher — call the parent today ({$s['phone']})." : 'Check with the class teacher and plan catch-up work.', 'action' => 'View student', 'tab' => 'progress', 'section' => $s['class'], 'rank' => $mineCt ? 90 : 70];
        }

        // 3. Cover duty today
        foreach ($day['cover'] as $c) {
            $items[] = ['level' => 'warn', 'title' => "Cover duty: {$c['class']} {$c['subject']} at {$c['start']}", 'detail' => "{$c['absent_teacher']} is on leave · {$c['room']}" . ($c['status'] === 'In progress' ? ' · happening now' : ''), 'action' => 'My timetable', 'tab' => 'timetable', 'rank' => $c['status'] === 'In progress' ? 92 : 80];
        }

        // 4. Marks entry waiting for me
        $pending = array_values(array_filter($data->examinations(), fn ($e) => $e['teacher_id'] === $this->teacher['id'] && $e['status'] === 'Marks entry'));
        if ($pending) {
            $days = (int) max(array_map(fn ($e) => CarbonImmutable::parse($e['date'], $now->getTimezone())->diffInDays($now->startOfDay()), $pending));
            $items[] = ['level' => $days > 3 ? 'risk' : 'warn', 'title' => Format::plural(count($pending), 'paper') . ' waiting for your marks entry', 'detail' => $pending[0]['exam'] . ' · ' . implode(', ', array_map(fn ($e) => "{$e['class']} {$e['subject']}", $pending)) . " · oldest {$days} days", 'action' => 'Enter marks', 'tab' => 'scan', 'rank' => 75 + min(15, $days)];
        }

        // 5. Homework due tomorrow with missing work
        foreach ($this->homework() as $h) {
            if ($h['due_in'] === 1 && $h['missing']) {
                $items[] = ['level' => 'warn', 'title' => count($h['missing']) . " of {$h['total']} have not submitted {$h['title']} ({$h['class']})", 'detail' => 'Due tomorrow · ' . implode(', ', array_slice(array_column($h['missing'], 'name'), 0, 3)) . (count($h['missing']) > 3 ? '…' : ''), 'action' => 'Open homework', 'tab' => 'homework', 'rank' => 55];
            }
        }

        // 6. Syllabus behind before the next exam (my subject only)
        $next = $this->engine->exams->nextExam($data);
        foreach ($data->syllabus() as $s) {
            if ($s['subject'] === $subject && in_array($s['class'], $this->sections(), true) && $s['status'] === 'Needs review') {
                $items[] = ['level' => 'warn', 'title' => "{$s['class']} {$subject} is behind: {$s['completion']}% done, {$s['expected']}% expected", 'detail' => ($next ? "{$next['exam']} in {$next['days']} days · " : '') . "now on {$s['chapter']}", 'action' => 'Plan catch-up', 'tab' => 'classes', 'rank' => 50];
            }
        }

        // 7. Weak students in my subject → one item, pointing at the section with the most
        $weakBySection = [];
        foreach ($this->sections() as $sec) {
            $weak = array_filter($data->students(), fn ($st) => $st['class'] === $sec && ($st['subject_marks'][$subject] ?? 100) < 40);
            if ($weak) {
                $weakBySection[$sec] = array_column(array_values($weak), 'name');
            }
        }
        if ($weakBySection) {
            uasort($weakBySection, fn ($a, $b) => count($b) <=> count($a));
            $total = array_sum(array_map('count', $weakBySection));
            $items[] = [
                'level' => 'info',
                'title' => Format::plural($total, 'student') . " below 40% in {$subject}" . (count($weakBySection) > 1 ? ' across ' . Format::plural(count($weakBySection), 'class', 'classes') : ' in ' . array_key_first($weakBySection)),
                'detail' => implode(' · ', array_map(fn ($sec, $names) => "{$sec}: " . implode(', ', $names), array_keys($weakBySection), $weakBySection)) . ' — a support worksheet can help.',
                'action' => 'Make worksheet', 'tab' => 'worksheet', 'section' => array_key_first($weakBySection), 'rank' => 40,
            ];
        }

        usort($items, fn ($a, $b) => $b['rank'] <=> $a['rank']);

        return $items;
    }

    // ======================================================= prepare for class

    /**
     * Everything to know before walking into a period: what to teach, who missed the last
     * class, whose homework is missing, and who needs an easy question to stay involved.
     */
    public function prep(?array $period): ?array
    {
        if (! $period) {
            return null;
        }
        $data = $this->data();
        $sec = $period['class'];
        $subject = $period['subject'];
        $isCover = ! empty($period['is_cover']);
        $students = array_values(array_filter($data->students(), fn ($s) => $s['class'] === $sec));
        $syl = collect($data->syllabus())->first(fn ($s) => $s['class'] === $sec && $s['subject'] === $subject);
        $chapter = $syl ? preg_replace('/^Unit \d+ · /', '', $syl['chapter']) : $subject;

        // Absent on the last school day before today
        $history = $data->attendanceHistory(30);
        $today = $data->now()->toDateString();
        $days = array_keys(reset($history) ?: []);
        $prevDays = array_values(array_filter($days, fn ($d) => $d < $today));
        $lastDay = end($prevDays) ?: null;
        $missedLast = $lastDay ? array_values(array_filter($students, fn ($s) => ($history[$s['id']][$lastDay] ?? 'P') === 'A')) : [];

        $weak = array_values(array_filter($students, fn ($s) => ($s['subject_marks'][$subject] ?? 100) < 40));
        $missedIds = array_column($missedLast, 'id');
        $strong = array_values(array_filter($students, fn ($s) => ($s['subject_marks'][$subject] ?? 0) >= 85 && ! in_array($s['id'], $missedIds, true)));
        $homework = $isCover ? [] : array_values(array_filter($this->homework(), fn ($h) => $h['class'] === $sec && $h['due_in'] <= 1 && $h['missing']));

        $tips = [];
        if ($isCover) {
            $tips[] = "Cover lesson for {$period['absent_teacher']}: continue “{$chapter}” with textbook exercises or a short revision quiz.";
        } elseif ($syl && $syl['status'] === 'Needs review') {
            $gap = $syl['expected'] - $syl['completion'];
            $tips[] = "The class is {$gap}% behind in {$subject}. Teach the core idea of “{$chapter}” and set the practice part as homework.";
        } elseif ($syl) {
            $tips[] = "Continue “{$chapter}” — {$syl['completion']}% of the syllabus done, {$syl['expected']}% expected by today.";
        }
        if ($missedLast) {
            $tips[] = 'Start with a 3-minute recap for ' . Format::list(array_column($missedLast, 'name')) . ' (absent ' . date('D', strtotime($lastDay)) . ').';
        }
        if ($weak && ! $isCover) {
            $tips[] = 'Ask ' . Format::list(array_column($weak, 'name')) . ' an easy question early to keep them involved.';
        }
        if ($strong && ! $isCover) {
            $tips[] = 'Give ' . Format::list(array_column(array_slice($strong, 0, 2), 'name')) . ' a challenge question if they finish early.';
        }
        foreach ($homework as $h) {
            $tips[] = 'Collect “' . $h['title'] . '”: still missing from ' . Format::list(array_column($h['missing'], 'name')) . '.';
        }

        return [
            'period' => $period,
            'is_cover' => $isCover,
            'chapter' => $chapter,
            'syllabus' => $syl,
            'missed_last' => array_column($missedLast, 'name'),
            'weak' => array_column($weak, 'name'),
            'homework_missing' => array_sum(array_map(fn ($h) => count($h['missing']), $homework)),
            'tips' => $tips,
        ];
    }

    // ================================================================ classes

    public function classCards(): array
    {
        $data = $this->data();
        $subject = $this->subject();
        $att = $this->engine->attendance->summary($data)['students'];
        $today = collect($this->day()['periods']);
        $cards = [];

        foreach ($this->sections() as $sec) {
            $students = array_values(array_filter($data->students(), fn ($s) => $s['class'] === $sec));
            $marks = array_map(fn ($s) => $s['subject_marks'][$subject] ?? null, $students);
            $marks = array_filter($marks, fn ($m) => $m !== null);
            $syl = collect($data->syllabus())->first(fn ($s) => $s['class'] === $sec && $s['subject'] === $subject);
            $secAtt = array_filter($att, fn ($a) => $a['class'] === $sec);
            $next = $today->first(fn ($p) => $p['class'] === $sec && in_array($p['status'], ['Upcoming', 'In progress'], true));

            $cards[] = [
                'section' => $sec,
                'subject' => $subject,
                'is_class_teacher' => $sec === $this->classTeacherOf(),
                'students' => count($students),
                'average' => $marks ? round(array_sum($marks) / count($marks)) : null,
                'below40' => count(array_filter($marks, fn ($m) => $m < 40)),
                'attendance' => $secAtt ? round(array_sum(array_column($secAtt, 'pct')) / count($secAtt), 1) : null,
                'syllabus' => $syl,
                'next' => $next ? ($next['status'] === 'In progress' ? 'Now · ' : 'Today ') . $next['time'] : 'No period today',
            ];
        }

        return $cards;
    }

    // ============================================================= attendance

    /** Register with smart hints from the last 30 school days. */
    public function register(string $section): array
    {
        $att = $this->engine->attendance->summary($this->data())['students'];
        $history = $this->data()->attendanceHistory(30);
        $rows = [];

        foreach ($this->data()->students() as $s) {
            if ($s['class'] !== $section) {
                continue;
            }
            $a = $att[$s['id']];
            $last5 = array_slice($history[$s['id']] ?? [], -5, 5, true);
            $hint = match (true) {
                $a['streak'] >= 2 => ['risk', "Absent {$a['streak']} days running — call home if absent again"],
                $a['pct'] < 75 => ['warn', "Only {$a['pct']}% this month"],
                $a['lates'] >= 3 => ['warn', "{$a['lates']} late arrivals this month"],
                default => null,
            };
            $rows[] = ['id' => $s['id'], 'name' => $s['name'], 'pct' => $a['pct'], 'last5' => $last5, 'hint' => $hint, 'phone' => $s['parent_phone']];
        }

        return $rows;
    }

    // =============================================================== progress

    public function progress(string $section): array
    {
        $subject = $this->subject();
        $byId = array_column($this->data()->students(), null, 'id');
        $rows = array_values(array_filter($this->engine->students->roster($this->data()), fn ($r) => $r['class'] === $section));

        return array_map(function ($r) use ($subject, $byId) {
            $marks = $byId[$r['id']]['subject_marks'];
            $mine = $marks[$subject] ?? null;
            $others = array_diff_key($marks, [$subject => true]);
            $othersAvg = $others ? round(array_sum($others) / count($others)) : null;
            $note = match (true) {
                $mine !== null && $mine < 40 => "Needs support in {$subject} — add to the support worksheet group",
                $mine !== null && $othersAvg !== null && $mine < $othersAvg - 12 => "Weaker in {$subject} than in other subjects ({$othersAvg}%) — check understanding",
                $mine !== null && $mine >= 85 => 'Strong — give challenge questions',
                default => $r['signal_type'] === 'ok' ? 'On track' : 'Follow the suggested plan',
            };

            return $r + ['my_subject' => $mine, 'others_avg' => $othersAvg, 'teacher_note' => $note];
        }, $rows);
    }

    // =============================================================== homework

    /** Demo homework for my sections. Backend: replace with the homework table. */
    public function homework(): array
    {
        return $this->memo['homework'] ??= $this->buildHomework();
    }

    private function buildHomework(): array
    {
        $data = $this->data();
        $now = $data->now()->startOfDay();
        $subject = $this->subject();
        $att = $this->engine->attendance->summary($data)['students'];
        $list = [];

        foreach ($this->sections() as $i => $sec) {
            $syl = collect($data->syllabus())->first(fn ($s) => $s['class'] === $sec && $s['subject'] === $subject);
            $chapter = $syl ? preg_replace('/^Unit \d+ · /', '', $syl['chapter']) : $subject;
            foreach ([['Practice sheet', 1 + ($i % 2)], ['Revision questions', 3 + $i]] as $k => [$kind, $dueIn]) {
                $due = $now->addDays($dueIn);
                if ($due->isSunday()) {
                    $due = $due->addDay();
                    $dueIn++;
                }
                $set = $now->subDays(3 - $k);
                $students = array_filter($data->students(), fn ($s) => $s['class'] === $sec);
                $missing = [];
                foreach ($students as $s) {
                    // Submission chance grows each day after it was set; regular attenders submit more often.
                    $daysOpen = max(1, (int) $set->diffInDays($now));
                    $chance = min(0.95, 0.2 + $daysOpen * 0.15 + ($att[$s['id']]['pct'] - 75) / 100);
                    $roll = hexdec(substr(md5($s['id'] . $kind . $sec . $set->toDateString()), 0, 6)) / 0xFFFFFF;
                    if ($roll > $chance) {
                        $missing[] = ['id' => $s['id'], 'name' => $s['name'], 'phone' => $s['parent_phone']];
                    }
                }
                $list[] = [
                    'id' => md5($sec . $kind . $set->toDateString()),
                    'title' => "{$chapter} · {$kind}",
                    'class' => $sec,
                    'subject' => $subject,
                    'due' => $due->format('D d M'),
                    'due_in' => $dueIn,
                    'total' => count($students),
                    'submitted' => count($students) - count($missing),
                    'missing' => $missing,
                ];
            }
        }
        usort($list, fn ($a, $b) => $a['due_in'] <=> $b['due_in']);

        return $list;
    }

    // ============================================================== worksheet

    /** What to make a worksheet on for a section, and who goes in which group. */
    public function worksheetPlan(string $section): array
    {
        $subject = $this->subject();
        $syl = collect($this->data()->syllabus())->first(fn ($s) => $s['class'] === $section && $s['subject'] === $subject);
        $students = array_values(array_filter($this->data()->students(), fn ($s) => $s['class'] === $section));
        $groups = ['Support' => [], 'Core' => [], 'Challenge' => []];
        foreach ($students as $s) {
            $m = $s['subject_marks'][$subject] ?? 60;
            $groups[$m < 50 ? 'Support' : ($m >= 80 ? 'Challenge' : 'Core')][] = $s['name'] . " ({$m}%)";
        }

        return [
            'subject' => $subject,
            'topic' => $syl ? preg_replace('/^Unit \d+ · /', '', $syl['chapter']) : $subject,
            'groups' => $groups,
        ];
    }

    /** Three levels of questions on one topic. Template-based; can be swapped for the AI service later. */
    public function worksheet(string $subject, string $topic, string $section): array
    {
        $t = trim($topic) !== '' ? trim($topic) : $subject;
        $grade = (int) $section;
        $maths = $subject === 'Mathematics';

        return [
            'Support' => [
                "Write the meaning of “{$t}” in your own words with one example.",
                $maths ? "Solve two simple sums on {$t} (show each step)." : "List three key words from {$t} and explain each in one line.",
                "Fill in the blanks: three short sentences about {$t} (teacher to complete from the textbook).",
            ],
            'Core' => [
                $maths ? "Solve a Class {$grade} textbook-level word problem on {$t}." : "Explain {$t} with a labelled diagram or a short paragraph.",
                "Find and correct the mistake in a worked example on {$t}.",
                "Give a real-life situation where {$t} is used and explain how.",
            ],
            'Challenge' => [
                $maths ? "Create your own word problem on {$t}, solve it, and check the answer another way." : "Compare {$t} with a related idea from an earlier chapter.",
                "Why might a student get {$t} wrong? Write a tip to help them.",
                "Apply {$t} to an unfamiliar situation and justify each step.",
            ],
        ];
    }

    // ================================================================ helpers

    public function marksEntryQueue(): array
    {
        return array_values(array_filter($this->data()->examinations(), fn ($e) => $e['teacher_id'] === $this->teacher['id'] && in_array($e['status'], ['Marks entry', 'Scheduled', 'Moderation'], true) && $e['date'] <= $this->data()->now()->addDays(30)->toDateString()));
    }

    private function studentName(string $id): string
    {
        return collect($this->data()->students())->firstWhere('id', $id)['name'] ?? $id;
    }
}
