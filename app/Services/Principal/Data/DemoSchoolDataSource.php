<?php

namespace App\Services\Principal\Data;

use Carbon\CarbonImmutable;

/**
 * Demo data that behaves like a real school over time.
 *
 * Every value is derived from (student / staff id + calendar date), so:
 *  - the same day always gives the same numbers (stable while you click around), but
 *  - tomorrow is different: new absences, fees fall due on the 5th/10th, families pay,
 *    teachers go on leave, exam marks move from "Marks entry" to "Published", and
 *    syllabus progress advances.
 *
 * Replace with an Eloquent-backed class later; keep the array shapes the same.
 */
class DemoSchoolDataSource implements SchoolDataSource
{
    private const SECTIONS = ['6-A', '6-B', '7-A', '7-B', '8-A', '8-B', '9-A', '9-B'];

    private const STUDENTS_PER_SECTION = 7;

    private const CORE_SUBJECTS = ['Mathematics', 'Science', 'English', 'Social Studies'];

    private const TIMETABLE_SUBJECTS = ['Mathematics', 'Science', 'English', 'Social Studies', 'Computer Science'];

    private const SLOTS = [
        ['09:00', '09:45'], ['09:50', '10:35'], ['10:50', '11:35'],
        ['11:40', '12:25'], ['13:15', '14:00'], ['14:05', '14:50'],
    ];

    private const FIRST_NAMES = [
        'Ananya', 'Rohan', 'Nisha', 'Dev', 'Meera', 'Aarav', 'Ishaan', 'Diya', 'Kabir', 'Saanvi',
        'Vihaan', 'Anika', 'Arjun', 'Kavya', 'Reyansh', 'Myra', 'Aditya', 'Tara', 'Krish', 'Pari',
        'Yash', 'Riya', 'Siddharth', 'Zoya',
    ];

    private const LAST_NAMES = [
        'Iyer', 'Das', 'Verma', 'Patel', 'Nair', 'Rao', 'Sharma', 'Reddy', 'Gupta', 'Menon',
        'Kulkarni', 'Singh', 'Joshi', 'Pillai', 'Bose', 'Khan',
    ];

    /** id => [name, designation, department, subjects] */
    private const STAFF = [
        'STF001' => ['Sunita Rao', 'Class Teacher', 'Mathematics', 'Mathematics'],
        'STF002' => ['Ravi Kumar', 'Subject Teacher', 'English', 'English'],
        'STF003' => ['Meena Sen', 'Subject Teacher', 'Science', 'Science'],
        'STF004' => ['Arun Das', 'Class Teacher', 'Social Studies', 'Social Studies'],
        'STF005' => ['Kavya Iyer', 'Subject Teacher', 'Computer Science', 'Computer Science'],
        'STF006' => ['Prakash Patel', 'Examination Coordinator', 'Administration', 'Exam coordination'],
        'STF007' => ['Anita Reddy', 'Principal', 'Leadership', 'School leadership'],
        'STF008' => ['Rahul Verma', 'Lab Assistant', 'Science Lab', 'Laboratory support'],
        'STF009' => ['Deepa Menon', 'Subject Teacher', 'Mathematics', 'Mathematics'],
        'STF010' => ['Neha Joshi', 'Subject Teacher', 'English', 'English'],
        'STF011' => ['Farhan Ali', 'Subject Teacher', 'Science', 'Science'],
        'STF012' => ['Lata Pillai', 'Class Teacher', 'Social Studies', 'Social Studies'],
    ];

    private const SUBJECT_TEACHERS = [
        'Mathematics' => ['STF001', 'STF009'],
        'Science' => ['STF003', 'STF011'],
        'English' => ['STF002', 'STF010'],
        'Social Studies' => ['STF004', 'STF012'],
        'Computer Science' => ['STF005'],
    ];

    private const CHAPTERS = [
        'Mathematics' => ['Number systems', 'Fractions & decimals', 'Integers', 'Algebraic expressions', 'Linear equations', 'Ratio & proportion', 'Geometry basics', 'Mensuration', 'Data handling', 'Revision'],
        'Science' => ['Living things', 'Materials', 'Motion', 'Force & pressure', 'Heat', 'Light', 'Electricity', 'Environment', 'Human body', 'Revision'],
        'English' => ['Reading skills', 'Grammar: tenses', 'Poetry', 'Formal letters', 'Comprehension', 'Story writing', 'Grammar: voice', 'Drama', 'Report writing', 'Revision'],
        'Social Studies' => ['Maps & globes', 'Early civilisations', 'Our constitution', 'Resources', 'Medieval India', 'Local government', 'Climate', 'Trade & markets', 'Modern India', 'Revision'],
    ];

