<?php

namespace App\Filament\Pages;

use App\Services\Principal\Data\DemoSchoolDataSource;
use App\Services\Principal\Insights\InsightEngine;
use App\Services\Teacher\TeacherCopilot;
use App\Services\Teacher\TeacherWorkspace;
use App\Services\Teacher\WorksheetGenerator;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Url;

class TeacherDashboard extends Page
{
    protected string $view = 'filament.pages.teacher-dashboard';

    protected static ?string $slug = 'teacher-dashboard';

    protected static ?string $navigationLabel = 'Teacher Dashboard';

    protected static ?string $title = null;

    protected static string|\UnitEnum|null $navigationGroup = 'School Management';

    protected static ?int $navigationSort = 4;

    #[Url(as: 'tab')]
    public string $activeTab = 'today';

    public string $question = '';

    public string $copilotAnswer = '';

    public string $copilotError = '';

    /** Section shown on Attendance / Progress / Worksheet */
    public string $attendanceSection = '';

    public string $progressSection = '';

    public string $worksheetSection = '';

    public string $worksheetTopic = '';

    public bool $showWorksheet = false;

    /** Editable questions: ['Support' => [...], 'Core' => [...], 'Challenge' => [...]] */
    public array $worksheetItems = [];

    /** Answer key, same shape as $worksheetItems */
    public array $worksheetAnswers = [];

    /** 'ai' | 'template' | '' */
    public string $worksheetSource = '';

    public string $worksheetNote = '';

    /** Questions per level (2–5) */
    public int $worksheetCount = 3;

    public bool $showAnswers = false;

    /** studentId => present | absent | late */
    public array $attendance = [];

    /** Timetable date (Y-m-d); empty = today */
    #[Url(as: 'date')]
    public string $ttDate = '';

    public string $scanMessage = '';

