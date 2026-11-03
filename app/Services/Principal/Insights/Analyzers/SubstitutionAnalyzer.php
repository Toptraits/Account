<?php

namespace App\Services\Principal\Insights\Analyzers;

use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Analyzer;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\Insight;

/**
 * Teachers on leave today → which periods are uncovered → who is free to take them.
 */
class SubstitutionAnalyzer implements Analyzer
{
    private array $memo = [];

    public function gaps(SchoolDataSource $data): array
    {
        return $this->memo[spl_object_id($data)] ??= $this->build($data);
    }

    private function build(SchoolDataSource $data): array
    {
        $staff = array_column($data->staff(), null, 'id');
        $onLeave = array_keys(array_filter($staff, fn ($s) => $s['status'] === 'On leave'));
        $teachers = array_filter($staff, fn ($s) => $s['status'] === 'Present' && str_contains($s['designation'], 'Teacher'));
        $timetable = $data->timetableToday();

        $busy = []; // start time => [teacherId => true]
        foreach ($timetable as $period) {
            $busy[$period['start']][$period['teacher_id']] = true;
        }

        $assignedToday = []; // spread cover fairly
        $gaps = [];
        foreach ($timetable as $period) {
            if (! in_array($period['teacher_id'], $onLeave, true) || $period['status'] === 'Completed') {
                continue;
            }

            $free = array_filter($teachers, fn ($t) => ! isset($busy[$period['start']][$t['id']]));
            // Same subject first, then whoever has covered the fewest periods today.
            uasort($free, fn ($a, $b) => [($b['department'] === $period['subject']), -($assignedToday[$b['id']] ?? 0)]
                <=> [($a['department'] === $period['subject']), -($assignedToday[$a['id']] ?? 0)]);
            $cover = reset($free) ?: null;

            if ($cover) {
                $busy[$period['start']][$cover['id']] = true;
                $assignedToday[$cover['id']] = ($assignedToday[$cover['id']] ?? 0) + 1;
            }

            $gaps[] = $period + [
                'absent_teacher' => $period['teacher'],
                'suggested_id' => $cover['id'] ?? null,
                'suggested' => $cover['name'] ?? null,
                'same_subject' => $cover !== null && $cover['department'] === $period['subject'],
            ];
        }

        return [
            'on_leave' => array_map(fn ($id) => $staff[$id], $onLeave),
            'gaps' => $gaps,
        ];
    }

    public function analyze(SchoolDataSource $data): array
    {
        $result = $this->gaps($data);
        $gaps = $result['gaps'];
        $names = array_map(fn ($s) => $s['name'], $result['on_leave']);

        if ($gaps === []) {
            if ($names === [] && $data->timetableToday() !== []) {
                return [new Insight(
                    key: 'staff.all-present',
                    category: 'staff',
                    severity: 'positive',
                    score: 12,
                    title: 'Every teacher is in today',
                    summary: 'No substitutions needed.',
                    briefing: 'All teachers are present today.',
                    value: 0,
                )];
            }

            return [];
        }

        $live = count(array_filter($gaps, fn ($g) => $g['status'] === 'In progress'));
        $uncovered = count(array_filter($gaps, fn ($g) => $g['suggested'] === null));

        return [new Insight(
            key: 'staff.substitution',
            category: 'staff',
            severity: $live > 0 || $uncovered > 0 ? 'critical' : 'warning',
            score: min(98, 62 + count($gaps) * 2 + $live * 8),
            title: Format::plural(count($gaps), 'period') . ' today ' . Format::verb(count($gaps), 'needs', 'need') . ' a substitute',
            summary: Format::list($names) . (count($names) === 1 ? ' is' : ' are') . ' on leave. '
                . ($live > 0 ? "{$live} of these classes are running right now without a teacher. " : '')
                . 'A free teacher is suggested for each period — same subject first.',
            briefing: Format::list($names) . ' on leave — ' . Format::plural(count($gaps), 'period') . ' ' . Format::verb(count($gaps), 'needs', 'need') . ' cover'
                . ($live > 0 ? ", {$live} right now" : '') . '.',
            metric: (string) count($gaps),
            value: (float) count($gaps),
            items: array_map(fn ($g) => [
                'label' => "{$g['time']} · {$g['class']} {$g['subject']}",
                'meta' => "{$g['absent_teacher']} on leave · {$g['room']}" . ($g['status'] === 'In progress' ? ' · happening now' : ''),
                'badge' => $g['suggested'] ? 'Cover: ' . $g['suggested'] : 'No one free',
                'tone' => $g['suggested'] ? ($g['status'] === 'In progress' ? 'risk' : 'info') : 'risk',
            ], array_slice($gaps, 0, 8)),
            actionLabel: 'Assign substitutes',
            actionTab: 'timetable',
        )];
    }
}