    private CarbonImmutable $now;

    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(?CarbonImmutable $now = null)
    {
        $this->now = $now ?? CarbonImmutable::now();
    }

    public function now(): CarbonImmutable
    {
        return $this->now;
    }

    // ---------------------------------------------------------------- students

    public function students(): array
    {
        return $this->memo['students'] ??= $this->buildStudents();
    }

    private function buildStudents(): array
    {
        $students = [];

        foreach (self::SECTIONS as $section) {
            for ($n = 1; $n <= self::STUDENTS_PER_SECTION; $n++) {
                $id = 'STU' . str_replace('-', '', $section) . sprintf('%02d', $n);
                $ability = 38 + $this->roll("ability|{$id}") * 57;

                $marks = [];
                foreach (self::CORE_SUBJECTS as $subject) {
                    $marks[$subject] = (int) max(20, min(99, round($ability + ($this->roll("sub|{$id}|{$subject}") - 0.5) * 24)));
                }

                $students[] = [
                    'id' => $id,
                    'name' => $this->pick(self::FIRST_NAMES, "fn|{$id}") . ' ' . $this->pick(self::LAST_NAMES, "ln|{$id}"),
                    'class' => $section,
                    'parent_phone' => '+91 9' . sprintf('%09d', (int) ($this->roll("ph|{$id}") * 999999999)),
                    'subject_marks' => $marks,
                ];
            }
        }

        return $students;
    }

    // -------------------------------------------------------------- attendance

    public function attendanceHistory(int $schoolDays = 30): array
    {
        return $this->memo["attendance{$schoolDays}"] ??= $this->buildAttendance($schoolDays);
    }

    private function buildAttendance(int $count): array
    {
        $days = $this->schoolDays($count);
        $week = $this->now->format('o-W');
        $history = [];

        foreach ($this->students() as $student) {
            $id = $student['id'];
            $rate = 0.97 - 0.32 * ($this->roll("attbase|{$id}") ** 4);   // most ~95%, a few ~65%
            $late = 0.02 + 0.2 * ($this->roll("late|{$id}") ** 3);

            // Occasionally a student goes absent for several days in a row (illness, family issue…).
            $streak = $this->roll("streak|{$id}|{$week}") < 0.04 ? 3 + (int) ($this->roll("slen|{$id}|{$week}") * 3) : 0;
            $streakFrom = count($days) - $streak;

            foreach ($days as $i => $day) {
                if ($streak > 0 && $i >= $streakFrom) {
                    $history[$id][$day] = 'A';
                } elseif ($this->roll("att|{$id}|{$day}") > $rate) {
                    $history[$id][$day] = 'A';
                } else {
                    $history[$id][$day] = $this->roll("lt|{$id}|{$day}") < $late ? 'L' : 'P';
                }
            }
        }

        return $history;
    }

    /** Last N school days (Mon–Sat), oldest first. Today counts once the register is taken (09:30). */
    private function schoolDays(int $count): array
    {
        $day = $this->now->startOfDay();
        if ($this->now->format('H:i') < '09:30') {
            $day = $day->subDay();
        }

        $days = [];
        while (count($days) < $count) {
            if (! $day->isSunday()) {
                $days[] = $day->toDateString();
            }
            $day = $day->subDay();
        }

        return array_reverse($days);
    }

    // -------------------------------------------------------------------- fees

    public function feeLedger(): array
    {
        return $this->memo['fees'] ??= $this->buildFees();
    }

