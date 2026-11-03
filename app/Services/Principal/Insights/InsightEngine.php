<?php

namespace App\Services\Principal\Insights;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzers\AttendanceAnalyzer;
use App\Services\Principal\Insights\Analyzers\ExamReadinessAnalyzer;
use App\Services\Principal\Insights\Analyzers\FeeDefaulterAnalyzer;
use App\Services\Principal\Insights\Analyzers\MarksAnalyzer;
use App\Services\Principal\Insights\Analyzers\StudentRiskAnalyzer;
use App\Services\Principal\Insights\Analyzers\SubstitutionAnalyzer;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * The "brain" of the principal dashboard.
 *
 *  1. Runs every analyzer on today's data.
 *  2. Adds context (time of day, fee-due week, Saturday review…).
 *  3. Compares with the principal's last visit (new / worse / better / resolved).
 *  4. Hides what the principal dismissed or snoozed — until the situation changes.
 *  5. Ranks everything, so the screen layout is different whenever the school is different.
 */
class InsightEngine
{
    /** A new "visit" starts after this many minutes away. */
    private const VISIT_GAP_MINUTES = 30;

    public readonly FeeDefaulterAnalyzer $fees;

    public readonly AttendanceAnalyzer $attendance;

    public readonly SubstitutionAnalyzer $substitution;

    public readonly ExamReadinessAnalyzer $exams;

    public readonly StudentRiskAnalyzer $students;

    public readonly MarksAnalyzer $marks;

    public function __construct(
        private SchoolDataSource $data,
        private Cache $cache,
        array $config = [],
    ) {
        $this->fees = new FeeDefaulterAnalyzer($config['fees']['grace_days'] ?? 7);
        $this->attendance = new AttendanceAnalyzer(
            $config['attendance']['defaulter_below'] ?? 75,
            $config['attendance']['streak_days'] ?? 3,
            $config['attendance']['section_drop_points'] ?? 6,
        );
        $this->substitution = new SubstitutionAnalyzer();
        $this->exams = new ExamReadinessAnalyzer(
            $config['exams']['look_ahead_days'] ?? 30,
            $config['exams']['marks_entry_due_days'] ?? 3,
        );
        $this->students = new StudentRiskAnalyzer($this->attendance, $this->fees);
        $this->marks = new MarksAnalyzer($config['marks']['pass_mark'] ?? 40);
    }

    public function data(): SchoolDataSource
    {
        return $this->data;
    }

    // ------------------------------------------------------------------ visits

    /** Call once when the page is opened. Freezes "what you saw last time" for comparison. */
    public function beginVisit(int|string $userId): void
    {
        $snapshot = $this->cache->get($this->key($userId, 'snapshot'));
        $lastSeen = $snapshot['at'] ?? null;

        if ($lastSeen !== null && $this->data->now()->timestamp - $lastSeen < self::VISIT_GAP_MINUTES * 60) {
            return; // same visit (refresh / quick return) – keep the old baseline
        }

        $this->cache->put($this->key($userId, 'baseline'), $snapshot ?? ['at' => null, 'items' => [], 'kpis' => []], 60 * 60 * 24 * 60);
    }

    /** Remember what was shown, so the next visit can say what changed. */
    public function saveSnapshot(int|string $userId, InsightReport $report): void
    {
        $baseline = $this->baseline($userId);
        $items = [];
        foreach ($report->all as $insight) {
            $previous = $baseline['items'][$insight->key] ?? null;
            $items[$insight->key] = [
                'value' => $insight->value,
                'severity' => $insight->severity,
                'title' => $insight->title,
                'seen' => $previous && $previous['value'] === $insight->value ? ($previous['seen'] ?? 1) + 1 : 1,
            ];
        }

        $this->cache->put($this->key($userId, 'snapshot'), [
            'at' => $this->data->now()->timestamp,
            'items' => $items,
            'kpis' => array_column($report->kpis, 'raw', 'key'),
        ], 60 * 60 * 24 * 60);
    }

    public function dismiss(int|string $userId, string $insightKey, string $fingerprint): void
    {
        $dismissed = $this->cache->get($this->key($userId, 'dismissed'), []);
        $dismissed[$insightKey] = $fingerprint;
        $this->cache->put($this->key($userId, 'dismissed'), $dismissed, 60 * 60 * 24 * 30);
    }

