<?php

namespace App\Filament\Pages;

use App\Services\Admin\AdminCopilot;
use App\Services\Admin\AdminWorkspace;
use App\Services\Admin\ImportAnalyzer;
use App\Services\Ai\AssistantClient;
use App\Services\Principal\Data\DemoSchoolDataSource;
use App\Services\Principal\Insights\Format;
use App\Services\Principal\Insights\InsightEngine;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class AdminDashboard extends Page
{
    protected string $view = 'filament.pages.admin-dashboard';

    protected static ?string $slug = 'admin-dashboard';

    protected static ?string $navigationLabel = 'Admin Dashboard';

    protected static ?string $title = null;

    protected static string|\UnitEnum|null $navigationGroup = 'School Management';

    protected static ?int $navigationSort = 1;

    public const TABS = ['home', 'admissions', 'students', 'fees', 'requests', 'staff', 'data', 'messages'];

    #[Url(as: 'tab')]
    public string $activeTab = 'home';

    // Copilot
    public string $question = '';

    public string $copilotAnswer = '';

    // Students
    public string $search = '';

    public string $studentFilter = 'all';   // all | risk | fees | attendance | records

    public ?string $profileId = null;

    // Admissions
    public string $admFilter = 'all';       // all | ready | docs | issues | offers | enquiry

    public string $admClass = 'all';

    // Fees
    public string $feeView = 'followup';    // followup | upi | classes

    public string $feeClass = 'all';

    // Requests
    public ?string $draftId = null;

    // Data
    public string $importText = '';

    public array $importResult = [];

    // Messages
    public string $audience = 'fees';

    public string $messageText = '';

    public string $messageNote = '';

    private ?AdminWorkspace $wsInstance = null;

    public static function canAccess(): bool
    {
        $role = strtolower(trim((string) auth()->user()?->role));

        return in_array($role, ['admin', 'school_admin', 'super_admin'], true);
    }

    public function mount(): void
    {
        if (! in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'home';
        }
        $this->ws()->beginVisit();
        $this->messageText = $this->ws()->audiences()[$this->audience]['template'];
    }

    // ================================================================ brain

    private function ws(): AdminWorkspace
    {
        if ($this->wsInstance) {
            return $this->wsInstance;
        }
        // Same school brain as Principal/Teacher. If you bind InsightEngine to real data later, this uses it.
        $engine = app()->bound(InsightEngine::class)
            ? app(InsightEngine::class)
            : new InsightEngine(new DemoSchoolDataSource(), cache()->store(), config('principal-insights', []));

        return $this->wsInstance = new AdminWorkspace($engine, cache()->store(), (string) (auth()->id() ?? 'guest'));
    }

    private function toast(string $title, string $body = '', string $type = 'success'): void
    {
        Notification::make()->title($title)->body($body)->{$type}()->send();
    }

    // =========================================================== navigation

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->activeTab = $tab;
            $this->profileId = null;
            $this->draftId = null;
        }
    }

    /** Buttons on "Needs your decision". */
    public function runDecision(string $key, string $action, string $title): void
    {
        if (str_starts_with($action, 'tab:')) {
            $this->setTab(substr($action, 4));

            return;
        }
        if (str_starts_with($action, 'msg:')) {
            $this->chooseAudience(substr($action, 4));
            $this->setTab('messages');

            return;
        }
        match ($action) {
            'confirm-upi' => $this->confirmAllUpi(),
            'verify-all' => $this->verifyAll(),
            default => null,
        };
        $this->ws()->markDone($key, $title);
    }

    // =============================================================== copilot

    public function ask(?string $preset = null): void
    {
        $question = trim($preset ?? $this->question);
        if ($question === '') {
            return;
        }
        $this->question = $question;
        $local = (new AdminCopilot($this->ws()))->answer($question);
        if ($local !== null) {
            $this->copilotAnswer = $local['answer'];
            if ($local['tab']) {
                $this->setTab($local['tab']);
            }
            foreach ($local['state'] as $prop => $value) {
                if (in_array($prop, ['profileId', 'feeView', 'feeClass', 'admFilter'], true)) {
                    $this->{$prop} = $value;
                }
            }

            return;
        }

        // Anything else → AI, grounded in today's office numbers (one user message, max 2,000 chars).
        $ws = $this->ws();
        $context = implode("\n", array_map(fn ($k) => "{$k['label']}: {$k['value']} " . ($k['sub'] ?? '') . " ({$k['note']})", $ws->kpis()))
            . "\n" . implode("\n", array_map(fn ($d) => "To do: {$d['title']} — {$d['detail']}", array_slice($ws->decisions(), 0, 6)));
        try {
            $reply = (new AssistantClient())->ask(
                "You are the school office administrator's assistant. Answer briefly and practically. Use the school data below when it helps; if it does not contain the answer, say so.",
                $question,
                $context,
            );
            $this->copilotAnswer = $reply !== '' ? $reply : 'I could not answer that. Type “help” to see what I can do.';
        } catch (\Throwable $e) {
            report($e);
            $this->copilotAnswer = 'I could not find that in the office data, and the AI service is not reachable right now. Type “help” for examples.';
        }
    }

    public function clearAnswer(): void
    {
        $this->copilotAnswer = '';
        $this->question = '';
    }

    // ============================================================ students

    public function openProfile(string $id): void
    {
        $this->profileId = $id;
    }

    /** From Certificates / Data: jump to the student's full profile. */
    public function openStudent(string $id): void
    {
        $this->setTab('students');
        $this->profileId = $id;
    }

    public function closeProfile(): void
    {
        $this->profileId = null;
    }

    public function setStudentFilter(string $f): void
    {
        $this->studentFilter = in_array($f, ['all', 'risk', 'fees', 'attendance', 'records'], true) ? $f : 'all';
    }

    // ========================================================== admissions

    public function setAdmFilter(string $f): void
    {
        $this->admFilter = in_array($f, ['all', 'ready', 'docs', 'issues', 'offers', 'enquiry'], true) ? $f : 'all';
    }

    public function verifyApplicant(string $id): void
    {
        $this->ws()->statePut('verified', $id, $this->ws()->now()->format('d M'));
        $this->toast('Application verified', 'Moved to Test / interview. (Demo — saved for this login only.)');
    }

    public function verifyAll(): void
    {
        $ready = $this->ws()->admissions()['ready'];
        foreach ($ready as $a) {
            $this->ws()->statePut('verified', $a['id'], $this->ws()->now()->format('d M'));
        }
        $this->toast(Format::plural(count($ready), 'application') . ' verified', 'Moved to Test / interview.');
    }

    // ================================================================= fees

    public function setFeeView(string $v): void
    {
        $this->feeView = in_array($v, ['followup', 'upi', 'classes'], true) ? $v : 'followup';
    }

    public function markReminded(string $studentId): void
    {
        $this->ws()->statePut('reminded', $studentId, $this->ws()->now()->toDateString());
        $this->toast('Reminder logged', 'This family will not be reminded again for 3 days. (Demo — no SMS sent.)');
    }

    public function decideUpi(string $ref, string $decision): void
    {
        $this->ws()->statePut('upi', $ref, $decision === 'confirm' ? 'Confirmed' : 'Rejected', true);
        $this->toast($decision === 'confirm' ? 'Payment confirmed against invoice' : 'Match rejected — moved to manual check');
    }

    public function confirmAllUpi(): void
    {
        $n = 0;
        foreach ($this->ws()->upi() as $l) {
            if ($l['status'] === 'Auto-matched' && ! $l['decided']) {
                $this->ws()->statePut('upi', $l['ref'], 'Confirmed', true);
                $n++;
            }
        }
        $this->toast(Format::plural($n, 'payment') . ' confirmed', 'Receipts can now be generated. (Demo — ledger not changed.)');
    }

    // ============================================================= requests

    public function showDraft(string $id): void
    {
        $this->draftId = $this->draftId === $id ? null : $id;
    }

    public function issueRequest(string $id): void
    {
        $this->ws()->statePut('issued', $id, $this->ws()->now()->format('d M'));
        $this->draftId = null;
        $this->toast('Marked as issued', 'Print the draft and hand it over. (Demo.)');
    }

    // ================================================================= data

    public function loadSample(): void
    {
        $this->importText = ImportAnalyzer::SAMPLE;
        $this->checkImport();
    }

    public function checkImport(): void
    {
        $this->importResult = (new ImportAnalyzer())->analyse($this->importText, $this->ws()->data()->students(), $this->ws()->now());
    }

    public function applyFixes(): void
    {
        $this->importText = (new ImportAnalyzer())->fix($this->importText);
        $this->checkImport();
        $this->toast('Safe fixes applied', 'Names, classes, phone numbers and dates were cleaned; exact duplicate rows removed. Rows with real problems still need you.');
    }

    public function importReady(): void
    {
        $n = (int) ($this->importResult['ready'] ?? 0);
        $this->toast(Format::plural($n, 'row') . ' ready to import', 'Demo only — nothing was saved. Backend: create students from the “ready” rows.', 'info');
    }

    public function linkSiblings(string $key): void
    {
        $this->ws()->statePut('siblings', $key, true);
        $this->toast('Children linked to one parent', 'Sibling discount and one message per parent will apply. (Demo.)');
    }

    // ============================================================= messages

    public function chooseAudience(string $key): void
    {
        $all = $this->ws()->audiences();
        if (isset($all[$key])) {
            $this->audience = $key;
            $this->messageText = $all[$key]['template'];
            $this->messageNote = '';
        }
    }

    public function improveMessage(): void
    {
        try {
            $reply = (new AssistantClient())->ask(
                'Rewrite this school message for parents in India: polite, clear, under 300 characters. Keep every {placeholder} exactly as written. Reply with the message only.',
                $this->messageText,
                '',
                40,
            );
            if ($reply !== '' && mb_strlen($reply) < 600) {
                $this->messageText = trim($reply, " \"\n");
                $this->messageNote = 'Rewritten by AI — check it before sending.';
            }
        } catch (\Throwable $e) {
            report($e);
            $this->messageNote = 'The AI service is not reachable right now. You can still edit the message yourself.';
        }
    }

    public function sendMessage(): void
    {
        $a = $this->ws()->audiences()[$this->audience] ?? null;
        if (! $a || trim($this->messageText) === '') {
            $this->messageNote = 'Write a message first.';

            return;
        }
        $n = count($a['recipients']);
        $log = $this->ws()->stateGet('sent');
        $this->ws()->statePut('sent', (string) (count($log) + 1), ['at' => $this->ws()->now()->format('d M H:i'), 'audience' => $a['label'], 'count' => $n, 'text' => mb_substr($this->messageText, 0, 140)]);
        if ($this->audience === 'fees') {
            foreach ($a['recipients'] as $r) {
                $this->ws()->statePut('reminded', $r['id'], $this->ws()->now()->toDateString());
            }
        }
        $this->messageNote = Format::plural($n, 'message') . ' queued (demo — nothing was delivered; connect an SMS/WhatsApp provider in the backend).';
        $this->toast('Messages queued', $a['label'] . ' · ' . $n);
    }

    // ================================================================= view

    protected function getViewData(): array
    {
        $ws = $this->ws();
        $att = $ws->attendance();
        $roster = $ws->roster();
        $feeRows = array_column($ws->feeDesk()['rows'], null, 'id');
        $records = $ws->records();

        // Students list (search + smart filters)
        $q = mb_strtolower(trim($this->search));
        $students = [];
        foreach ($ws->studentsById() as $id => $s) {
            $r = $roster[$id];
            $row = ['id' => $id, 'name' => $s['name'], 'class' => $s['class'], 'attendance' => $r['attendance'], 'marks' => $r['marks'],
                'signal' => $r['signal'], 'tone' => $r['signal_type'], 'fee' => $feeRows[$id] ?? null, 'missing' => $records[$id]['missing'], 'today' => $att['students'][$id]['today'] ?? null];
            $match = match ($this->studentFilter) {
                'risk' => $r['signal_type'] === 'risk',
                'fees' => isset($feeRows[$id]),
                'attendance' => $r['attendance'] < 75,
                'records' => $records[$id]['missing'] !== [],
                default => true,
            };
            if ($match && ($q === '' || str_contains(mb_strtolower($s['name'] . ' ' . $id . ' ' . $s['class'] . ' ' . $r['signal']), $q))) {
                $students[] = $row;
            }
        }
        usort($students, fn ($a, $b) => [$a['tone'] !== 'risk', $a['tone'] !== 'warn', $a['class'], $a['name']] <=> [$b['tone'] !== 'risk', $b['tone'] !== 'warn', $b['class'], $b['name']]);

        // Admissions list
        $adm = $ws->admissions();
        $apps = array_values(array_filter($adm['applicants'], function ($a) {
            $ok = match ($this->admFilter) {
                'ready' => $a['ready'],
                'docs' => $a['stage'] >= 0 && $a['stage'] <= 2 && $a['missing'] !== [],
                'issues' => $a['issues'] !== [] && $a['stage'] >= 0,
                'offers' => $a['stage'] >= 4,
                'enquiry' => $a['stage'] === 0,
                default => true,
            };

            return $ok && ($this->admClass === 'all' || $a['class'] === $this->admClass);
        }));

        $fd = $ws->feeDesk();
        $feeList = $this->feeClass === 'all' ? $fd['rows'] : array_values(array_filter($fd['rows'], fn ($r) => $r['class'] === $this->feeClass));

        $audiences = $ws->audiences();
        $aud = $audiences[$this->audience] ?? reset($audiences);
        $date = $ws->now()->format('d M');

        $requests = $ws->requests();
        $decisions = $ws->decisions();

        return [
            'ws' => $ws,
            'now' => $ws->now(),
            'greeting' => $ws->greeting(),
            'focus' => $ws->focus(),
            'since' => $ws->sinceLastVisit(),
            'kpis' => $ws->kpis(),
            'decisions' => $decisions,
            'att' => $att,
            'students' => $students,
            'profile' => $this->profileId ? $ws->student($this->profileId) : null,
            'adm' => $adm,
            'apps' => array_slice($apps, 0, 40),
            'appsTotal' => count($apps),
            'fd' => $fd,
            'feeList' => $feeList,
            'upi' => $ws->upi(),
            'requests' => $requests,
            'draft' => $this->draftId ? $ws->certificateDraft($this->draftId) : null,
            'staffDesk' => $ws->staffDesk(),
            'health' => $ws->dataHealth(),
            'audiences' => $audiences,
            'aud' => $aud,
            'previews' => array_map(fn ($r) => ['to' => $r['parent'] . ($r['phone'] ? ' · ' . $r['phone'] : ''), 'text' => AdminWorkspace::fill($this->messageText, $r, $date)], array_slice($aud['recipients'], 0, 3)),
            'sentLog' => array_reverse($ws->stateGet('sent')),
            'badges' => [
                'home' => count($decisions),
                'admissions' => count($adm['ready']),
                'fees' => count(array_filter($ws->upi(), fn ($l) => ! $l['decided'] && $l['status'] !== 'Unmatched')),
                'requests' => count(array_filter($requests, fn ($r) => $r['status'] === 'Ready to issue')),
                'staff' => count($ws->staffDesk()['gaps']),
                'data' => count($ws->dataHealth()['siblings']) + count($ws->dataHealth()['duplicates']),
            ],
            'suggestions' => $this->suggestions($ws),
        ];
    }

    /** Copilot chips change with the time of day and what is happening today. */
    private function suggestions(AdminWorkspace $ws): array
    {
        $h = (int) $ws->now()->format('G');
        $s = [];
        if ($h < 11) {
            $s[] = 'who is absent today';
            $s[] = 'staff on leave';
        }
        $s[] = "today's UPI payments";
        if ($h >= 15) {
            $s[] = 'who should I call today';
        }
        $s[] = 'applications ready to verify';
        $s[] = 'seats left in class 7';
        $s[] = 'fees overdue more than 60 days';
        $s[] = 'certificates pending';

        return array_slice($s, 0, 6);
    }
}