    private function buildFees(): array
    {
        $start = $this->academicStart();
        $ledger = [];

        foreach ($this->students() as $student) {
            $id = $student['id'];
            $classNumber = (int) explode('-', $student['class'])[0];
            $tuition = 15000 + $classNumber * 500;

            $heads = [
                ['Tuition · Term 1', $tuition, $start->addDays(39)],   // ~10 Jul
                ['Tuition · Term 2', $tuition, $start->addDays(131)],  // ~10 Oct
                ['Tuition · Term 3', $tuition - 2000, $start->addDays(223)], // ~10 Jan
            ];

            if ($this->roll("transport|{$id}") < 0.45) {
                for ($m = 0; $m < 10; $m++) {
                    $due = $start->addMonths($m)->day(5);
                    $heads[] = ['Transport · ' . $due->format('M'), 1800, $due];
                }
            }

            $chronic = $this->roll("payer|{$id}") < 0.07;
            $typicalDelay = (int) (($this->roll("delay|{$id}") ** 2) * 70);

            foreach ($heads as [$head, $amount, $due]) {
                $paidOn = null;
                if (! $chronic) {
                    $jitter = (int) round($this->roll("jit|{$id}|{$head}") * 10 - 3);
                    $date = $due->addDays($typicalDelay + $jitter);
                    $paidOn = $date->lessThanOrEqualTo($this->now) ? $date->toDateString() : null; // never reveal the future
                }

                $ledger[] = [
                    'student_id' => $id,
                    'head' => $head,
                    'amount' => $amount,
                    'due_date' => $due->toDateString(),
                    'paid_on' => $paidOn,
                ];
            }
        }

        return $ledger;
    }

    // ------------------------------------------------------------------- staff

    public function staff(): array
    {
        return $this->memo['staff'] ??= $this->buildStaff();
    }

    private function buildStaff(): array
    {
        $assigned = [];
        foreach (self::SECTIONS as $section) {
            foreach (array_keys(self::SUBJECT_TEACHERS) as $subject) {
                $assigned[$this->teacherFor($subject, $section)][] = $section;
            }
        }

        $staff = [];
        foreach (self::STAFF as $id => [$name, $designation, $department, $subjects]) {
            $onLeave = $this->isOnLeave($id, $this->now);
            $classes = $assigned[$id] ?? [];

            $staff[] = [
                'id' => $id,
                'name' => $name,
                'designation' => $designation,
                'department' => $department,
                'subjects' => $subjects,
                'classes' => $classes === [] ? ($id === 'STF007' ? 'All classes' : '6-9') : implode(', ', array_unique($classes)),
                'status' => $onLeave ? 'On leave' : 'Present',
                'employment' => 'Active',
            ];
        }

        return $staff;
    }

    // --------------------------------------------------------------- timetable

    public function timetableToday(): array
    {
        return $this->timetableForDate($this->now->toDateString());
    }

    public function timetableForDate(string $date): array
    {
        $day = CarbonImmutable::parse($date, $this->now->getTimezone())->startOfDay();

        return $this->memo["timetable:{$day->toDateString()}"] ??= $this->buildTimetable($day);
    }

    private function buildTimetable(CarbonImmutable $day): array
    {
        if ($day->isSunday()) {
            return [];
        }

        $dow = $day->dayOfWeekIso;
        $today = $this->now->toDateString();
        $date = $day->toDateString();
        $clock = $this->now->format('H:i');
        $rows = [];

        foreach (self::SECTIONS as $k => $section) {
            foreach (self::SLOTS as $s => [$start, $end]) {
                $subject = self::TIMETABLE_SUBJECTS[($s + $k + $dow) % count(self::TIMETABLE_SUBJECTS)];
                $teacherId = $this->teacherFor($subject, $section);

                $rows[] = [
                    'time' => "{$start}–{$end}",
                    'start' => $start,
                    'end' => $end,
                    'class' => $section,
                    'subject' => $subject,
                    'teacher' => self::STAFF[$teacherId][0],
                    'teacher_id' => $teacherId,
                    'teacher_on_leave' => $this->isOnLeave($teacherId, $day),
                    'room' => match ($subject) {
                        'Computer Science' => 'Computer Lab',
                        'Science' => 'Lab 0' . ($k % 4 + 1),
                        default => 'Room ' . sprintf('%02d', 6 + $k),
                    },
                    'status' => match (true) {
                        $date < $today => 'Completed',
                        $date > $today => 'Scheduled',
                        $clock >= $end => 'Completed',
                        $clock >= $start => 'In progress',
                        default => 'Upcoming',
                    },
                ];
            }
        }

        return $rows;
    }

    /** Same rule for every date, so the leave shown for "next Tuesday" is stable. */
    private function isOnLeave(string $staffId, CarbonImmutable $day): bool
    {
        return $staffId !== 'STF007' && ! $day->isSunday() && $this->roll("leave|{$staffId}|{$day->toDateString()}") < 0.08;
    }

    // ---------------------------------------------------------------- syllabus