    public function snoozeUntilTomorrow(int|string $userId, string $insightKey): void
    {
        $snoozed = $this->cache->get($this->key($userId, 'snoozed'), []);
        $snoozed[$insightKey] = $this->data->now()->addDay()->setTime(8, 0)->timestamp;
        $this->cache->put($this->key($userId, 'snoozed'), $snoozed, 60 * 60 * 24 * 3);
    }

    public function restoreHidden(int|string $userId): void
    {
        $this->cache->forget($this->key($userId, 'dismissed'));
        $this->cache->forget($this->key($userId, 'snoozed'));
    }

    // ------------------------------------------------------------------ report

    public function report(int|string $userId, string $name = 'Principal'): InsightReport
    {
        $now = $this->data->now();
        $baseline = $this->baseline($userId);
        $firstVisit = $baseline['at'] === null;
        $dismissed = $this->cache->get($this->key($userId, 'dismissed'), []);
        $snoozed = $this->cache->get($this->key($userId, 'snoozed'), []);

        $all = array_merge(
            $this->substitution->analyze($this->data),
            $this->attendance->analyze($this->data),
            $this->fees->analyze($this->data),
            $this->students->analyze($this->data),
            $this->exams->analyze($this->data),
            $this->marks->analyze($this->data),
        );

        $visible = [];
        $hidden = $new = $worse = $better = 0;

        foreach ($all as $insight) {
            [$boost, $why] = $this->contextBoost($insight, $now);
            $score = $insight->score + $boost;
            $insight->why = array_merge(["Base priority {$insight->score} from live {$insight->category} data"], $why);

            if (! $firstVisit) {
                $previous = $baseline['items'][$insight->key] ?? null;

                if ($previous === null) {
                    $insight->change = 'new';
                    if ($insight->severity !== 'positive') {
                        $score += 8;
                        $new++;
                        $insight->why[] = 'New since your last visit (+8)';
                    }
                } elseif ($insight->value !== null && $previous['value'] !== null && abs($insight->value - $previous['value']) > 0.0001) {
                    $delta = $insight->value - $previous['value'];
                    $improved = $insight->betterWhen === 'lower' ? $delta < 0 : $delta > 0;
                    $insight->change = $improved ? 'better' : 'worse';
                    $insight->changeText = ($delta > 0 ? '↑ ' : '↓ ') . $this->formatDelta($insight, abs($delta)) . ' since last visit';
                    if ($improved) {
                        $better++;
                    } else {
                        $score += 6;
                        $worse++;
                        $insight->why[] = 'Got worse since your last visit (+6)';
                    }
                } else {
                    $insight->change = 'same';
                    if (($previous['seen'] ?? 1) >= 3) {
                        $score -= 8;
                        $insight->why[] = 'Unchanged for several visits (−8)';
                    }
                }
            }

            $insight->finalScore = max(0, min(100, $score));

            $isDismissed = ($dismissed[$insight->key] ?? null) === $insight->fingerprint();
            $isSnoozed = ($snoozed[$insight->key] ?? 0) > $now->timestamp;
            if ($isDismissed || $isSnoozed) {
                $hidden++;
                continue;
            }
            $visible[] = $insight;
        }

        usort($visible, fn (Insight $a, Insight $b) => $b->finalScore <=> $a->finalScore);

        // Problems that existed last time and are gone now.
        $currentKeys = array_map(fn (Insight $i) => $i->key, $all);
        $resolved = array_filter(
            $baseline['items'],
            fn ($item, $key) => ($item['severity'] ?? '') !== 'positive' && ! in_array($key, $currentKeys, true),
            ARRAY_FILTER_USE_BOTH,
        );

        $kpis = $this->kpis($baseline['kpis'] ?? []);

        return new InsightReport(
            insights: $visible,
            all: $all,
            hiddenCount: $hidden,
            greeting: $this->greeting($now, $name),
            focus: $this->focus($now),
            briefing: $this->briefing($visible),
            sinceLastVisit: $this->sinceText($baseline, $new, $worse, $better + count($resolved), $now),
            kpis: $kpis,
            categoryOrder: $this->categoryOrder($visible),
            generatedAt: $now,
        );
    }

    // ----------------------------------------------------------------- helpers

