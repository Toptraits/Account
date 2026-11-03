<?php

namespace App\Services\Principal\Insights\Analyzers;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzer;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\Insight;
use Carbon\CarbonImmutable;

/**
 * Joins three modules: exam calendar + syllabus progress + marks-entry status.
 */
class ExamReadinessAnalyzer implements Analyzer
{
    public function __construct(
        private int $lookAheadDays = 30,
        private int $marksEntryDueDays = 3,
    ) {
    }

    public function nextExam(SchoolDataSource $data): ?array
    {
        $today = $data->now()->startOfDay();
        // First paper of each exam; the "next exam" is the one that has not started yet.
        $starts = [];
        foreach ($data->examinations() as $e) {
            $starts[$e['exam']] = min($starts[$e['exam']] ?? $e['date'], $e['date']);
        }
        $upcoming = array_filter($starts, fn ($date) => $date >= $today->toDateString());
        if ($upcoming === []) {
            return null;
        }

        asort($upcoming);
        $exam = array_key_first($upcoming);
        $days = (int) round($today->diffInDays(CarbonImmutable::parse($upcoming[$exam], $today->getTimezone())));

        return ['exam' => $exam, 'date' => $upcoming[$exam], 'days' => $days];
    }

    private function inDays(int $days): string
    {
        return match ($days) {
            0 => 'today',
            1 => 'tomorrow',
            default => "in {$days} days",
        };
    }

    public function analyze(SchoolDataSource $data): array
    {
        $insights = [];
        $today = $data->now()->startOfDay();
        $next = $this->nextExam($data);

        // 1. Syllabus behind for the next exam.
        if ($next !== null && $next['days'] <= $this->lookAheadDays) {
            $behind = array_values(array_filter($data->syllabus(), fn ($s) => $s['expected'] - $s['completion'] > 8 && $s['completion'] < 100));
            usort($behind, fn ($a, $b) => ($b['expected'] - $b['completion']) <=> ($a['expected'] - $a['completion']));

            if ($behind !== []) {
                $soon = $next['days'] <= 14;
                $insights[] = new Insight(
                    key: 'exams.readiness',
                    category: 'exams',
                    severity: $soon ? 'critical' : 'warning',
                    score: min(92, 38 + count($behind) * 3 + ($soon ? 18 : 0)),
                    title: "{$next['exam']} " . $this->inDays($next['days']) . " — " . Format::plural(count($behind), 'class-subject') . ' behind syllabus',
                    summary: 'At the current pace these classes will reach the exam without finishing the portion. Consider extra periods or trimming the portion now, not the week before.',
                    briefing: "{$next['exam']} starts " . $this->inDays($next['days']) . " and " . Format::plural(count($behind), 'class-subject') . ' ' . Format::verb(count($behind), 'is', 'are') . ' behind syllabus — worst is '
                        . "{$behind[0]['class']} {$behind[0]['subject']} at {$behind[0]['completion']}%.",
                    metric: $next['days'] . 'd',
                    value: (float) count($behind),
                    items: array_map(fn ($s) => [
                        'label' => "{$s['class']} · {$s['subject']}",
                        'meta' => "{$s['chapter']} · expected {$s['expected']}%",
                        'badge' => "{$s['completion']}% done",
                        'tone' => $s['expected'] - $s['completion'] > 15 ? 'risk' : 'warn',
                    ], array_slice($behind, 0, 6)),
                    actionLabel: 'Open syllabus tracker',
                    actionTab: 'syllabus',
                );
            }
        }

        // 2. Marks entry overdue → group by teacher so the principal can nudge people, not papers.
        $late = array_filter($data->examinations(), function ($e) use ($today) {
            return $e['status'] === 'Marks entry'
                && CarbonImmutable::parse($e['date'], $today->getTimezone())->diffInDays($today) > $this->marksEntryDueDays;
        });
        if ($late !== []) {
            $byTeacher = [];
            foreach ($late as $e) {
                $byTeacher[$e['teacher']][] = "{$e['class']} {$e['subject']}";
            }
            uasort($byTeacher, fn ($a, $b) => count($b) <=> count($a));

            $insights[] = new Insight(
                key: 'exams.marks-overdue',
                category: 'exams',
                severity: 'warning',
                score: min(80, 40 + count($late) * 2),
                title: Format::plural(count($late), 'paper') . " still waiting for marks entry after {$this->marksEntryDueDays}+ days",
                summary: 'Results cannot be published until these are in. ' . Format::plural(count($byTeacher), 'teacher') . ' to follow up with.',
                briefing: Format::plural(count($late), 'exam paper') . ' ' . Format::verb(count($late), 'is', 'are') . ' overdue for marks entry.',
                metric: (string) count($late),
                value: (float) count($late),
                items: array_map(fn ($teacher, $papers) => [
                    'label' => $teacher,
                    'meta' => implode(', ', array_slice($papers, 0, 4)) . (count($papers) > 4 ? '…' : ''),
                    'badge' => Format::plural(count($papers), 'paper'),
                    'tone' => count($papers) >= 3 ? 'risk' : 'warn',
                ], array_keys($byTeacher), array_values($byTeacher)),
                actionLabel: 'Nudge teachers',
                actionTab: 'examinations',
                actionParams: ['view' => 'marks'],
            );
        }

        // 3. Results moderated & ready for the principal's sign-off.
        $ready = array_filter($data->examinations(), fn ($e) => $e['status'] === 'Moderation');
        if ($ready !== []) {
            $insights[] = new Insight(
                key: 'exams.signoff',
                category: 'exams',
                severity: 'info',
                score: 35 + min(20, count($ready)),
                title: Format::plural(count($ready), 'result sheet') . ' moderated and waiting for your sign-off',
                summary: 'Review class averages and outliers, then approve for publishing.',
                metric: (string) count($ready),
                value: (float) count($ready),
                items: array_map(fn ($e) => [
                    'label' => "{$e['class']} · {$e['subject']}",
                    'meta' => $e['exam'] . ' · ' . $e['teacher'],
                    'badge' => 'Ready',
                    'tone' => 'info',
                ], array_slice(array_values($ready), 0, 5)),
                actionLabel: 'Review results',
                actionTab: 'examinations',
                actionParams: ['view' => 'results'],
            );
        }

        return $insights;
    }
}