    public function syllabus(): array
    {
        return $this->memo['syllabus'] ??= $this->buildSyllabus();
    }

    private function buildSyllabus(): array
    {
        $start = $this->academicStart();
        $end = $start->addMonths(9); // ~1 March
        $elapsed = max(0, min(1, $start->diffInDays($this->now) / max(1, $start->diffInDays($end))));
        $expected = (int) round($elapsed * 100);
        $rows = [];

        foreach (self::SECTIONS as $section) {
            foreach (self::CORE_SUBJECTS as $subject) {
                $pace = 0.8 + $this->roll("pace|{$section}|{$subject}") * 0.35;
                $completion = (int) min(100, round($expected * $pace));
                $index = (int) min(9, floor($completion / 10));
                $gap = $expected - $completion;

                $rows[] = [
                    'class' => $section,
                    'subject' => $subject,
                    'chapter' => 'Unit ' . ($index + 1) . ' · ' . self::CHAPTERS[$subject][$index],
                    'topic' => $completion % 10 >= 7 ? 'Practice & unit test' : 'Concept teaching',
                    'completion' => $completion,
                    'expected' => $expected,
                    'status' => $completion >= 100 ? 'Completed' : ($gap > 8 ? 'Needs review' : 'In progress'),
                    'target' => "{$expected}% expected by today",
                ];
            }
        }

        return $rows;
    }

    // ------------------------------------------------------------ examinations

    public function examinations(): array
    {
        return $this->memo['exams'] ??= $this->buildExams();
    }

    private function buildExams(): array
    {
        $start = $this->academicStart();
        $plans = [
            ['Periodic Assessment 1', $start->addDays(149), 40],
            ['Half-Yearly Examination', $start->addDays(170), 80],
            ['Periodic Assessment 2', $start->addDays(233), 40],
            ['Annual Examination', $start->addDays(274), 80],
        ];
        $today = $this->now->startOfDay();
        $rows = [];

        foreach ($plans as [$exam, $firstDay, $maxMarks]) {
            foreach (self::SECTIONS as $section) {
                foreach (self::CORE_SUBJECTS as $i => $subject) {
                    $date = $firstDay->addDays($i);
                    if ($date->isSunday()) {
                        $date = $date->addDay();
                    }
                    $teacherId = $this->teacherFor($subject, $section);
                    $isB = str_ends_with($section, 'B');

                    if ($date->greaterThanOrEqualTo($today)) {
                        $status = 'Scheduled';
                    } else {
                        $since = (int) $date->diffInDays($today);
                        $lag = 4 + (int) ($this->roll("pub|{$exam}|{$section}|{$subject}") * 10);
                        $status = $since >= $lag ? 'Published' : ($since >= $lag - 2 ? 'Moderation' : 'Marks entry');
                    }

                    $rows[] = [
                        'exam' => $exam,
                        'class' => $section,
                        'subject' => $subject,
                        'date' => $date->toDateString(),
                        'start' => $maxMarks === 40 ? ($isB ? '11:00' : '09:00') : '09:00',
                        'end' => $maxMarks === 40 ? ($isB ? '12:00' : '10:00') : '12:00',
                        'max_marks' => $maxMarks,
                        'teacher' => self::STAFF[$teacherId][0],
                        'teacher_id' => $teacherId,
                        'room' => 'Room ' . sprintf('%02d', 6 + array_search($section, self::SECTIONS, true)),
                        'status' => $status,
                    ];
                }
            }
        }

        return $rows;
    }

    // ----------------------------------------------------------------- helpers

    private function academicStart(): CarbonImmutable
    {
        $year = $this->now->month >= 6 ? $this->now->year : $this->now->year - 1;

        return CarbonImmutable::create($year, 6, 1, 0, 0, 0, $this->now->getTimezone());
    }

    private function teacherFor(string $subject, string $section): string
    {
        $list = self::SUBJECT_TEACHERS[$subject];
        [$class, $sec] = explode('-', $section);

        return $list[((int) $class + ($sec === 'B' ? 1 : 0)) % count($list)];
    }

    /** Deterministic pseudo-random number in [0, 1) for a given key. */
    private function roll(string $key): float
    {
        return hexdec(substr(md5($key), 0, 8)) / 4294967296;
    }

    private function pick(array $items, string $key): string
    {
        return $items[(int) floor($this->roll($key) * count($items))];
    }
}
