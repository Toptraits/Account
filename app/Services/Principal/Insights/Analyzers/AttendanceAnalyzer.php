<?php

namespace App\Services\Principal\Insights\Analyzers;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzer;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\Insight;

/**
 * Attendance defaulters, absence streaks, sudden drops in a section and good news.
 */
class AttendanceAnalyzer implements Analyzer
{
    private array $memo = [];

    public function __construct(
        private int $defaulterBelow = 75,
        private int $streakDays = 3,
        private int $sectionDropPoints = 6,
    ) {
    }

    public function summary(SchoolDataSource $data): array
    {
        return $this->memo[spl_object_id($data)] ??= $this->build($data);
    }

    private function build(SchoolDataSource $data): array
    {
        $history = $data->attendanceHistory(30);
        $today = $data->now()->toDateString();
        $students = array_column($data->students(), null, 'id');
        $days = array_keys(reset($history) ?: []);
        $registerTaken = in_array($today, $days, true);

        $perStudent = [];
        $sectionDaily = []; // section => date => [present, total]

        foreach ($history as $id => $marks) {
            $student = $students[$id];
            $present = count(array_filter($marks, fn ($m) => $m !== 'A'));
            $streak = 0;
            foreach (array_reverse($marks) as $mark) {
                if ($mark !== 'A') {
                    break;
                }
                $streak++;
            }

            $perStudent[$id] = [
                'id' => $id,
                'name' => $student['name'],
                'class' => $student['class'],
                'phone' => $student['parent_phone'],
                'pct' => round($present / max(1, count($marks)) * 100, 1),
                'absences' => count($marks) - $present,
                'lates' => count(array_filter($marks, fn ($m) => $m === 'L')),
                'streak' => $streak,
                'today' => $registerTaken ? $marks[$today] : null,
            ];

            foreach ($marks as $date => $mark) {
                $sectionDaily[$student['class']][$date][0] = ($sectionDaily[$student['class']][$date][0] ?? 0) + ($mark !== 'A' ? 1 : 0);
                $sectionDaily[$student['class']][$date][1] = ($sectionDaily[$student['class']][$date][1] ?? 0) + 1;
            }
        }

        // Section today vs its own usual level.
        $sections = [];
        foreach ($sectionDaily as $section => $byDate) {
            $latestDate = array_key_last($byDate);
            [$p, $t] = $byDate[$latestDate];
            $previous = array_slice($byDate, 0, -1, true);
            $usual = $previous === [] ? 100 : array_sum(array_map(fn ($d) => $d[0] / $d[1] * 100, $previous)) / count($previous);

            $sections[$section] = [
                'class' => $section,
                'strength' => $t,
                'present' => $p,
                'absent' => $t - $p,
                'rate' => round($p / $t * 100, 1),
                'usual' => round($usual, 1),
                'drop' => round($usual - $p / $t * 100, 1),
            ];
        }
        ksort($sections, SORT_NATURAL);

        // School-wide daily rate for "best day" and trend sparkline.
        $daily = [];
        foreach ($days as $date) {
            $p = $t = 0;
            foreach ($sectionDaily as $byDate) {
                $p += $byDate[$date][0];
                $t += $byDate[$date][1];
            }
            $daily[$date] = round($p / max(1, $t) * 100, 1);
        }

        $latest = end($daily) ?: 0;
        $todayRows = array_filter($perStudent, fn ($s) => $s['today'] !== null);

        $defaulters = array_values(array_filter($perStudent, fn ($s) => $s['pct'] < $this->defaulterBelow));
        usort($defaulters, fn ($a, $b) => $a['pct'] <=> $b['pct']);

        $streaks = array_values(array_filter($perStudent, fn ($s) => $s['streak'] >= $this->streakDays));
        usort($streaks, fn ($a, $b) => $b['streak'] <=> $a['streak']);

        return [
            'register_taken' => $registerTaken,
            'latest_date' => array_key_last($daily),
            'latest_rate' => $latest,
            'best_rate' => $daily === [] ? 0 : max($daily),
            'daily' => $daily,
            'present_today' => count(array_filter($todayRows, fn ($s) => $s['today'] !== 'A')),
            'absent_today' => count(array_filter($todayRows, fn ($s) => $s['today'] === 'A')),
            'late_today' => count(array_filter($todayRows, fn ($s) => $s['today'] === 'L')),
            'total' => count($perStudent),
            'students' => $perStudent,
            'sections' => array_values($sections),
            'defaulters' => $defaulters,
            'streaks' => $streaks,
        ];
    }

