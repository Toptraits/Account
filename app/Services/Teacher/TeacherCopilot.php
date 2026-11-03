<?php

namespace App\Services\Teacher;

use App\Services\Principal\Copilot\PrincipalCopilot;
use App\Services\Principal\Insights\Format;

/**
 * Teacher Copilot: teacher-specific questions first ("my day", homework, worksheet,
 * marks entry, cover), then the shared school Copilot for marks/attendance/students.
 * Fee details are not shown to teachers.
 */
class TeacherCopilot
{
    /** @param array<string, array> $registers attendance registers submitted today, keyed by section */
    public function __construct(private TeacherWorkspace $ws, private array $registers = [])
    {
    }

    /** @return array{answer:string, tab:?string, state:array}|null */
    public function answer(string $question): ?array
    {
        $q = ' ' . mb_strtolower(trim(preg_replace('/\s+/', ' ', $question))) . ' ';
        preg_match('/\b([6-9])\s*[- ]?\s*([ab])\b/', $q, $m);
        $section = $m ? $m[1] . '-' . strtoupper($m[2]) : null;

        if (preg_match('/^\s*(help|\?|what can you do|examples?)\s*$/', $q)) {
            return $this->r("Ask me in plain words, for example:\n• what is my day today · my timetable tomorrow · what's my next class\n• who is absent in 8A · attendance below 75% in 8A · tell me about Rohan Sharma\n• low marks in 6A · top 5 in my subject · syllabus behind\n• homework not submitted · marks entry pending · any cover duty today\n• make a worksheet on Fractions for 6A");
        }

        if (preg_match('/\bfees?\b|dues|defaulter|unpaid|payment/', $q)) {
            return $this->r('Fee details are shared with the principal and the accounts office only. If a student\'s fees seem to affect their studies, please raise it with the class teacher or the office.');
        }

        // Today's absentees in my own class: answer from the register I submitted (not from old data).
        $ct = $this->ws->classTeacherOf();
        if (preg_match('/absent|present|attendance/', $q) && preg_match('/today|who is|who are|register/', $q) && ($section ?? $ct) === $ct && $ct) {
            if (! isset($this->registers[$ct])) {
                return $this->r("You have not submitted the {$ct} register yet today. Open Take Attendance to mark it.", 'attendance');
            }
            $absent = array_keys(array_filter($this->registers[$ct]['marks'], fn ($m) => $m === 'absent'));
            $names = array_map(fn ($id) => collect($this->ws->data()->students())->firstWhere('id', $id)['name'] ?? $id, $absent);

            return $this->r("{$ct} register (submitted " . date('H:i', $this->registers[$ct]['at']) . '): ' . (count($this->registers[$ct]['marks']) - count($absent)) . ' present, ' . count($absent) . ' absent' . ($names ? ":\n• " . implode("\n• ", $names) : '.'), 'attendance');
        }

        // Low marks in MY subject: "low marks in 6a", "weak students in 8a", "below 50 in 7b"
        if (preg_match('/low marks|weak|struggl|below \d+|failing|need(?:s)? help/', $q) && ! preg_match('/attendance/', $q)) {
            $line = preg_match('/below (\d+)/', $q, $b) ? (int) $b[1] : 40;
            $subject = $this->ws->subject();
            $secs = $section ? [$section] : $this->ws->sections();
            $rows = [];
            foreach ($this->ws->data()->students() as $s) {
                $mk = $s['subject_marks'][$subject] ?? null;
                if (in_array($s['class'], $secs, true) && $mk !== null && $mk < $line) {
                    $rows[] = "• {$s['name']} ({$s['class']}) — {$subject} {$mk}%";
                }
            }

            return $this->r("Students below {$line}% in {$subject}" . ($section ? " in {$section}" : ' in your classes') . ': ' . count($rows) . "\n" . ($rows ? implode("\n", $rows) . "\n\nTip: ask “make a worksheet for " . ($section ?? $secs[0]) . "” to get a support sheet." : 'Nobody. 🎉'), 'progress', $section ? ['progressSection' => $section] : []);
        }

        // Worksheet: "make a worksheet on fractions for 6a"
        if (preg_match('/worksheet|practice sheet|questions on|question paper/', $q)) {
            $sec = $section ?? ($this->ws->sections()[0] ?? '8-A');
            $topic = preg_match('/(?:on|about|for topic)\s+([a-z0-9 &\-]+?)(?:\s+for\s+|\s+in\s+|\s*$)/', trim($q), $t) ? ucwords(trim($t[1])) : $this->ws->worksheetPlan($sec)['topic'];
            $plan = $this->ws->worksheetPlan($sec);

            return $this->r("Worksheet ready on “{$topic}” for {$sec} in 3 levels:\n• Support (" . count($plan['groups']['Support']) . ' students) · Core (' . count($plan['groups']['Core']) . ') · Challenge (' . count($plan['groups']['Challenge']) . ")\nOpen the Worksheet tab to review and print.", 'worksheet', ['worksheetTopic' => $topic, 'worksheetSection' => $sec, 'showWorksheet' => true]);
        }

        // Prepare for the next / current class
        if (preg_match('/prepare|\bprep\b|plan for|what (?:should i|to) teach|lesson plan|before (?:my )?class/', $q)) {
            $day = $this->ws->day();
            $p = $this->ws->prep($day['current'] ?? $day['next']);
            if (! $p) {
                return $this->r('No more periods today. Ask “my timetable tomorrow” to plan ahead.', 'timetable');
            }
            $per = $p['period'];

            return $this->r("{$per['time']} · {$per['class']} {$per['subject']} · {$per['room']}" . ($p['is_cover'] ? ' (cover)' : '') . "\nTopic: {$p['chapter']}\n• " . implode("\n• ", $p['tips']), 'today');
        }

        // My day / timetable
        if (preg_match('/my (day|periods?|classes today|schedule|timetable)|next (class|period)|what do i have|today\'?s classes|tomorrow/', $q)) {
            $tomorrow = str_contains($q, 'tomorrow');
            $date = $this->ws->data()->now()->addDays($tomorrow ? 1 : 0);
            if ($date->isSunday()) {
                $date = $date->addDay();
            }
            $day = $this->ws->day($date->toDateString());
            if (preg_match('/next (class|period)/', $q) && ! $tomorrow) {
                $n = $day['current'] ?? $day['next'];

                return $this->r($n ? ($day['current'] ? 'Now: ' : 'Next: ') . "{$n['time']} · {$n['class']} {$n['subject']} · {$n['room']}" . (! empty($n['is_cover']) ? ' (cover)' : '') : 'No more periods today.', 'timetable');
            }
            $lines = array_map(fn ($p) => "• {$p['time']} {$p['class']} {$p['subject']} · {$p['room']} · {$p['status']}" . (! empty($p['is_cover']) ? ' · COVER' : ''), $day['periods']);

            return $this->r(($tomorrow ? 'Tomorrow' : 'Today') . ' (' . $date->format('D d M') . '): ' . Format::plural(count($lines), 'period') . "\n" . implode("\n", $lines), 'timetable', ['ttDate' => $tomorrow ? $date->toDateString() : '']);
        }

        if (preg_match('/cover|substitut/', $q)) {
            $cover = $this->ws->day()['cover'];

            return $this->r($cover ? "Cover duty today:\n" . implode("\n", array_map(fn ($c) => "• {$c['time']} {$c['class']} {$c['subject']} for {$c['absent_teacher']} · {$c['room']}", $cover)) : 'No cover duty for you today.', 'timetable');
        }

        if (preg_match('/homework|not submitted|submission/', $q)) {
            $hw = array_values(array_filter($this->ws->homework(), fn ($h) => ! $section || $h['class'] === $section));
            $lines = array_map(fn ($h) => "• {$h['title']} ({$h['class']}, due {$h['due']}): {$h['submitted']}/{$h['total']} submitted" . ($h['missing'] ? ' · missing: ' . implode(', ', array_column($h['missing'], 'name')) : ''), $hw);

            return $this->r("Homework" . ($section ? " for {$section}" : '') . ":\n" . implode("\n", $lines), 'homework');
        }

        if (preg_match('/marks entry|enter marks|papers? (?:to|pending)|pending (?:marks|papers)|correction/', $q)) {
            $p = array_filter($this->ws->marksEntryQueue(), fn ($e) => $e['status'] === 'Marks entry');

            return $this->r($p ? 'Waiting for your marks entry (' . count($p) . "):\n" . implode("\n", array_map(fn ($e) => "• {$e['exam']} · {$e['class']} {$e['subject']} · exam on {$e['date']}", $p)) : 'No marks entry pending. 🎉', 'scan');
        }

        if (preg_match('/my classes|my sections|which classes/', $q)) {
            $lines = array_map(fn ($c) => "• {$c['section']} {$c['subject']}" . ($c['is_class_teacher'] ? ' (class teacher)' : '') . ": average {$c['average']}%, {$c['below40']} below 40%, syllabus {$c['syllabus']['completion']}% (expected {$c['syllabus']['expected']}%)", $this->ws->classCards());

            return $this->r("Your classes:\n" . implode("\n", $lines), 'classes');
        }

        // Shared school Copilot (marks, attendance, students, syllabus, exams…)
        $shared = (new PrincipalCopilot($this->ws->engine()))->answer($question);
        if ($shared !== null) {
            $tab = match ($shared['tab']) {
                'attendance' => 'attendance',
                'timetable' => 'timetable',
                'students', 'risk', 'marks' => 'progress',
                default => null,
            };
            $state = isset($shared['state']['student']) ? [] : array_intersect_key($shared['state'], ['marksClass' => 1, 'marksSection' => 1]);
            if (isset($state['marksClass'], $state['marksSection']) && $state['marksSection'] !== 'all') {
                $state = ['progressSection' => $state['marksClass'] . '-' . $state['marksSection']];
            } else {
                $state = [];
            }

            // Teachers do not see fee details, even inside shared answers.
            $answer = implode("\n", array_filter(explode("\n", $shared['answer']), fn ($line) => ! preg_match('/\bfee|₹/iu', $line)));

            return $this->r($answer, $tab, $state);
        }

        return null;
    }

    private function r(string $answer, ?string $tab = null, array $state = []): array
    {
        return ['answer' => $answer, 'tab' => $tab, 'state' => $state];
    }
}
