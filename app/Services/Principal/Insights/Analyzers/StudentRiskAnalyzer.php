<?php

namespace App\Services\Principal\Insights\Analyzers;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzer;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\Insight;

/**
 * Combines attendance + marks + fees into one risk score per student, and writes a
 * suggested strategy from the actual reasons (no hard-coded "AI signal" text any more).
 *
 * roster() returns the SAME keys your existing Students / At-Risk tables use, so those
 * tabs become data-driven without changing their Blade markup.
 */
class StudentRiskAnalyzer implements Analyzer
{
    private array $memo = [];

    public function __construct(
        private AttendanceAnalyzer $attendance,
        private FeeDefaulterAnalyzer $fees,
    ) {
    }

    /** @return list<array<string, mixed>> sorted by risk, highest first */
    public function roster(SchoolDataSource $data): array
    {
        return $this->memo[spl_object_id($data)] ??= $this->build($data);
    }

    private function build(SchoolDataSource $data): array
    {
        $att = $this->attendance->summary($data)['students'];
        $fees = array_column($this->fees->summary($data)['defaulters'], null, 'id');
        $reviewDate = $data->now()->addDays(14)->format('d M');
        $rows = [];

        foreach ($data->students() as $student) {
            $id = $student['id'];
            $a = $att[$id];
            $marks = $student['subject_marks'];
            $avg = (int) round(array_sum($marks) / count($marks));
            $weakSubject = array_search(min($marks), $marks, true);
            $weakScore = min($marks);
            $feeDays = $fees[$id]['days'] ?? 0;

            $score = 0;
            $reasons = [];
            $steps = [];

            if ($a['streak'] >= 3) {
                $score += 18;
                $reasons[] = "Absent {$a['streak']} days running";
                $steps[] = "Absent {$a['streak']} days in a row — class teacher to call the family today to check on wellbeing.";
            }
            if ($a['pct'] < 90) {
                $score += (90 - $a['pct']) * 1.6;
                $reasons[] = "Attendance {$a['pct']}%";
                if ($a['streak'] < 3) {
                    $steps[] = "Attendance is {$a['pct']}% over 30 school days ({$a['absences']} absences). Agree a return plan with the parent and review on {$reviewDate}.";
                }
            }
            if ($avg < 60) {
                $score += (60 - $avg) * 1.2;
                $reasons[] = "Average {$avg}%";
            }
            if ($weakScore < 40) {
                $score += 8;
                $reasons[] = "{$weakSubject} {$weakScore}%";
                $steps[] = "Weakest in {$weakSubject} ({$weakScore}%). Two remedial periods a week for 4 weeks, then a short re-test.";
            } elseif ($avg < 60) {
                $steps[] = "Overall average {$avg}%. Subject teachers to identify the top two learning gaps and set a fortnightly check.";
            }
            if ($feeDays > 30) {
                $score += 8;
                $reasons[] = "Fee overdue {$feeDays}d";
                // The cross-module insight a traditional ERP never makes:
                if ($a['pct'] < 85 || $avg < 60) {
                    $steps[] = "Fees are {$feeDays} days overdue at the same time as falling attendance/marks. Ask the counsellor to check for family difficulty before any fee reminder is sent.";
                }
            }
            if ($a['lates'] >= 4) {
                $score += 4;
                $reasons[] = "{$a['lates']} late arrivals";
                $steps[] = "{$a['lates']} late arrivals this month — check transport or morning routine with the family.";
            }

            $score = (int) min(100, round($score));
            $type = $score >= 35 ? 'risk' : ($score >= 15 ? 'warn' : 'ok');

            $rows[] = [
                'id' => $id,
                'name' => $student['name'],
                'class' => $student['class'],
                'grade' => $avg >= 80 ? 'A' : ($avg >= 65 ? 'B' : ($avg >= 50 ? 'C' : 'D')),
                'attendance' => (int) round($a['pct']),
                'marks' => $avg,
                'risk_score' => $score,
                'reasons' => $reasons,
                'signal' => $type === 'ok' ? 'On track' : ($reasons[0] ?? 'Needs review'),
                'signal_type' => $type,
                'strategy' => $steps === [] ? 'On track. Keep encouraging current routine and consider enrichment activities.' : implode(' ', $steps),
                'training' => $type === 'ok'
                    ? 'No intervention needed right now.'
                    : "Suggested owner: class teacher of {$student['class']}. Next review: {$reviewDate}. Confirm before acting.",
                'phone' => $student['parent_phone'],
            ];
        }

        usort($rows, fn ($x, $y) => $y['risk_score'] <=> $x['risk_score']);

        return $rows;
    }

    public function analyze(SchoolDataSource $data): array
    {
        $multi = array_values(array_filter($this->roster($data), fn ($r) => count($r['reasons']) >= 2 && $r['signal_type'] === 'risk'));
        if ($multi === []) {
            return [];
        }

        $n = count($multi);

        return [new Insight(
            key: 'students.multi-signal',
            category: 'students',
            severity: $n >= 4 ? 'critical' : 'warning',
            score: min(90, 52 + $n * 3),
            title: Format::plural($n, 'student') . ' showing several warning signs at once',
            summary: 'Attendance, marks and fees are each fine to watch alone — together they usually mean something is wrong at home or in class. Each has a suggested plan.',
            briefing: Format::plural($n, 'student') . ' ' . Format::verb($n, 'has', 'have') . ' 2+ warning signs together, led by ' . $multi[0]['name'] . ' (' . implode(', ', array_slice($multi[0]['reasons'], 0, 2)) . ').',
            metric: (string) $n,
            value: (float) $n,
            items: array_map(fn ($r) => [
                'id' => $r['id'],
                'label' => "{$r['name']} · {$r['class']}",
                'meta' => implode(' · ', $r['reasons']),
                'badge' => "Risk {$r['risk_score']}",
                'tone' => 'risk',
            ], array_slice($multi, 0, 6)),
            actionLabel: 'Open at-risk register',
            actionTab: 'risk',
        )];
    }
}