    /** Situation-aware priority: what matters at 8:45 am is not what matters at 3 pm. */
    private function contextBoost(Insight $insight, CarbonImmutable $now): array
    {
        $boost = 0;
        $why = [];
        $hour = (int) $now->format('G');

        if ($hour < 11 && in_array($insight->category, ['attendance', 'staff'], true)) {
            $boost += 12;
            $why[] = 'Morning: attendance and classroom cover come first (+12)';
        }
        if ($hour >= 13 && in_array($insight->category, ['exams', 'students', 'academics'], true)) {
            $boost += 8;
            $why[] = 'Afternoon: academic follow-up time (+8)';
        }
        if ($now->day <= 12 && $insight->category === 'fees') {
            $boost += 10;
            $why[] = 'Fee-due period of the month (+10)';
        }
        if ($now->isSaturday() && $insight->category === 'students') {
            $boost += 8;
            $why[] = 'Saturday weekly student review (+8)';
        }

        return [$boost, $why];
    }

    private function kpis(array $previous): array
    {
        $att = $this->attendance->summary($this->data);
        $fees = $this->fees->summary($this->data);
        $roster = $this->students->roster($this->data);
        $staff = $this->data->staff();
        $teaching = array_filter($staff, fn ($s) => str_contains($s['designation'], 'Teacher'));
        $present = array_filter($teaching, fn ($s) => $s['status'] === 'Present');
        $syllabus = $this->data->syllabus();
        $onTrack = count(array_filter($syllabus, fn ($s) => $s['status'] !== 'Needs review'));
        $next = $this->exams->nextExam($this->data);
        $atRisk = count(array_filter($roster, fn ($r) => $r['signal_type'] === 'risk'));

        $rows = [
            ['key' => 'attendance', 'label' => $att['register_taken'] ? 'Attendance today' : 'Attendance (last day)', 'raw' => $att['latest_rate'],
                'value' => $att['latest_rate'] . '%', 'foot' => $this->attendanceWhy($att),
                'tone' => $att['latest_rate'] >= 93 ? 'ok' : ($att['latest_rate'] >= 85 ? 'warn' : 'risk'), 'better' => 'higher', 'tab' => 'attendance',
                'spark' => array_values(array_slice($att['daily'], -14))],
            ['key' => 'fees', 'label' => 'Fee collection', 'raw' => $fees['rate'],
                'value' => $fees['rate'] . '%', 'foot' => Format::inr($fees['overdue']) . ' overdue · ' . count($fees['defaulters']) . ' families',
                'tone' => $fees['rate'] >= 90 ? 'ok' : ($fees['rate'] >= 80 ? 'warn' : 'risk'), 'better' => 'higher', 'tab' => 'fees', 'spark' => []],
            ['key' => 'risk', 'label' => 'Students at risk', 'raw' => $atRisk,
                'value' => (string) $atRisk, 'foot' => 'of ' . count($roster) . ' · combined score',
                'tone' => $atRisk === 0 ? 'ok' : ($atRisk <= 3 ? 'warn' : 'risk'), 'better' => 'lower', 'tab' => 'risk', 'spark' => []],
            ['key' => 'staff', 'label' => 'Teachers present', 'raw' => count($present),
                'value' => count($present) . '/' . count($teaching), 'foot' => count($teaching) - count($present) === 0 ? 'Full strength' : (count($teaching) - count($present)) . ' on leave',
                'tone' => count($present) === count($teaching) ? 'ok' : 'warn', 'better' => 'higher', 'tab' => 'staff', 'spark' => []],
            ['key' => 'syllabus', 'label' => 'Syllabus on pace', 'raw' => round($onTrack / max(1, count($syllabus)) * 100),
                'value' => round($onTrack / max(1, count($syllabus)) * 100) . '%', 'foot' => (count($syllabus) - $onTrack) . ' class-subjects behind',
                'tone' => $onTrack / max(1, count($syllabus)) >= 0.85 ? 'ok' : 'warn', 'better' => 'higher', 'tab' => 'syllabus', 'spark' => []],
            ['key' => 'exam', 'label' => 'Next exam', 'raw' => $next['days'] ?? null,
                'value' => $next ? ($next['days'] === 0 ? 'Today' : "{$next['days']} days") : '—', 'foot' => $next['exam'] ?? 'Nothing scheduled',
                'tone' => $next && $next['days'] <= 7 ? 'warn' : 'info', 'better' => 'higher', 'tab' => 'examinations', 'spark' => []],
        ];

        foreach ($rows as &$row) {
            $before = $previous[$row['key']] ?? null;
            $row['trend'] = null;
            if ($before !== null && $row['raw'] !== null && $row['key'] !== 'exam' && abs($row['raw'] - $before) > 0.05) {
                $delta = round($row['raw'] - $before, 1);
                $good = $row['better'] === 'higher' ? $delta > 0 : $delta < 0;
                $row['trend'] = ($delta > 0 ? '▲ ' : '▼ ') . abs($delta) . ($good ? '' : ' ⚠');
            }
        }

        return $rows;
    }