    public function analyze(SchoolDataSource $data): array
    {
        $s = $this->summary($data);
        $insights = [];

        if ($s['streaks'] !== []) {
            $n = count($s['streaks']);
            $insights[] = new Insight(
                key: 'attendance.streaks',
                category: 'attendance',
                severity: 'critical',
                score: min(95, 68 + $n * 4),
                title: Format::plural($n, 'student') . " absent {$this->streakDays}+ days in a row",
                summary: 'Long unexplained absences are the earliest warning sign of a health, family or dropout problem. Call these families today.',
                briefing: Format::plural($n, 'student') . ' ' . Format::verb($n, 'has', 'have') . " been absent {$this->streakDays} or more days running — "
                    . ($n > 2 ? implode(', ', array_map(fn ($x) => $x['name'], array_slice($s['streaks'], 0, 2))) . ' and ' . ($n - 2) . ' more' : Format::list(array_map(fn ($x) => $x['name'], $s['streaks']))) . '.',
                metric: (string) $n,
                value: (float) $n,
                items: array_map(fn ($x) => [
                    'id' => $x['id'],
                    'label' => "{$x['name']} · {$x['class']}",
                    'meta' => "Parent {$x['phone']} · {$x['pct']}% this month",
                    'badge' => "{$x['streak']} days",
                    'tone' => 'risk',
                ], array_slice($s['streaks'], 0, 6)),
                actionLabel: 'Call list',
                actionTab: 'attendance',
                actionParams: ['filter' => 'streaks'],
            );
        }

        if ($s['defaulters'] !== []) {
            $n = count($s['defaulters']);
            $insights[] = new Insight(
                key: 'attendance.defaulters',
                category: 'attendance',
                severity: $n >= 6 ? 'critical' : 'warning',
                score: min(90, 48 + $n * 2),
                title: Format::plural($n, 'student') . " below {$this->defaulterBelow}% attendance",
                summary: 'Based on the last 30 school days. Lowest first — these students risk not meeting the exam eligibility rule.',
                briefing: Format::plural($n, 'student') . ' ' . Format::verb($n, 'is', 'are') . " below the {$this->defaulterBelow}% attendance line.",
                metric: (string) $n,
                value: (float) $n,
                items: array_map(fn ($x) => [
                    'id' => $x['id'],
                    'label' => "{$x['name']} · {$x['class']}",
                    'meta' => "{$x['absences']} absences · {$x['lates']} late",
                    'badge' => "{$x['pct']}%",
                    'tone' => $x['pct'] < 65 ? 'risk' : 'warn',
                ], array_slice($s['defaulters'], 0, 6)),
                actionLabel: 'Open attendance defaulters',
                actionTab: 'attendance',
                actionParams: ['filter' => 'defaulters'],
            );
        }

        $drops = array_values(array_filter($s['sections'], fn ($x) => $x['drop'] >= $this->sectionDropPoints));
        usort($drops, fn ($a, $b) => $b['drop'] <=> $a['drop']);
        if ($drops !== []) {
            $worst = $drops[0];
            $insights[] = new Insight(
                key: 'attendance.section-drop',
                category: 'attendance',
                severity: 'warning',
                score: (int) min(85, 42 + $worst['drop'] * 1.5),
                title: "{$worst['class']} attendance is {$worst['rate']}% — {$worst['drop']} pts below usual",
                summary: count($drops) > 1
                    ? 'Also lower than usual: ' . Format::list(array_map(fn ($x) => "{$x['class']} ({$x['rate']}%)", array_slice($drops, 1, 3))) . '. Check for illness, an event or a timetable issue.'
                    : 'A sudden dip in one section often means illness spreading or an issue in that class. Worth a quick word with the class teacher.',
                briefing: "{$worst['class']} attendance dropped to {$worst['rate']}% (usually {$worst['usual']}%).",
                metric: $worst['rate'] . '%',
                value: $worst['drop'],
                items: array_map(fn ($x) => [
                    'label' => "Class {$x['class']}",
                    'meta' => "{$x['absent']} absent of {$x['strength']} · usually {$x['usual']}%",
                    'badge' => "−{$x['drop']} pts",
                    'tone' => 'warn',
                ], $drops),
                actionLabel: 'View sections',
                actionTab: 'attendance',
            );
        }

        if ($s['latest_rate'] >= 95 || ($s['latest_rate'] >= $s['best_rate'] && $s['latest_rate'] >= 90)) {
            $best = $s['latest_rate'] >= $s['best_rate'];
            $insights[] = new Insight(
                key: 'attendance.great-day',
                category: 'attendance',
                severity: 'positive',
                score: $best ? 22 : 14,
                title: "Attendance {$s['latest_rate']}%" . ($best ? ' — best in 30 school days' : ' — a strong day'),
                summary: 'Worth a shout-out in assembly or to staff.',
                briefing: "Attendance is {$s['latest_rate']}%" . ($best ? ', the best in the last 30 school days.' : '.'),
                metric: $s['latest_rate'] . '%',
                value: $s['latest_rate'],
                betterWhen: 'higher',
            );
        }

        if (! $s['register_taken']) {
            $insights[] = new Insight(
                key: 'attendance.register-pending',
                category: 'attendance',
                severity: 'info',
                score: 30,
                title: "Today's register is not complete yet",
                summary: 'Figures below are from the last school day. They refresh automatically after 09:30.',
                actionLabel: 'Attendance',
                actionTab: 'attendance',
            );
        }

        return $insights;
    }
}
