<?php

namespace App\Services\Principal\Insights\Analyzers;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzer;
use App\Services\Principal\Insights\Insight;

/**
 * Marks by class/section and subject, plus the list of low-scoring students.
 */
class MarksAnalyzer implements Analyzer
{
    public function __construct(private int $passMark = 40)
    {
    }

    /**
     * @param  string  $scope  'all' or a section like '6-A' (or a whole class like '6')
     * @param  int  $threshold  students below this % in any subject are listed
     */
    public function summary(SchoolDataSource $data, string $scope = 'all', int $threshold = 40): array
    {
        $students = $data->students();
        $subjects = array_keys($students[0]['subject_marks'] ?? []);

        // Section × subject averages (the heatmap) and school-wide subject averages.
        $matrix = [];
        $schoolTotals = [];
        foreach ($students as $s) {
            foreach ($s['subject_marks'] as $subject => $mark) {
                $matrix[$s['class']][$subject][] = $mark;
                $schoolTotals[$subject][] = $mark;
            }
        }
        ksort($matrix, SORT_NATURAL);
        $heatmap = [];
        foreach ($matrix as $section => $bySubject) {
            foreach ($bySubject as $subject => $marks) {
                $heatmap[$section][$subject] = (int) round(array_sum($marks) / count($marks));
            }
            $heatmap[$section]['Overall'] = (int) round(array_sum($heatmap[$section]) / count($bySubject));
        }
        $schoolAvg = array_map(fn ($m) => round(array_sum($m) / count($m), 1), $schoolTotals);

        // Students in the chosen scope.
        $inScope = array_values(array_filter($students, function ($s) use ($scope) {
            return $scope === 'all'
                || $s['class'] === $scope
                || (! str_contains($scope, '-') && str_starts_with($s['class'], $scope . '-'));
        }));

        $subjectRows = [];
        foreach ($subjects as $subject) {
            $marks = array_column(array_map(fn ($s) => ['m' => $s['subject_marks'][$subject], 'n' => $s['name']], $inScope), 'm', 'n');
            if ($marks === []) {
                continue;
            }
            $avg = round(array_sum($marks) / count($marks), 1);
            arsort($marks);
            $subjectRows[] = [
                'subject' => $subject,
                'average' => $avg,
                'school_average' => $schoolAvg[$subject],
                'vs_school' => round($avg - $schoolAvg[$subject], 1),
                'highest' => reset($marks),
                'highest_name' => array_key_first($marks),
                'lowest' => end($marks),
                'lowest_name' => array_key_last($marks),
                'pass_rate' => round(count(array_filter($marks, fn ($m) => $m >= $this->passMark)) / count($marks) * 100),
                'below' => count(array_filter($marks, fn ($m) => $m < $threshold)),
            ];
        }

        $rows = array_map(function ($s) use ($threshold) {
            $marks = $s['subject_marks'];
            $avg = round(array_sum($marks) / count($marks), 1);
            $weak = array_filter($marks, fn ($m) => $m < $threshold);
            asort($weak);

            return [
                'id' => $s['id'],
                'name' => $s['name'],
                'class' => $s['class'],
                'marks' => $marks,
                'average' => $avg,
                'weak_subjects' => $weak,
            ];
        }, $inScope);

        $low = array_values(array_filter($rows, fn ($r) => $r['weak_subjects'] !== [] || $r['average'] < $threshold));
        usort($low, fn ($a, $b) => $a['average'] <=> $b['average']);

        $top = $rows;
        usort($top, fn ($a, $b) => $b['average'] <=> $a['average']);

        $scopeAvg = $rows === [] ? 0 : round(array_sum(array_column($rows, 'average')) / count($rows), 1);
        $overall = array_column(array_map(fn ($sec, $v) => ['s' => $sec, 'v' => $v['Overall']], array_keys($heatmap), $heatmap), 'v', 's');

        return [
            'subjects' => $subjects,
            'heatmap' => $heatmap,
            'school_average' => round(array_sum($schoolAvg) / max(1, count($schoolAvg)), 1),
            'scope_average' => $scopeAvg,
            'student_count' => count($rows),
            'subject_rows' => $subjectRows,
            'low_students' => $low,
            'top_students' => array_slice($top, 0, 3),
            'rank' => $scope !== 'all' && isset($overall[$scope])
                ? count(array_filter($overall, fn ($v) => $v > $overall[$scope])) + 1
                : null,
            'section_count' => count($heatmap),
        ];
    }

    /** Home-screen insight: the weakest section-subject combinations right now. */
    public function analyze(SchoolDataSource $data): array
    {
        $s = $this->summary($data);
        $weak = [];
        foreach ($s['heatmap'] as $section => $bySubject) {
            foreach ($bySubject as $subject => $avg) {
                if ($subject !== 'Overall' && $avg < 55) {
                    $weak[] = ['section' => $section, 'subject' => $subject, 'avg' => $avg];
                }
            }
        }
        if ($weak === []) {
            return [];
        }
        usort($weak, fn ($a, $b) => $a['avg'] <=> $b['avg']);
        $worst = $weak[0];

        return [new Insight(
            key: 'academics.weak-sections',
            category: 'academics',
            severity: $worst['avg'] < 45 ? 'critical' : 'warning',
            score: min(85, 40 + count($weak) * 3 + (55 - $worst['avg'])),
            title: "{$worst['section']} {$worst['subject']} averages {$worst['avg']}% — lowest in school",
            summary: count($weak) . ' class-subject ' . (count($weak) === 1 ? 'combination is' : 'combinations are') . ' below 55% on average. Open Marks to see which students are pulling it down.',
            briefing: "{$worst['section']} {$worst['subject']} has the weakest marks ({$worst['avg']}% average).",
            metric: $worst['avg'] . '%',
            value: (float) count($weak),
            items: array_map(fn ($w) => [
                'label' => "{$w['section']} · {$w['subject']}",
                'meta' => 'School average ' . round(array_sum(array_column(array_map(fn ($r) => ['v' => $r[$w['subject']]], $s['heatmap']), 'v')) / $s['section_count']) . '%',
                'badge' => "{$w['avg']}%",
                'tone' => $w['avg'] < 45 ? 'risk' : 'warn',
            ], array_slice($weak, 0, 6)),
            actionLabel: 'Open marks for ' . $worst['section'],
            actionTab: 'marks',
            actionParams: ['section' => $worst['section']],
        )];
    }
}
