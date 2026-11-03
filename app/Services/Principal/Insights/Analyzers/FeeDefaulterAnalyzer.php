<?php

namespace App\Services\Principal\Insights\Analyzers;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzer;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\Insight;
use Carbon\CarbonImmutable;

/**
 * Who has not paid, how much, for how long — and who is about to slip.
 */
class FeeDefaulterAnalyzer implements Analyzer
{
    private array $memo = [];

    public function __construct(private int $graceDays = 7)
    {
    }

    /**
     * Full fee picture for the Fees tab and for other analyzers.
     *
     * @return array{expected:int,collected:int,overdue:int,rate:float,due_next_7:int,
     *   buckets:array<string,int>,defaulters:list<array<string,mixed>>,new_this_week:list<array<string,mixed>>}
     */
    public function summary(SchoolDataSource $data): array
    {
        return $this->memo[spl_object_id($data)] ??= $this->build($data);
    }

    private function build(SchoolDataSource $data): array
    {
        $today = $data->now()->startOfDay();
        $students = array_column($data->students(), null, 'id');
        $expected = $collected = $dueNext7 = 0;
        $byStudent = [];

        foreach ($data->feeLedger() as $row) {
            $due = CarbonImmutable::parse($row['due_date'], $today->getTimezone());

            if ($due->greaterThan($today)) {
                if ($row['paid_on'] === null && $due->lessThanOrEqualTo($today->addDays(7))) {
                    $dueNext7 += $row['amount'];
                }
                continue;
            }

            $expected += $row['amount'];
            if ($row['paid_on'] !== null) {
                $collected += $row['amount'];
                continue;
            }

            $days = (int) $due->diffInDays($today);
            if ($days <= $this->graceDays) {
                continue; // still inside the grace period – not a defaulter yet
            }

            $id = $row['student_id'];
            $byStudent[$id] ??= ['amount' => 0, 'oldest' => 0, 'heads' => [], 'newest' => PHP_INT_MAX];
            $byStudent[$id]['amount'] += $row['amount'];
            $byStudent[$id]['oldest'] = max($byStudent[$id]['oldest'], $days);
            $byStudent[$id]['newest'] = min($byStudent[$id]['newest'], $days);
            $byStudent[$id]['heads'][] = $row['head'];
        }

        $defaulters = [];
        $buckets = ['0–30 days' => 0, '31–60 days' => 0, '60+ days' => 0];

        foreach ($byStudent as $id => $info) {
            $student = $students[$id];
            $bucket = $info['oldest'] > 60 ? '60+ days' : ($info['oldest'] > 30 ? '31–60 days' : '0–30 days');
            $buckets[$bucket] += $info['amount'];

            $defaulters[] = [
                'id' => $id,
                'name' => $student['name'],
                'class' => $student['class'],
                'phone' => $student['parent_phone'],
                'amount' => $info['amount'],
                'days' => $info['oldest'],
                'bucket' => $bucket,
                'heads' => $this->summariseHeads($info['heads']),
                // Only families whose FIRST missed instalment crossed the grace period this week.
                'is_new' => $info['oldest'] - $this->graceDays <= 7,
                // Older + bigger debts first.
                'priority' => $info['amount'] / 1000 + $info['oldest'] * 0.8,
            ];
        }

        usort($defaulters, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return [
            'expected' => $expected,
            'collected' => $collected,
            'overdue' => array_sum(array_column($defaulters, 'amount')),
            'rate' => $expected > 0 ? round($collected / $expected * 100, 1) : 100.0,
            'due_next_7' => $dueNext7,
            'buckets' => $buckets,
            'defaulters' => $defaulters,
            'new_this_week' => array_values(array_filter($defaulters, fn ($d) => $d['is_new'])),
        ];
    }

    /** ["Tuition · Term 1", "Tuition · Term 2", "Transport · Jun", …] → "Tuition T1, T2 · Transport ×5 months" */
    private function summariseHeads(array $heads): string
    {
        $groups = [];
        foreach ($heads as $head) {
            [$name, $part] = array_pad(explode(' · ', $head, 2), 2, '');
            $groups[$name][] = $part;
        }

        return implode(' · ', array_map(function ($name, $parts) {
            return $name === 'Transport'
                ? 'Transport ×' . count($parts) . (count($parts) === 1 ? ' month' : ' months')
                : $name . ' ' . implode(', ', array_map(fn ($p) => str_replace('Term ', 'T', $p), $parts));
        }, array_keys($groups), $groups));
    }

    public function analyze(SchoolDataSource $data): array
    {
        $s = $this->summary($data);
        $insights = [];
        $count = count($s['defaulters']);

        if ($count > 0) {
            $over60 = count(array_filter($s['defaulters'], fn ($d) => $d['days'] > 60));
            $critical = $over60 >= 3 || $s['overdue'] >= 100000;

            $insights[] = new Insight(
                key: 'fees.defaulters',
                category: 'fees',
                severity: $critical ? 'critical' : 'warning',
                score: (int) min(95, 45 + $over60 * 4 + $s['overdue'] / 20000),
                title: Format::inr($s['overdue']) . ' overdue from ' . Format::plural($count, 'family', 'families'),
                summary: "{$over60} unpaid for 60+ days · collection rate {$s['rate']}% · sorted by amount × age so you call the right families first.",
                briefing: Format::inr($s['overdue']) . ' in fees is overdue across ' . Format::plural($count, 'family', 'families')
                    . ($over60 > 0 ? ", {$over60} of them for more than 60 days" : '') . '.',
                metric: Format::inr($s['overdue']),
                value: (float) $s['overdue'],
                items: array_map(fn ($d) => [
                    'id' => $d['id'],
                    'label' => "{$d['name']} · {$d['class']}",
                    'meta' => $d['heads'],
                    'badge' => Format::inr($d['amount']) . " · {$d['days']}d",
                    'tone' => $d['days'] > 60 ? 'risk' : ($d['days'] > 30 ? 'warn' : 'info'),
                ], array_slice($s['defaulters'], 0, 6)),
                actionLabel: 'Open defaulter list',
                actionTab: 'fees',
            );
        }

        $new = $s['new_this_week'];
        if ($new !== []) {
            $insights[] = new Insight(
                key: 'fees.new-defaulters',
                category: 'fees',
                severity: 'warning',
                score: 40 + min(20, count($new) * 3),
                title: Format::plural(count($new), 'family', 'families') . ' slipped into overdue this week',
                summary: 'A friendly reminder in the first week recovers most of these — much easier than chasing a 60-day balance later.',
                briefing: Format::plural(count($new), 'family', 'families') . ' became fee defaulters this week; an early reminder works best.',
                metric: (string) count($new),
                value: (float) count($new),
                items: array_map(fn ($d) => [
                    'id' => $d['id'],
                    'label' => "{$d['name']} · {$d['class']}",
                    'meta' => $d['heads'],
                    'badge' => Format::inr($d['amount']),
                    'tone' => 'warn',
                ], array_slice($new, 0, 5)),
                actionLabel: 'Send gentle reminder',
                actionTab: 'fees',
                actionParams: ['filter' => 'new'],
            );
        }

        if ($s['due_next_7'] > 0) {
            $insights[] = new Insight(
                key: 'fees.upcoming',
                category: 'fees',
                severity: 'info',
                score: 25,
                title: Format::inr($s['due_next_7']) . ' falls due in the next 7 days',
                summary: 'Schedule an advance reminder to parents so this does not turn into overdue balances.',
                metric: Format::inr($s['due_next_7']),
                value: (float) $s['due_next_7'],
                betterWhen: 'higher',
                actionLabel: 'Plan reminder',
                actionTab: 'fees',
            );
        }

        if ($s['rate'] >= 92) {
            $insights[] = new Insight(
                key: 'fees.healthy',
                category: 'fees',
                severity: 'positive',
                score: 15,
                title: "Fee collection is healthy at {$s['rate']}%",
                summary: Format::inr($s['collected']) . ' collected of ' . Format::inr($s['expected']) . ' due so far this year.',
                briefing: "Fee collection is healthy at {$s['rate']}%.",
                metric: $s['rate'] . '%',
                value: $s['rate'],
                betterWhen: 'higher',
            );
        }

        return $insights;
    }
}
