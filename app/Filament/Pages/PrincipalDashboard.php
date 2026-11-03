<?php

namespace App\Filament\Pages;

use App\Services\Principal\Copilot\PrincipalCopilot;
use App\Services\Principal\Data\DemoSchoolDataSource;
use App\Services\Principal\Data\SchoolDataSource;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\InsightEngine;
use App\Services\Principal\Insights\InsightReport;
use App\Services\Principal\Insights\WorkspaceData;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class PrincipalDashboard extends Page
{
    protected string $view = 'filament.pages.principal-dashboard';

    protected static ?string $slug = 'principal-dashboard';

    protected static ?string $navigationLabel = 'Principal Dashboard';

    protected static ?string $title = null;

    protected static string|\UnitEnum|null $navigationGroup = 'School Management';

    protected static ?int $navigationSort = 2;

    // #[Url] keeps the open tab in the address bar, so refresh / shared links open the same screen.
    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public string $search = '';

    public string $headerQuery = '';

    public string $timetableClass = '6';

    public string $timetableSection = 'A';

    /** Timetable date (Y-m-d). Empty = today. Kept in the URL so a review link opens the same day. */
    #[Url(as: 'date')]
    public string $timetableDate = '';

    public string $syllabusClass = '6';

    public string $syllabusSection = 'A';

    public string $staffSearch = '';

    public string $examinationClass = '6';

    public string $examinationSection = 'A';

    public string $examinationName = 'Half-Yearly Examination';

    #[Url(as: 'exam')]
    public string $examinationView = 'exams';

    public string $examNotice = '';

    public string $copilotAnswer = '';

    public string $copilotError = '';

    public string $classFilter = 'all';

    public ?string $selectedStudentId = null;

    /** all | new | 60 */
    public string $feeFilter = 'all';

    /** sections | defaulters | streaks */
    public string $attendanceFilter = 'sections';

    // Marks tab filters: class 'all' or '6'..'9', section 'all' or 'A'/'B', low-marks line in %.
    #[Url(as: 'mclass')]
    public string $marksClass = 'all';

    #[Url(as: 'msec')]
    public string $marksSection = 'all';

    public int $marksThreshold = 40;

    /** Approvals filter: all | results | fees | leave */
    public string $approvalFilter = 'all';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'principal'], true);
    }

    public function mount(): void
    {
        // Freeze "what you saw last time", build today's report, then remember it for next time.
        $engine = $this->engine();
        $engine->beginVisit($this->userKey());
        $engine->saveSnapshot($this->userKey(), $this->report);
    }

    // ===================================================================== brain

    /** Built once per request. Not saved between requests, so every click re-analyses fresh data. */
    private ?InsightEngine $engineInstance = null;

    private function engine(): InsightEngine
    {
        // Works without any AppServiceProvider change. If you later bind InsightEngine
        // (e.g. to switch to real database data), that binding is used automatically.
        return $this->engineInstance ??= app()->bound(InsightEngine::class)
            ? app(InsightEngine::class)
            : new InsightEngine(new DemoSchoolDataSource(), cache()->store(), config('principal-insights', []));
    }

    private function data(): SchoolDataSource
    {
        return $this->engine()->data();
    }

    private function userKey(): int|string
    {
        return auth()->id() ?? 'guest';
    }

    /** Re-computed on every request — so every launch and every click reflects the current data. */
    #[Computed]
    public function report(): InsightReport
    {
        $name = trim((string) (auth()->user()?->name ?? 'Principal'));

        return $this->engine()->report($this->userKey(), explode(' ', $name)[0] ?: 'Principal');
    }

    #[Computed]
    public function feeSummary(): array
    {
        return $this->engine()->fees->summary($this->data());
    }

    #[Computed]
    public function attendanceSummary(): array
    {
        return $this->engine()->attendance->summary($this->data());
    }

    /** Marks for the chosen class/section — recalculated whenever a filter changes. */
    #[Computed]
    public function marksReport(): array
    {
        return $this->engine()->marks->summary($this->data(), $this->marksScope(), max(10, min(90, $this->marksThreshold)));
    }

    public function marksScope(): string
    {
        if ($this->marksClass === 'all') {
            return 'all';
        }

        return $this->marksSection === 'all' ? $this->marksClass : $this->marksClass . '-' . $this->marksSection;
    }

    /** Click a cell in the heatmap → show that section. */
    public function focusMarks(string $section): void
    {
        if (preg_match('/^([6-9])-([AB])$/', $section, $m)) {
            [$this->marksClass, $this->marksSection] = [$m[1], $m[2]];
            $this->activeTab = 'marks';
        }
    }

    public function updatedMarksClass(): void
    {
        if ($this->marksClass === 'all') {
            $this->marksSection = 'all';
        }
    }

    // ------------------------------------------- Analytics / Approvals / Circulars

    #[Computed]
    public function analyticsData(): array
    {
        return (new WorkspaceData($this->engine()))->analytics($this->report);
    }

    #[Computed]
    public function approvalsData(): array
    {
        return (new WorkspaceData($this->engine()))->approvals($this->workspaceStore('decisions'));
    }

    #[Computed]
    public function circularsData(): array
    {
        return (new WorkspaceData($this->engine()))->circulars($this->report, $this->workspaceStore('circulars'));
    }

    /** Approve / decline / ask for info. Saved per user (cache) so the item leaves the inbox. */
    public function decideApproval(string $id, string $decision): void
    {
        if (! in_array($decision, ['approved', 'declined', 'info'], true)) {
            return;
        }
        $item = collect($this->approvalsData['items'])->firstWhere('id', $id);
        if (! $item) {
            return;
        }

        // TODO backend: save the decision to the database and notify the requester.
        if ($decision !== 'info') {
            $this->workspacePut('decisions', $id, ['decision' => $decision, 'at' => now()->timestamp, 'title' => $item['title']]);
        }
        unset($this->approvalsData);

        Notification::make()
            ->title(match ($decision) { 'approved' => 'Approved', 'declined' => 'Declined', default => 'Asked for more information' } . ' (demo)')
            ->body($item['title'])
            ->{$decision === 'declined' ? 'warning' : 'success'}()
            ->send();
    }

    public function setApprovalFilter(string $filter): void
    {
        $this->approvalFilter = in_array($filter, ['all', 'results', 'fees', 'leave'], true) ? $filter : 'all';
    }

    public function sendCircular(string $id): void
    {
        $draft = collect($this->circularsData['drafts'])->firstWhere('id', $id);
        if (! $draft) {
            return;
        }
        // TODO backend: queue SMS / WhatsApp / app / email to the audience.
        $this->workspacePut('circulars', $id, ['status' => 'sent', 'at' => now()->timestamp, 'title' => $draft['title'], 'audience' => $draft['audience']]);
        unset($this->circularsData);
        Notification::make()->title('Circular queued (demo)')->body("{$draft['title']} · {$draft['audience']} parents")->success()->send();
    }

    public function dismissCircular(string $id): void
    {
        $this->workspacePut('circulars', $id, ['status' => 'dismissed', 'at' => now()->timestamp]);
        unset($this->circularsData);
    }

    private function workspaceStore(string $name): array
    {
        return cache()->get("principal-workspace:{$this->userKey()}:{$name}", []);
    }

    private function workspacePut(string $name, string $id, array $value): void
    {
        $all = $this->workspaceStore($name);
        $all[$id] = $value;
        cache()->put("principal-workspace:{$this->userKey()}:{$name}", $all, now()->addDays(30));
    }

    #[Computed]
    public function substitution(): array
    {
        return $this->engine()->substitution->gaps($this->data());
    }

    // ============================================================ insight actions

    public function refreshInsights(): void
    {
        unset($this->report, $this->feeSummary, $this->attendanceSummary, $this->substitution, $this->marksReport, $this->analyticsData, $this->approvalsData, $this->circularsData);
    }

    /** Primary button on an insight card → jump to the right screen with the right filter. */
    public function openInsight(string $key): void
    {
        $insight = $this->report->find($key);
        if (! $insight || ! $insight->actionTab) {
            return;
        }

        $this->setTab($insight->actionTab);
        $params = $insight->actionParams;

        if (isset($params['filter']) && $insight->actionTab === 'fees') {
            $this->feeFilter = $params['filter'];
        }
        if (isset($params['filter']) && $insight->actionTab === 'attendance') {
            $this->attendanceFilter = $params['filter'];
        }
        if (isset($params['view'])) {
            $this->setExaminationView($params['view']);
        }
        if (isset($params['section'])) {
            $this->focusMarks($params['section']);
        }
    }

    public function dismissInsight(string $key): void
    {
        $insight = $this->report->find($key);
        if ($insight) {
            $this->engine()->dismiss($this->userKey(), $key, $insight->fingerprint());
            unset($this->report);
            Notification::make()->title('Hidden until something changes')->body('It will come back automatically if the numbers move.')->send();
        }
    }

    public function snoozeInsight(string $key): void
    {
        $this->engine()->snoozeUntilTomorrow($this->userKey(), $key);
        unset($this->report);
        Notification::make()->title('Snoozed until 8:00 tomorrow')->send();
    }

    public function restoreInsights(): void
    {
        $this->engine()->restoreHidden($this->userKey());
        unset($this->report);
    }

    // Demo actions — replace the bodies with real jobs (SMS/WhatsApp, timetable update) later.
    public function sendFeeReminder(string $studentId): void
    {
        $row = collect($this->feeSummary['defaulters'])->firstWhere('id', $studentId);
        Notification::make()
            ->title('Reminder queued (demo)')
            ->body($row ? "{$row['name']} · " . Format::inr($row['amount']) . ' outstanding' : null)
            ->success()->send();
    }

    public function assignSubstitute(string $class, string $start): void
    {
        $gap = collect($this->substitution['gaps'])->first(fn ($g) => $g['class'] === $class && $g['start'] === $start);
        Notification::make()
            ->title($gap && $gap['suggested'] ? "{$gap['suggested']} assigned to {$class} at {$start} (demo)" : 'No free teacher found')
            ->success()->send();
    }

    public function markFamilyCalled(string $studentId): void
    {
        Notification::make()->title('Call logged (demo)')->body("Follow-up recorded for {$studentId}.")->success()->send();
    }

    // ================================================================ search

    public function findStudents(): void
    {
        $this->search = trim($this->headerQuery);
        $this->classFilter = 'all';
        $this->selectedStudentId = null;
        $this->activeTab = 'students';
        $this->copilotError = '';
    }

    public function viewSearchResult(string $studentId): void
    {
        $this->showStudentStrategy($studentId);
        $this->headerQuery = '';
    }

    // =============================================================== copilot

    /** Quick-question chips under the search bar call this. */
    public function askSuggested(string $question): void
    {
        $this->headerQuery = $question;
        $this->askCopilot();
    }

    public function askCopilot(): void
    {
        $question = trim($this->headerQuery);
        $this->copilotError = '';
        $this->copilotAnswer = '';

        if ($question === '') {
            $this->copilotError = 'Type a question first, or type “help” to see examples.';

            return;
        }

        // 1. Understand the question and answer from live school data (fast, no AI needed).
        $copilot = new PrincipalCopilot($this->engine());
        $result = $copilot->answer(mb_substr($question, 0, 500));

        if ($result !== null) {
            $this->copilotAnswer = $result['answer'];
            $this->applyCopilotState($result['tab'], $result['state']);

            return;
        }

        // 2. Anything else → the AI model, grounded in today's analysis.
        try {
            // ONE user message (instructions + today's data + question, max 2,000 chars) — the AI service rejects "system" messages.
            $reply = (new \App\Services\Ai\AssistantClient())->ask(
                "You are the principal's assistant in a school ERP. Answer briefly using ONLY the school data below from today's analysis. If the data does not contain the answer, say so.",
                $question,
                $this->report->asContext(12),
            );
            $this->copilotAnswer = $reply !== '' ? $reply : $copilot->notUnderstood($question);
        } catch (\Throwable $exception) {
            report($exception); // details go to the log, not to the screen
            $this->copilotAnswer = $copilot->notUnderstood($question);
        }
    }

    /** Open the tab the answer belongs to, with the same filters, so the screen matches the answer. */
    private function applyCopilotState(?string $tab, array $state): void
    {
        if (isset($state['student'])) {
            $this->showStudentStrategy($state['student']);
            unset($state['student']);
        } elseif ($tab) {
            $this->setTab($tab);
        }

        $allowed = ['marksClass', 'marksSection', 'marksThreshold', 'feeFilter', 'attendanceFilter', 'staffSearch',
            'timetableClass', 'timetableSection', 'syllabusClass', 'syllabusSection',
            'examinationClass', 'examinationSection', 'examinationName', 'examinationView'];
        foreach ($state as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $this->{$key} = $value;
            }
        }

        unset($this->marksReport, $this->feeSummary, $this->attendanceSummary);
    }

    public function clearCopilotAnswer(): void
    {
        $this->copilotAnswer = '';
        $this->copilotError = '';
    }

    // ============================================================ navigation

    public function setExaminationView(string $view): void
    {
        if (in_array($view, ['exams', 'schedule', 'marks', 'results', 'grade-scale'], true)) {
            $this->examinationView = $view;
            $this->examNotice = '';
        }
    }

    public function requestNewExamPlan(): void
    {
        $this->examNotice = 'Demo preview only: exam creation and publishing are not connected to the school database yet. No record has been saved.';
        $this->examinationView = 'exams';
    }

    public function viewExamPlan(string $examName): void
    {
        $rows = $this->data()->examinations();
        if (! in_array($examName, array_column($rows, 'exam'), true)) {
            return;
        }

        $this->examinationName = $examName;
        $first = collect($rows)->firstWhere('exam', $examName);
        [$this->examinationClass, $this->examinationSection] = explode('-', $first['class'], 2);
        $this->examinationView = 'schedule';
        $this->examNotice = '';
        $this->activeTab = 'examinations';
    }

    public function setTab(string $tab): void
    {
        $allowed = ['overview', 'approvals', 'analytics', 'students', 'staff', 'risk', 'attendance', 'marks', 'examinations', 'fees', 'timetable', 'syllabus', 'circulars'];

        if (in_array($tab, $allowed, true)) {
            $this->activeTab = $tab;
        }
    }

    // ------------------------------------------------------------ timetable date

    /** The date being reviewed; invalid or empty input falls back to today. */
    public function selectedTimetableDate(): \Carbon\CarbonImmutable
    {
        $today = $this->data()->now()->startOfDay();
        if ($this->timetableDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->timetableDate)) {
            return $today;
        }
        try {
            return \Carbon\CarbonImmutable::parse($this->timetableDate, $today->getTimezone())->startOfDay();
        } catch (\Throwable) {
            return $today;
        }
    }

    /** ‹ Previous / Next › school day (skips Sundays). */
    public function shiftTimetableDate(int $days): void
    {
        $date = $this->selectedTimetableDate()->addDays($days > 0 ? 1 : -1);
        if ($date->isSunday()) {
            $date = $date->addDays($days > 0 ? 1 : -1);
        }
        $this->timetableDate = $date->toDateString();
    }

    public function resetTimetableDate(): void
    {
        $this->timetableDate = '';
    }

    public function setFeeFilter(string $filter): void
    {
        $this->feeFilter = in_array($filter, ['all', 'new', '60'], true) ? $filter : 'all';
    }

    public function setAttendanceFilter(string $filter): void
    {
        $this->attendanceFilter = in_array($filter, ['sections', 'defaulters', 'streaks'], true) ? $filter : 'sections';
    }

    public function showStudentStrategy(string $studentId): void
    {
        if (collect($this->demoStudents())->contains('id', $studentId)) {
            $this->selectedStudentId = $studentId;
            $this->activeTab = 'students';
        }
    }

    public function closeStudentStrategy(): void
    {
        $this->selectedStudentId = null;
    }

    // ============================================================= view data

    protected function getViewData(): array
    {
        $allStudents = $this->demoStudents();
        $classes = array_values(array_unique(array_column($allStudents, 'class')));
        sort($classes, SORT_NATURAL);

        $query = mb_strtolower(trim($this->search));
        $students = array_values(array_filter($allStudents, function (array $student) use ($query): bool {
            $matchesSearch = $query === ''
                || str_contains(mb_strtolower($student['name']), $query)
                || str_contains(mb_strtolower($student['id']), $query)
                || str_contains(mb_strtolower($student['class']), $query);

            return $matchesSearch
                && ($this->classFilter === 'all' || $student['class'] === $this->classFilter)
                && ($this->activeTab !== 'risk' || $student['signal_type'] !== 'ok');
        }));

        $selectedStudent = collect($allStudents)->firstWhere('id', $this->selectedStudentId);

        $headerQuery = mb_strtolower(trim($this->headerQuery));
        $headerSearchResults = $headerQuery === '' ? [] : array_slice(array_values(array_filter(
            $allStudents,
            fn (array $s): bool => str_contains(mb_strtolower($s['name'] . ' ' . $s['id'] . ' ' . $s['class'] . ' ' . $s['signal']), $headerQuery),
        )), 0, 5);

        $schoolClasses = ['6', '7', '8', '9'];
        $sections = ['A', 'B'];
        // Timetable for the chosen date + the whole week (Mon–Sat) for the chosen section.
        $ttDate = $this->selectedTimetableDate();
        $ttSection = $this->timetableClass . '-' . $this->timetableSection;
        $timetableEntries = array_values(array_filter($this->data()->timetableForDate($ttDate->toDateString()), fn ($r) => $r['class'] === $ttSection));
        $weekStart = $ttDate->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $timetableWeek = [];
        for ($i = 0; $i < 6; $i++) {
            $day = $weekStart->addDays($i);
            $timetableWeek[$day->toDateString()] = [
                'label' => $day->format('D d M'),
                'periods' => array_column(array_filter($this->data()->timetableForDate($day->toDateString()), fn ($r) => $r['class'] === $ttSection), null, 'start'),
            ];
        }
        $timetableSlots = array_values(array_unique(array_merge(...array_map(fn ($d) => array_keys($d['periods']), array_values($timetableWeek)))));
        sort($timetableSlots);
        $syllabusItems = array_values(array_filter($this->demoSyllabus(), fn ($r) => $r['class'] === $this->syllabusClass . '-' . $this->syllabusSection));

        $staffQuery = mb_strtolower(trim($this->staffSearch));
        $staffMembers = array_values(array_filter($this->demoStaff(), function (array $m) use ($staffQuery): bool {
            return $staffQuery === '' || str_contains(mb_strtolower(implode(' ', [$m['id'], $m['name'], $m['designation'], $m['department'], $m['subjects'], $m['classes']])), $staffQuery);
        }));

        $staffSummary = [
            'total' => count($staffMembers),
            'present' => count(array_filter($staffMembers, fn ($m) => $m['status'] === 'Present')),
            'onLeave' => count(array_filter($staffMembers, fn ($m) => $m['status'] === 'On leave')),
            'active' => count(array_filter($staffMembers, fn ($m) => $m['employment'] === 'Active')),
        ];

        $allExams = $this->demoExaminations();
        $examinationNames = array_values(array_unique(array_column($allExams, 'exam')));
        $examRows = array_values(array_filter($allExams, fn ($r) => $r['class'] === $this->examinationClass . '-' . $this->examinationSection && $r['exam'] === $this->examinationName));
        $examSummary = [
            'papers' => count($examRows),
            'scheduled' => count(array_filter($examRows, fn ($r) => $r['status'] === 'Scheduled')),
            'marksEntry' => count(array_filter($examRows, fn ($r) => $r['status'] === 'Marks entry')),
            'published' => count(array_filter($examRows, fn ($r) => $r['status'] === 'Published')),
        ];

        $examPlanMap = [];
        foreach ($allExams as $row) {
            $key = $row['exam'];
            $examPlanMap[$key] ??= ['exam' => $key, 'start_date' => $row['date'], 'end_date' => $row['date'], 'classes' => [], 'papers' => 0, 'statuses' => []];
            $examPlanMap[$key]['start_date'] = min($examPlanMap[$key]['start_date'], $row['date']);
            $examPlanMap[$key]['end_date'] = max($examPlanMap[$key]['end_date'], $row['date']);
            $examPlanMap[$key]['classes'][$row['class']] = true;
            $examPlanMap[$key]['papers']++;
            $examPlanMap[$key]['statuses'][] = $row['status'];
        }
        $examPlans = array_values(array_map(function (array $plan): array {
            $statuses = $plan['statuses'];
            $allPublished = count(array_filter($statuses, fn ($s) => $s === 'Published')) === count($statuses);
            $needsReview = count(array_intersect($statuses, ['Marks entry', 'Moderation'])) > 0;
            $plan['class_count'] = count($plan['classes']);
            $plan['status'] = $allPublished ? 'Published' : ($needsReview ? 'Review needed' : 'Scheduled');
            unset($plan['classes'], $plan['statuses']);

            return $plan;
        }, $examPlanMap));
        usort($examPlans, fn ($a, $b) => strcmp($b['start_date'], $a['start_date']));

        return [
            'students' => $students,
            'classes' => $classes,
            'schoolClasses' => $schoolClasses,
            'sections' => $sections,
            'timetableEntries' => $timetableEntries,
            'ttDate' => $ttDate,
            'ttIsToday' => $ttDate->isSameDay($this->data()->now()),
            'timetableWeek' => $timetableWeek,
            'timetableSlots' => $timetableSlots,
            'syllabusItems' => $syllabusItems,
            'staffMembers' => $staffMembers,
            'staffSummary' => $staffSummary,
            'examRows' => $examRows,
            'examinationNames' => $examinationNames,
            'examSummary' => $examSummary,
            'examPlans' => $examPlans,
            'allExamRowsForView' => $allExams,
            'selectedStudent' => $selectedStudent,
            'headerSearchResults' => $headerSearchResults,
        ];
    }

    // ======================================================================
    //  Data access. These used to be hard-coded arrays; they now come from the
    //  SchoolDataSource (date-driven demo today, database later). The method
    //  names are unchanged so the rest of the page keeps working.
    // ======================================================================

    private function demoStudents(): array
    {
        return $this->engine()->students->roster($this->data());
    }

    private function demoStaff(): array
    {
        return $this->data()->staff();
    }

    private function demoTimetable(): array
    {
        return $this->data()->timetableToday();
    }

    private function demoSyllabus(): array
    {
        return $this->data()->syllabus();
    }

    private function demoExaminations(): array
    {
        return $this->data()->examinations();
    }
}