    /**
     * One line that answers "why isn't attendance 100%?" for the day shown on the card:
     * how many were absent, how many of them are on a streak, and which section pulled it down.
     */
    private function attendanceWhy(array $att): string
    {
        $date = $att['latest_date'];
        if ($date === null) {
            return 'No register taken yet';
        }
        $history = $this->data->attendanceHistory(30);
        $absent = $streak = 0;
        foreach ($history as $id => $marks) {
            if (($marks[$date] ?? null) === 'A') {
                $absent++;
                $streak += ($att['students'][$id]['streak'] ?? 0) >= 3 ? 1 : 0;
            }
        }
        if ($absent === 0) {
            return 'Everyone present — 100%';
        }
        $low = $att['sections'] ? collect($att['sections'])->sortBy('rate')->first() : null;
        $line = Format::plural($absent, 'student') . ' absent';
        if ($streak > 0) {
            $line .= ' (' . $streak . ' on 3+ day ' . Format::verb($streak, 'streak', 'streaks') . ')';
        } elseif (($n = count($att['defaulters'])) > 0) {
            $line .= " · {$n} below 75% this month";
        }
        if ($low && $low['rate'] < 100) {
            $line .= " · lowest {$low['class']} {$low['rate']}%";
        }

        return $line;
    }

    /** @param list<Insight> $visible */
    private function briefing(array $visible): array
    {
        $lines = [];
        foreach ($visible as $insight) {
            if ($insight->severity !== 'positive' && $insight->briefing !== '' && count($lines) < 3) {
                $lines[] = $insight->briefing;
            }
        }
        foreach ($visible as $insight) {
            if ($insight->severity === 'positive' && $insight->briefing !== '') {
                $lines[] = 'Good news: ' . lcfirst($insight->briefing);
                break;
            }
        }

        return $lines === [] ? ['Nothing needs your attention right now. A calm day.'] : $lines;
    }

    private function sinceText(array $baseline, int $new, int $worse, int $improved, CarbonImmutable $now): string
    {
        if ($baseline['at'] === null) {
            return 'First visit — from now on I will tell you what changed between visits.';
        }

        $ago = CarbonImmutable::createFromTimestamp($baseline['at'], $now->getTimezone())->diffForHumans($now, ['parts' => 1, 'syntax' => CarbonImmutable::DIFF_RELATIVE_TO_NOW]);
        $parts = array_filter([
            $new ? Format::plural($new, 'new alert') : null,
            $worse ? "{$worse} got worse" : null,
            $improved ? "{$improved} improved or resolved" : null,
        ]);

        return "Since your last visit ({$ago}): " . ($parts === [] ? 'nothing important changed.' : Format::list($parts) . '.');
    }

    private function greeting(CarbonImmutable $now, string $name): string
    {
        $hour = (int) $now->format('G');
        $part = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        return "{$part}, {$name}";
    }

    private function focus(CarbonImmutable $now): string
    {
        $hour = (int) $now->format('G');
        $focus = match (true) {
            $now->isSunday() => 'Sunday · plan the week ahead',
            $hour < 11 => 'Morning focus · attendance & classroom cover',
            $hour < 14 => 'Midday focus · academics & approvals',
            default => 'End-of-day focus · follow-ups & tomorrow',
        };
        if ($now->day <= 12) {
            $focus .= ' · fee-due week';
        }
        if ($now->isSaturday()) {
            $focus .= ' · weekly review';
        }

        return $focus;
    }

    /** @param list<Insight> $visible */
    private function categoryOrder(array $visible): array
    {
        $max = [];
        foreach ($visible as $insight) {
            $max[$insight->category] = max($max[$insight->category] ?? 0, $insight->finalScore);
        }
        arsort($max);

        return array_keys($max);
    }

    private function formatDelta(Insight $insight, float $delta): string
    {
        return str_starts_with($insight->key, 'fees.') && $delta >= 1000
            ? Format::inr($delta)
            : rtrim(rtrim(number_format($delta, 1), '0'), '.');
    }

    private function baseline(int|string $userId): array
    {
        return $this->cache->get($this->key($userId, 'baseline')) ?? ['at' => null, 'items' => [], 'kpis' => []];
    }

    private function key(int|string $userId, string $name): string
    {
        return "principal-insights:{$userId}:{$name}";
    }
}