    private ?TeacherWorkspace $wsInstance = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'teacher', 'class_teacher'], true);
    }

    public function mount(): void
    {
        $ws = $this->ws();
        $home = $ws->classTeacherOf() ?? ($ws->sections()[0] ?? '8-A');
        $this->attendanceSection = $home;
        $this->progressSection = $home;
        $this->worksheetSection = $home;
        $this->worksheetTopic = $ws->worksheetPlan($home)['topic'];
        $this->loadRegister();
    }

    // ================================================================== brain

    private function ws(): TeacherWorkspace
    {
        if ($this->wsInstance) {
            return $this->wsInstance;
        }
        $engine = app()->bound(InsightEngine::class)
            ? app(InsightEngine::class)
            : new InsightEngine(new DemoSchoolDataSource(), cache()->store(), config('principal-insights', []));

        // Backend: map the logged-in user to their staff record (e.g. users.staff_id).
        return $this->wsInstance = new TeacherWorkspace($engine, auth()->user()?->name);
    }

    private function userKey(): string
    {
        return (string) (auth()->id() ?? 'guest');
    }

    // ============================================================= navigation

    public function setTab(string $tab, ?string $section = null): void
    {
        if (! in_array($tab, ['today', 'classes', 'attendance', 'progress', 'homework', 'worksheet', 'scan', 'timetable'], true)) {
            return;
        }
        $this->activeTab = $tab;
        if ($section && in_array($section, $this->ws()->sections(), true)) {
            match ($tab) {
                'attendance' => [$this->attendanceSection = $section, $this->loadRegister()],
                'progress' => $this->progressSection = $section,
                'worksheet' => $this->useWorksheetPlan($section),
                default => null,
            };
        }
    }

    // ============================================================= attendance

    private function registers(): array
    {
        return cache()->get("teacher-ws:{$this->userKey()}:registers:" . $this->ws()->data()->now()->toDateString(), []);
    }

    private function loadRegister(): void
    {
        $saved = $this->registers()[$this->attendanceSection]['marks'] ?? [];
        $this->attendance = [];
        foreach ($this->ws()->register($this->attendanceSection) as $row) {
            $this->attendance[$row['id']] = $saved[$row['id']] ?? 'present';
        }
    }

    public function updatedAttendanceSection(): void
    {
        $this->loadRegister();
    }

    public function markAll(string $status): void
    {
        if (in_array($status, ['present', 'absent', 'late'], true)) {
            foreach (array_keys($this->attendance) as $id) {
                $this->attendance[$id] = $status;
            }
        }
    }

    public function setMark(string $studentId, string $status): void
    {
        if (isset($this->attendance[$studentId]) && in_array($status, ['present', 'absent', 'late'], true)) {
            $this->attendance[$studentId] = $status;
        }
    }

    public function submitAttendance(): void
    {
        // TODO backend: save to the attendance table for today's date and this section.
        $key = "teacher-ws:{$this->userKey()}:registers:" . $this->ws()->data()->now()->toDateString();
        $all = cache()->get($key, []);
        $all[$this->attendanceSection] = ['marks' => $this->attendance, 'at' => now()->timestamp];
        cache()->put($key, $all, now()->endOfDay());

        $absent = count(array_filter($this->attendance, fn ($m) => $m === 'absent'));
        Notification::make()->title("{$this->attendanceSection} register submitted (demo)")
            ->body(count($this->attendance) - $absent . ' present · ' . $absent . ' absent')->success()->send();
    }

    public function notifyAbsentParents(): void
    {
        // TODO backend: send SMS/WhatsApp to parents of absent students.
        $absent = count(array_filter($this->attendance, fn ($m) => $m === 'absent'));
        Notification::make()->title("Parents of {$absent} absent " . ($absent === 1 ? 'student' : 'students') . ' notified (demo)')->success()->send();
    }

    // ======================================================== homework / marks

    public function remindHomework(string $homeworkId): void
    {
        $h = collect($this->ws()->homework())->firstWhere('id', $homeworkId);
        // TODO backend: notify students/parents who have not submitted.
        Notification::make()->title('Reminder sent (demo)')->body($h ? count($h['missing']) . " students · {$h['title']}" : null)->success()->send();
    }

    public function startMarksEntry(string $class, string $subject): void
    {
        // TODO backend: open the marks-entry / OCR screen for this paper.
        $this->scanMessage = "Marks entry for {$class} {$subject}: upload the scanned answer sheets or enter marks manually. (OCR is not connected yet.)";
    }

    // ============================================================== worksheet

    public function useWorksheetPlan(string $section): void
    {
        $this->worksheetSection = $section;
        $this->worksheetTopic = $this->ws()->worksheetPlan($section)['topic'];
        $this->showWorksheet = false;
        $this->worksheetItems = [];
        $this->worksheetAnswers = [];
        $this->worksheetSource = '';
        $this->worksheetNote = '';
    }

    public function updatedWorksheetSection(): void
    {
        $this->useWorksheetPlan($this->worksheetSection);
    }

    /** Creates draft questions; the teacher then edits, adds or removes them before printing. */
    public function generateWorksheet(): void
    {
        $ws = $this->ws();
        $plan = $ws->worksheetPlan($this->worksheetSection);
        $count = max(2, min(5, $this->worksheetCount));
        $topic = trim($this->worksheetTopic) !== '' ? trim($this->worksheetTopic) : $plan['topic'];

        $result = (new WorksheetGenerator())->generate(
            $ws->subject(),
            $topic,
            (int) $this->worksheetSection,
            $count,
            array_map('count', $plan['groups']),
            $ws->worksheet($ws->subject(), $topic, $this->worksheetSection),
        );

        $this->worksheetItems = $result['questions'];
        $this->worksheetAnswers = $result['answers'];
        $this->worksheetSource = $result['source'];
        $this->worksheetNote = $result['note'];
        $this->showWorksheet = true;
    }

    public function addQuestion(string $level): void
    {
        if (isset($this->worksheetItems[$level])) {
            $this->worksheetItems[$level][] = '';
            $this->worksheetAnswers[$level][] = '';
        }
    }

    public function removeQuestion(string $level, int $index): void
    {
        if (isset($this->worksheetItems[$level][$index])) {
            unset($this->worksheetItems[$level][$index], $this->worksheetAnswers[$level][$index]);
            $this->worksheetItems[$level] = array_values($this->worksheetItems[$level]);
            $this->worksheetAnswers[$level] = array_values($this->worksheetAnswers[$level] ?? []);
        }
    }

    // ============================================================== timetable

    public function shiftTtDate(int $days): void
    {
        $d = $this->ttCarbon()->addDays($days > 0 ? 1 : -1);
        if ($d->isSunday()) {
            $d = $d->addDays($days > 0 ? 1 : -1);
        }
        $this->ttDate = $d->toDateString();
    }

    public function resetTtDate(): void
    {
        $this->ttDate = '';
    }

    private function ttCarbon(): \Carbon\CarbonImmutable
    {
        $today = $this->ws()->data()->now()->startOfDay();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->ttDate)) {
            return $today;
        }
        try {
            return \Carbon\CarbonImmutable::parse($this->ttDate, $today->getTimezone())->startOfDay();
        } catch (\Throwable) {
            return $today;
        }
    }

    // ================================================================ copilot

    public function askSuggested(string $q): void
    {
        $this->question = $q;
        $this->askCopilot();
    }

    public function askCopilot(): void
    {
        $question = trim($this->question);
        $this->copilotError = '';
        $this->copilotAnswer = '';
        if ($question === '') {
            $this->copilotError = 'Type a question first, or type “help”.';

            return;
        }

        $result = (new TeacherCopilot($this->ws(), $this->registers()))->answer(mb_substr($question, 0, 500));
        if ($result) {
            $this->copilotAnswer = $result['answer'];
            if ($result['tab']) {
                $this->activeTab = $result['tab'];
            }
            foreach ($result['state'] as $k => $v) {
                if (in_array($k, ['worksheetTopic', 'worksheetSection', 'progressSection', 'ttDate'], true)) {
                    $this->{$k} = $v;
                }
            }
            if (! empty($result['state']['showWorksheet'])) {
                $this->generateWorksheet();
            }

            return;
        }

        try {
            // ONE user message — the AI service rejects "system" messages.
            $reply = (new \App\Services\Ai\AssistantClient())->ask(
                'You are a helpful assistant for a school teacher. Be brief and practical. Do not share fee or financial details.',
                $question,
            );
            $this->copilotAnswer = $reply !== '' ? $reply : 'I could not find that in your class data. Type “help” to see what I can answer.';
        } catch (\Throwable $e) {
            report($e);
            $this->copilotAnswer = 'I could not find that in your class data. Type “help” to see what I can answer.';
        }
    }

    public function clearCopilot(): void
    {
        $this->question = '';
        $this->copilotAnswer = '';
        $this->copilotError = '';
    }

    // ============================================================== view data

    protected function getViewData(): array
    {
        $ws = $this->ws();
        $registers = $this->registers();
        $day = $ws->day();
        $ttDate = $this->ttCarbon();
        $homework = $ws->homework();
        $queue = $ws->marksEntryQueue();
        $attention = $ws->attention($registers);
        $progress = $ws->progress($this->progressSection);

        return [
            'teacher' => $ws->teacher(),
            'subject' => $ws->subject(),
            'mySections' => $ws->sections(),
            'classTeacherOf' => $ws->classTeacherOf(),
            'now' => $ws->data()->now(),
            'day' => $day,
            'prep' => $ws->prep($day['current'] ?? $day['next']),
            'attention' => $attention,
            'classCards' => $ws->classCards(),
            'registerRows' => $ws->register($this->attendanceSection),
            'registerSubmitted' => $registers[$this->attendanceSection] ?? null,
            'progressRows' => $progress,
            'homework' => $homework,
            'worksheetPlan' => $ws->worksheetPlan($this->worksheetSection),
            'marksQueue' => $queue,
            'ttDay' => $ws->day($ttDate->toDateString()),
            'ttDateObj' => $ttDate,
            'ttWeek' => $ws->week($ttDate->toDateString()),
            'kpis' => [
                'periods' => count($day['periods']),
                'done' => $day['done'],
                'watch' => count(array_filter($progress, fn ($r) => $r['signal_type'] !== 'ok')),
                'marks' => count(array_filter($queue, fn ($e) => $e['status'] === 'Marks entry')),
                'homework' => array_sum(array_map(fn ($h) => count($h['missing']), array_filter($homework, fn ($h) => $h['due_in'] <= 2))),
            ],
            'badges' => [
                'today' => count(array_filter($attention, fn ($a) => $a['level'] === 'risk')),
                'attention' => count($attention),
                'scan' => count(array_filter($queue, fn ($e) => $e['status'] === 'Marks entry')),
            ],
        ];
    }
}
