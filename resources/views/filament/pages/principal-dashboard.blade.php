<x-filament-panels::page>
    <style>
:root{color-scheme:dark;--pd-bg:#0e1420;--pd-card:#172033;--pd-ink:#eaf0fb;--pd-mut:#9aa8bf;--pd-line:#27324a;--pd-pri:#7aa2ff;--pd-pri-t:#1c2a4d;--pd-ok:#4fd195;--pd-ok-t:#12301f;--pd-warn:#f2b254;--pd-warn-t:#35280f;--pd-bad:#ff8a80;--pd-bad-t:#3a1a18;--pd-org:#fb923c;--pd-org-t:#3b2412}
body:has(.pd-principal-page){background:var(--pd-bg)!important;color:var(--pd-ink)!important;color-scheme:dark}
body:has(.pd-principal-page) .fi-body,
body:has(.pd-principal-page) .fi-layout,
body:has(.pd-principal-page) .fi-main-ctn,
body:has(.pd-principal-page) .fi-main,
body:has(.pd-principal-page) .fi-page,
body:has(.pd-principal-page) .fi-page-content,
body:has(.pd-principal-page) .fi-sidebar,
body:has(.pd-principal-page) .fi-sidebar-header,
body:has(.pd-principal-page) .fi-sidebar-nav,
body:has(.pd-principal-page) .fi-sidebar-footer,
body:has(.pd-principal-page) .fi-topbar{background:var(--pd-bg)!important;color:var(--pd-ink)!important}
body:has(.pd-principal-page) .fi-topbar{background:var(--pd-card)!important;border-bottom:1px solid var(--pd-line)!important}
body:has(.pd-principal-page) .fi-sidebar{border-right:1px solid var(--pd-line)!important}
body:has(.pd-principal-page) .fi-sidebar-item-label,
body:has(.pd-principal-page) .fi-topbar-item-btn,
body:has(.pd-principal-page) .fi-breadcrumbs-item-label{color:var(--pd-mut)!important}
body:has(.pd-principal-page) .fi-sidebar-item a:hover,
body:has(.pd-principal-page) .fi-sidebar-item.fi-active>a,
body:has(.pd-principal-page) .fi-sidebar-item-active>a{background:var(--pd-pri-t)!important;color:var(--pd-pri)!important}
body:has(.pd-principal-page) .fi-sidebar-group-label{color:var(--pd-mut)!important}
.pd-principal-page{color:var(--pd-ink);font:15px/1.45 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;display:grid;gap:16px}
.pd-principal-page *{box-sizing:border-box}
.pd-topbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 14px;background:var(--pd-card);border:1px solid var(--pd-line);border-radius:14px}
.pd-brand{font-weight:750;white-space:nowrap;color:var(--pd-ink)}
.pd-live-chip{font-size:12px;padding:4px 10px;border-radius:999px;background:var(--pd-ok-t);color:var(--pd-ok);font-weight:700;white-space:nowrap}
.pd-search-form{display:flex;align-items:center;gap:8px;flex:1;min-width:min(100%,420px);flex-wrap:wrap}
.pd-search-input{flex:1;min-width:200px;width:100%;height:44px;padding:0 14px;border:1px solid var(--pd-line);border-radius:10px;background:#0e1420;color:var(--pd-ink);outline:none}
.pd-search-input:focus,.pd-input:focus{border-color:var(--pd-pri);box-shadow:0 0 0 3px #7aa2ff22;outline:none}
.pd-wrap{color:var(--pd-ink)}
.pd-banner{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:18px 20px;border:1px solid var(--pd-line);border-radius:16px;background:linear-gradient(120deg,#1c2a4d,#172033 70%,#172b31)}
.pd-eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:.13em;font-weight:800;color:var(--pd-pri)}
.pd-title{font-size:25px;font-weight:800;letter-spacing:-.025em;margin-top:4px;color:var(--pd-ink)}
.pd-muted{color:var(--pd-mut);font-size:13px}
.pd-demo{display:inline-flex;align-items:center;gap:7px;border:1px solid #6a5833;border-radius:999px;padding:6px 10px;background:var(--pd-warn-t);color:var(--pd-warn);font-size:12px;font-weight:700;white-space:nowrap}
.pd-tabs{display:flex;gap:6px;flex-wrap:wrap;padding:6px;border:1px solid var(--pd-line);border-radius:13px;background:var(--pd-card)}
.pd-tab{border:1px solid transparent;border-radius:9px;padding:9px 13px;font-size:13px;font-weight:700;color:var(--pd-mut);background:transparent;cursor:pointer}
.pd-tab.active{background:var(--pd-pri);color:#fff;box-shadow:0 2px 6px #0004}
.pd-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.pd-card{border:1px solid var(--pd-line);border-radius:15px;background:var(--pd-card);padding:16px;min-width:0;color:var(--pd-ink)}
.pd-card-label{color:var(--pd-mut);font-size:12px;font-weight:650}
.pd-card-value{font-size:25px;font-weight:800;letter-spacing:-.02em;margin-top:7px;color:var(--pd-ink)}
.pd-card-foot{font-size:12px;color:var(--pd-mut);margin-top:5px}
.pd-section{border:1px solid var(--pd-line);border-radius:15px;padding:16px;background:var(--pd-card);color:var(--pd-ink)}
.pd-section-title{font-size:16px;font-weight:800;margin:0 0 12px;color:var(--pd-ink)}
.pd-two{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,1fr);gap:14px}
.pd-mini-list{display:grid;gap:10px}
.pd-mini-row{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--pd-line);border-radius:10px;padding:11px 12px;background:#111a2a}
.pd-pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:800;white-space:nowrap}
.pd-ok{background:var(--pd-ok-t);color:var(--pd-ok)}.pd-warn{background:var(--pd-warn-t);color:var(--pd-warn)}.pd-risk{background:var(--pd-bad-t);color:var(--pd-bad)}.pd-info{background:var(--pd-pri-t);color:var(--pd-pri)}
.pd-table-wrap{overflow-x:auto;border:1px solid var(--pd-line);border-radius:12px}
.pd-table{width:100%;border-collapse:collapse;min-width:760px;color:var(--pd-ink)}
.pd-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--pd-mut);background:#111a2a;padding:12px}
.pd-table td{padding:12px;border-top:1px solid var(--pd-line);vertical-align:middle;font-size:13px;color:var(--pd-ink)}
.pd-table tr:hover td{background:#1c2a4d55}
.pd-input{width:100%;border:1px solid var(--pd-line);background:#0e1420;color:var(--pd-ink);border-radius:9px;padding:10px 12px;font-size:13px}
.pd-btn{border:1px solid var(--pd-line);background:var(--pd-card);color:var(--pd-ink);border-radius:9px;padding:9px 12px;font-size:12px;font-weight:750;cursor:pointer;min-height:40px}
.pd-btn:hover{background:var(--pd-pri-t)}.pd-btn-primary{background:var(--pd-pri);border-color:var(--pd-pri);color:#fff}
.pd-progress{height:8px;background:var(--pd-line);border-radius:999px;overflow:hidden;margin-top:8px}.pd-progress span{display:block;height:100%;background:var(--pd-pri);border-radius:999px}
.pd-bar-row{display:grid;grid-template-columns:70px minmax(100px,1fr) 48px;align-items:center;gap:10px;margin:12px 0;font-size:12px}
.pd-table-name{font-weight:800;color:var(--pd-ink)}.pd-table-sub{font-size:11px;color:var(--pd-mut);margin-top:3px}
.pd-search-results{display:grid;gap:6px;margin-top:-8px;padding:8px;background:var(--pd-card);border:1px solid var(--pd-line);border-radius:12px}
.pd-search-result{display:flex;align-items:center;justify-content:space-between;gap:12px;text-align:left;width:100%;border:1px solid var(--pd-line);background:#111a2a;color:var(--pd-ink);border-radius:9px;padding:10px 12px;cursor:pointer}
.pd-search-result:hover{background:var(--pd-pri-t);border-color:var(--pd-pri)}
.pd-copilot-answer{border:1px solid var(--pd-line);background:var(--pd-card);border-radius:14px;padding:14px;overflow-wrap:anywhere}
.pd-copilot-text{white-space:pre-line;line-height:1.7;font-size:14px}
.pd-copilot-answer.error{border-color:#8c3834;color:var(--pd-bad)}
@media(max-width:1000px){.pd-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pd-two{grid-template-columns:1fr}}
@media(max-width:700px){.pd-search-form{min-width:100%}.pd-topbar{align-items:stretch}.pd-brand{width:100%}.pd-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pd-title{font-size:21px}.pd-card{padding:12px}.pd-card-value{font-size:21px}.pd-tab{padding:8px 10px}}
@media(max-width:420px){.pd-grid{grid-template-columns:1fr}.pd-search-input{min-width:100%}.pd-search-form button{flex:1}}

/* Principal-only shell: hide the default Filament sidebar and page title while keeping the user menu. */
body:has(.pd-principal-page) .fi-sidebar,
body:has(.pd-principal-page) .fi-sidebar-close-overlay,
body:has(.pd-principal-page) .fi-header,
body:has(.pd-principal-page) .fi-breadcrumbs {display:none!important}
body:has(.pd-principal-page) .fi-main,
body:has(.pd-principal-page) .fi-main-ctn,
body:has(.pd-principal-page) .fi-page,
body:has(.pd-principal-page) .fi-page-content {margin-inline-start:0!important;width:100%!important;max-width:none!important}
body:has(.pd-principal-page) .fi-topbar {background:var(--pd-card)!important;border-bottom:1px solid var(--pd-line)!important;color:var(--pd-ink)!important}
body:has(.pd-principal-page) .fi-topbar .fi-topbar-start:has(.fi-logo),
body:has(.pd-principal-page) .fi-topbar a[href="/admin"],
body:has(.pd-principal-page) .fi-topbar .fi-logo,
body:has(.pd-principal-page) .fi-topbar .fi-brand,
body:has(.pd-principal-page) .fi-topbar .fi-logo-text,
body:has(.pd-principal-page) .fi-topbar [data-filament-brand],
body:has(.pd-principal-page) .fi-sidebar-header {display:none!important}
body:has(.pd-principal-page) .fi-topbar .fi-topbar-item-btn {color:var(--pd-ink)!important}
.pd-workspace{display:grid;grid-template-columns:228px minmax(0,1fr);gap:18px;align-items:start;width:100%;max-width:none}
.pd-side-nav{position:sticky;top:18px;align-self:start;border:1px solid var(--pd-line);background:var(--pd-card);border-radius:15px;padding:14px;display:grid;gap:5px}
.pd-side-title{padding:5px 9px 10px;color:var(--pd-mut);font-weight:800;font-size:11px;letter-spacing:.11em;text-transform:uppercase}
.pd-side-link{display:flex;align-items:center;gap:10px;width:100%;min-height:42px;text-align:left;padding:9px 10px;border:1px solid transparent;border-radius:10px;background:transparent;color:var(--pd-mut);font-size:13px;font-weight:700;cursor:pointer}
.pd-side-link:hover{background:#111a2a;color:var(--pd-ink)}
.pd-side-link.active{background:var(--pd-pri-t);color:var(--pd-pri);border-color:#354b7a}
.pd-side-icon{width:22px;flex:0 0 22px;text-align:center;font-size:16px}
.pd-main{min-width:0;display:grid;gap:16px}
.pd-content-title{font-size:24px;font-weight:800;color:var(--pd-ink);margin:0 0 2px}
@media(max-width:1050px){.pd-workspace{grid-template-columns:205px minmax(0,1fr);gap:12px}}
@media(max-width:780px){.pd-workspace{grid-template-columns:1fr}.pd-side-nav{position:static;grid-template-columns:repeat(2,minmax(0,1fr))}.pd-side-title{grid-column:1/-1}.pd-side-link{font-size:12px}.pd-main{min-width:0}}
@media(max-width:480px){.pd-side-nav{grid-template-columns:1fr}.pd-side-title{grid-column:auto}}


/* Examination workspace: inspired by a modern exam-management table, but styled for Toptraits dark UI. */
.pd-exam-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px}
.pd-exam-title{font-size:19px;font-weight:850;margin:0;color:var(--pd-ink)}
.pd-exam-tabs{display:flex;gap:7px;flex-wrap:wrap;padding:7px;border:1px solid var(--pd-line);border-radius:13px;background:#111a2a;margin-bottom:14px}
.pd-exam-tab{display:inline-flex;align-items:center;gap:8px;border:1px solid transparent;border-radius:9px;padding:9px 12px;background:transparent;color:var(--pd-mut);font-size:12px;font-weight:800;cursor:pointer}
.pd-exam-tab.active{background:var(--pd-pri-t);border-color:#354b7a;color:var(--pd-pri)}
.pd-exam-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:14px 0}
.pd-exam-plan-list{display:grid;gap:10px}
.pd-exam-plan{display:grid;grid-template-columns:minmax(180px,1.2fr) minmax(150px,1fr) minmax(100px,.6fr) minmax(120px,.7fr) auto;align-items:center;gap:14px;padding:14px;border:1px solid var(--pd-line);border-radius:12px;background:#111a2a}
.pd-exam-plan:hover{border-color:#3d527b;background:#1a2740}
.pd-exam-plan-name{font-weight:800;color:var(--pd-ink)}
.pd-exam-plan-sub{font-size:11px;color:var(--pd-mut);margin-top:4px}
.pd-exam-label{font-size:10px;color:var(--pd-mut);text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin-bottom:4px}
.pd-exam-empty{padding:20px;border:1px dashed var(--pd-line);border-radius:12px;color:var(--pd-mut);text-align:center}
.pd-grade-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.pd-grade-item{padding:13px;border:1px solid var(--pd-line);border-radius:11px;background:#111a2a}
.pd-grade-item strong{font-size:18px;color:var(--pd-ink)}
.pd-grade-item span{display:block;margin-top:4px;color:var(--pd-mut);font-size:12px}
@media(max-width:1050px){.pd-exam-plan{grid-template-columns:repeat(2,minmax(0,1fr))}.pd-exam-plan>div:last-child{grid-column:1/-1}.pd-grade-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.pd-exam-plan{grid-template-columns:1fr}.pd-exam-plan>div:last-child{grid-column:auto}.pd-exam-tabs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.pd-exam-tab{justify-content:center}.pd-grade-grid{grid-template-columns:1fr}}

</style>
    @include('filament.pages.principal.styles')

    <div class="pd-wrap pd-principal-page">
      <div class="pd-workspace">
        <aside class="pd-side-nav" aria-label="Principal workspace navigation">
                    @php
              $navIcons = [
                  'home' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
                  'check' => '<rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.5 12.5l2.5 2.5 5-6"/>',
                  'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
                  'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2c2.8.3 5 2.7 5 5.8"/>',
                  'alert' => '<path d="M12 4l9 16H3z"/><path d="M12 10v4"/><circle cx="12" cy="17" r=".6"/>',
                  'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                  'pencil' => '<path d="M4 20l4-1 11-11-3-3L5 16z"/><path d="M14 6l3 3"/>',
                  'rupee' => '<path d="M7 5h10M7 9h10M7 5c5 0 5 8 0 8l8 6"/>',
                  'book' => '<path d="M4 5c3-1 5-1 8 1 3-2 5-2 8-1v14c-3-1-5-1-8 1-3-2-5-2-8-1z"/><path d="M12 6v14"/>',
                  'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
                  'list' => '<path d="M8 6h12M8 12h12M8 18h12"/><circle cx="4" cy="6" r=".8"/><circle cx="4" cy="12" r=".8"/><circle cx="4" cy="18" r=".8"/>',
                  'staff' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/>',
                  'trend' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
              ];
              $approvalBadge = count($this->approvalsData['items']);
              $circularBadge = count($this->circularsData['drafts']);
              $navGroups = [
                  'Today' => [
                      'overview' => ['Home Dashboard', 'home', null],
                      'approvals' => ['Approvals', 'check', $approvalBadge ? [$approvalBadge, ''] : null],
                      'circulars' => ['Alerts & Circulars', 'bell', $circularBadge ? [$circularBadge, 'info'] : null],
                  ],
                  'Students' => [
                      'students' => ['Students & AI signals', 'users', null],
                      'risk' => ['At-Risk', 'alert', null],
                      'attendance' => ['Attendance', 'clock', null],
                      'marks' => ['Marks', 'pencil', null],
                      'fees' => ['Fees', 'rupee', null],
                  ],
                  'Academics' => [
                      'examinations' => ['Examinations', 'book', null],
                      'timetable' => ['Timetable', 'calendar', null],
                      'syllabus' => ['Syllabus', 'list', null],
                      'staff' => ['Staff', 'staff', null],
                      'analytics' => ['School Analytics', 'trend', null],
                  ],
              ];
          @endphp
          @foreach ($navGroups as $groupName => $groupItems)
            <div class="pd-side-group">{{ $groupName }}</div>
            @foreach ($groupItems as $tabKey => [$tabLabel, $tabIcon, $tabBadge])
              <button type="button" wire:click="setTab('{{ $tabKey }}')" class="pd-side-link {{ $activeTab === $tabKey ? 'active' : '' }}" aria-current="{{ $activeTab === $tabKey ? 'page' : 'false' }}">
                <span class="pd-side-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $navIcons[$tabIcon] !!}</svg></span>
                <span>{{ $tabLabel }}</span>
                @if ($tabBadge)<span class="pd-side-badge {{ $tabBadge[1] }}">{{ $tabBadge[0] }}</span>@endif
              </button>
            @endforeach
          @endforeach
        </aside>
        <main class="pd-main">
        <div class="pd-topbar">
            <span class="pd-brand">Toptraits · Principal</span>
            <span class="pd-live-chip">● School overview</span>
            <div class="pd-search-form">
                <input class="pd-search-input" type="search" wire:model.live.debounce.300ms="headerQuery" wire:keydown.enter="askCopilot" placeholder="Find a student or ask Copilot… e.g. “fee defaulters”, “who is absent”, “what should I focus on”" aria-label="Find a student or ask Copilot">
                <button type="button" wire:click="findStudents" class="pd-btn">Search students</button>
                <button type="button" wire:click="askCopilot" wire:loading.attr="disabled" class="pd-btn pd-btn-primary">✦ Ask Copilot</button>
            </div>
        </div>
        {{-- Quick questions: one click asks Copilot --}}
        <div class="pd-chips" style="margin-top:-6px">
            @foreach (['What should I focus on today?', 'Who is absent today?', 'Fees pending more than 60 days', 'Which section is weakest in maths?', 'Who is on leave today?', 'help'] as $chip)
                <button type="button" class="pd-chip" wire:click="askSuggested(@js($chip))">{{ $chip }}</button>
            @endforeach
        </div>
        <div wire:loading.flex wire:target="askCopilot,askSuggested" class="pd-copilot-answer" style="gap:8px;align-items:center">✦ Thinking…</div>
        @if ($copilotError !== '')
            <div class="pd-copilot-answer error" role="alert"><strong>Copilot</strong> · {{ $copilotError }}</div>
        @endif
        @if ($copilotAnswer !== '')
            <div class="pd-copilot-answer" role="status" wire:loading.remove wire:target="askCopilot,askSuggested">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px">
                    <strong>✦ Copilot · {{ $headerQuery }}</strong>
                    <button type="button" wire:click="clearCopilotAnswer" class="pd-btn">Close</button>
                </div>
                <div class="pd-copilot-text">{{ $copilotAnswer }}</div>
            </div>
        @endif
        @if (trim($headerQuery) !== '' && $activeTab !== 'students' && count($headerSearchResults) > 0)
            <div class="pd-search-results" aria-label="Student search results">
                @foreach ($headerSearchResults as $result)
                    <button type="button" wire:click="viewSearchResult(@js($result['id']))" class="pd-search-result">
                        <span><strong>{{ $result['name'] }}</strong><br><span class="pd-muted">{{ $result['id'] }} · {{ $result['class'] }} · Attendance {{ $result['attendance'] }}% · Marks {{ $result['marks'] }}%</span></span>
                        <span class="pd-pill {{ $result['signal_type'] === 'ok' ? 'pd-ok' : ($result['signal_type'] === 'warn' ? 'pd-warn' : 'pd-risk') }}">{{ $result['signal'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Home has its own dynamic greeting, so the static banner shows on the other tabs only. --}}
        @if ($activeTab !== 'overview')
        <div class="pd-banner">
            <div>
                <div class="pd-eyebrow">School leadership overview</div>
                <div class="pd-title">Principal Dashboard</div>
                <p class="pd-muted" style="margin-top:5px">Review student progress, attendance, academics, fee status and today's timetable.</p>
            </div>
            <span class="pd-demo">● DEMO DATA — not connected to the school database</span>
        </div>

        <div class="pd-view-heading">
            <h1 class="pd-content-title">{{ match ($activeTab) {
                'approvals' => 'Approvals',
                'analytics' => 'School Analytics',
                'students' => 'Students & AI signals',
                'staff' => 'Staff Directory',
                'risk' => 'At-Risk Students & SIS',
                'attendance' => 'Attendance Overview',
                'marks' => 'Academic Marks',
                'examinations' => 'Examinations & Results',
                'fees' => 'Fees Overview',
                'timetable' => 'Timetable',
                'syllabus' => 'Syllabus Progress',
                'circulars' => 'Circulars & Alerts',
                default => 'School Overview',
            } }}</h1>
            <p class="pd-muted">School leadership workspace · Analysed {{ now()->format('d M Y, H:i') }}</p>
        </div>
        @endif

        @if ($activeTab === 'overview')
            @include('filament.pages.principal.overview')
        @elseif ($activeTab === 'students' || $activeTab === 'risk')
            <section class="pd-section space-y-3">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div><h2 class="pd-section-title" style="margin-bottom:3px">{{ $activeTab === 'risk' ? 'At-risk student register' : 'Student roster & AI signals' }}</h2><p class="pd-muted">{{ $activeTab === 'risk' ? 'Students flagged by attendance, marks and fees together — highest risk first.' : 'Signals are calculated from attendance, marks and fees. Highest risk first; use “View strategy” for the suggested plan.' }}</p></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" style="min-width:min(100%,420px)">
                        <input class="pd-input" type="search" wire:model.live.debounce.300ms="search" placeholder="Search student or admission no…" aria-label="Search students">
                        <select class="pd-input" wire:model.live="classFilter" aria-label="Filter by class"><option value="all">All classes</option>@foreach ($classes as $class)<option value="{{ $class }}">Class {{ $class }}</option>@endforeach</select>
                    </div>
                </div>

                @if ($selectedStudent)
                    <div class="pd-section" style="border-color:var(--pd-pri);background:#1c2a4d55">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div><div class="pd-eyebrow">Support strategy · generated from live signals</div><h3 class="pd-section-title" style="margin-top:4px">{{ $selectedStudent['name'] }} · {{ $selectedStudent['class'] }} — {{ $selectedStudent['signal'] }}</h3></div>
                            <button type="button" wire:click="closeStudentStrategy" class="pd-btn">Close</button>
                        </div>
                        <div class="pd-chips" style="margin:0 0 10px">
                            @foreach ($selectedStudent['reasons'] ?? [] as $reason)
                                <span class="pd-pill pd-warn">{{ $reason }}</span>
                            @endforeach
                            <span class="pd-pill pd-info">Risk score {{ $selectedStudent['risk_score'] ?? 0 }}/100</span>
                        </div>
                        <p><strong>Suggested strategy:</strong> {{ $selectedStudent['strategy'] }}</p>
                        <p style="margin-top:8px"><strong>Owner & review:</strong> {{ $selectedStudent['training'] }}</p>
                        <p class="pd-muted" style="margin-top:8px">Suggestions only. A teacher or principal must verify before taking action.</p>
                    </div>
                @endif

                <div class="pd-table-wrap">
                    <table class="pd-table">
                        <thead><tr><th>Student</th><th>Grade</th><th>Class</th><th>Attendance</th><th>Marks</th><th>Risk</th><th>AI signal</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse ($students as $student)
                            <tr wire:key="stu-{{ $student['id'] }}">
                                <td><div class="pd-table-name">{{ $student['name'] }}</div><div class="pd-table-sub">{{ $student['id'] }}</div></td>
                                <td><span class="pd-pill {{ $student['grade'] === 'A' ? 'pd-ok' : ($student['grade'] === 'B' ? 'pd-info' : 'pd-warn') }}">{{ $student['grade'] }}</span></td>
                                <td>{{ $student['class'] }}</td>
                                <td><strong>{{ $student['attendance'] }}%</strong></td>
                                <td><strong>{{ $student['marks'] }}%</strong></td>
                                <td>{{ $student['risk_score'] ?? 0 }}</td>
                                <td><span class="pd-pill {{ $student['signal_type'] === 'ok' ? 'pd-ok' : ($student['signal_type'] === 'warn' ? 'pd-warn' : 'pd-risk') }}">{{ $student['signal'] }}</span></td>
                                <td>@if ($student['signal_type'] !== 'ok')<button type="button" wire:click="showStudentStrategy(@js($student['id']))" class="pd-btn">View strategy</button>@else<span class="pd-muted">No action</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="pd-muted" style="padding:22px;text-align:center">No students match your search.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @elseif ($activeTab === 'staff')
            <section class="pd-section">
                <div class="flex flex-wrap items-end justify-between gap-3" style="margin-bottom:14px">
                    <div><h2 class="pd-section-title" style="margin-bottom:4px">Staff directory</h2><p class="pd-muted">Search the staff register by name, employee ID, subject, role or assigned class.</p></div>
                    <div style="min-width:min(100%,320px)"><input class="pd-input" type="search" wire:model.live.debounce.250ms="staffSearch" placeholder="Search staff, subject or class…" aria-label="Search staff directory"></div>
                </div>
                <div class="pd-grid" style="margin-bottom:14px">
                    <div class="pd-card"><div class="pd-card-label">Staff records</div><div class="pd-card-value">{{ $staffSummary['total'] }}</div><div class="pd-card-foot">Matching records</div></div>
                    <div class="pd-card"><div class="pd-card-label">Present today</div><div class="pd-card-value">{{ $staffSummary['present'] }}</div><div class="pd-card-foot">Today's status</div></div>
                    <div class="pd-card"><div class="pd-card-label">On leave</div><div class="pd-card-value">{{ $staffSummary['onLeave'] }}</div><div class="pd-card-foot">{{ $staffSummary['onLeave'] > 0 ? 'Check Timetable for cover' : 'Full strength' }}</div></div>
                    <div class="pd-card"><div class="pd-card-label">Active</div><div class="pd-card-value">{{ $staffSummary['active'] }}</div><div class="pd-card-foot">Staff register</div></div>
                </div>
                <div class="pd-table-wrap"><table class="pd-table"><thead><tr><th>Employee ID</th><th>Staff member</th><th>Designation</th><th>Department / subject</th><th>Assigned classes</th><th>Today</th><th>Employment</th></tr></thead><tbody>
                    @forelse ($staffMembers as $member)
                        <tr>
                            <td>{{ $member['id'] }}</td>
                            <td><div class="pd-table-name">{{ $member['name'] }}</div><div class="pd-table-sub">{{ $member['department'] }}</div></td>
                            <td>{{ $member['designation'] }}</td>
                            <td>{{ $member['subjects'] }}</td>
                            <td>{{ $member['classes'] }}</td>
                            <td><span class="pd-pill {{ $member['status'] === 'Present' ? 'pd-ok' : 'pd-warn' }}">{{ $member['status'] }}</span></td>
                            <td><span class="pd-pill pd-info">{{ $member['employment'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pd-muted" style="padding:22px;text-align:center">No staff match your search.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @elseif ($activeTab === 'attendance')
            @include('filament.pages.principal.attendance')
        @elseif ($activeTab === 'marks')
            @include('filament.pages.principal.marks')
        @elseif ($activeTab === 'examinations')
            <section class="pd-section">
                <div class="pd-exam-head">
                    <div>
                        <div class="pd-eyebrow">Academic operations</div>
                        <h2 class="pd-exam-title">Examination Centre</h2>
                        <p class="pd-muted" style="margin-top:4px">Plan assessment windows, review date sheets, follow marks entry and check results before release.</p>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span class="pd-pill pd-info">Academic year {{ now()->month >= 6 ? now()->year : now()->year - 1 }}–{{ substr((string) (now()->month >= 6 ? now()->year + 1 : now()->year), -2) }}</span>
                        <button type="button" wire:click="requestNewExamPlan" class="pd-btn pd-btn-primary">＋ New exam plan</button>
                    </div>
                </div>

                @if ($examNotice !== '')
                    <div class="pd-copilot-answer" role="status" style="margin-bottom:12px">{{ $examNotice }}</div>
                @endif

                <div class="pd-exam-tabs" role="tablist" aria-label="Examination sections">
                    <button type="button" role="tab" aria-selected="{{ $examinationView === 'exams' ? 'true' : 'false' }}" wire:click="setExaminationView('exams')" class="pd-exam-tab {{ $examinationView === 'exams' ? 'active' : '' }}">▣ Exam plans</button>
                    <button type="button" role="tab" aria-selected="{{ $examinationView === 'schedule' ? 'true' : 'false' }}" wire:click="setExaminationView('schedule')" class="pd-exam-tab {{ $examinationView === 'schedule' ? 'active' : '' }}">▦ Schedule</button>
                    <button type="button" role="tab" aria-selected="{{ $examinationView === 'marks' ? 'true' : 'false' }}" wire:click="setExaminationView('marks')" class="pd-exam-tab {{ $examinationView === 'marks' ? 'active' : '' }}">✎ Marks progress</button>
                    <button type="button" role="tab" aria-selected="{{ $examinationView === 'results' ? 'true' : 'false' }}" wire:click="setExaminationView('results')" class="pd-exam-tab {{ $examinationView === 'results' ? 'active' : '' }}">▥ Results</button>
                    <button type="button" role="tab" aria-selected="{{ $examinationView === 'grade-scale' ? 'true' : 'false' }}" wire:click="setExaminationView('grade-scale')" class="pd-exam-tab {{ $examinationView === 'grade-scale' ? 'active' : '' }}">◇ Grade scale</button>
                </div>

                @if ($examinationView === 'exams')
                    <div class="pd-grid" style="margin-bottom:14px">
                        <div class="pd-card"><div class="pd-card-label">Exam plans</div><div class="pd-card-value">{{ count($examPlans) }}</div><div class="pd-card-foot">Assessment windows this year</div></div>
                        <div class="pd-card"><div class="pd-card-label">Papers scheduled</div><div class="pd-card-value">{{ count($allExamRowsForView ?? $examRows) }}</div><div class="pd-card-foot">Use Schedule to filter class and section</div></div>
                        <div class="pd-card"><div class="pd-card-label">Needs review</div><div class="pd-card-value">{{ count(array_filter($examPlans, fn ($plan) => $plan['status'] === 'Review needed')) }}</div><div class="pd-card-foot">Marks or moderation work in progress</div></div>
                        <div class="pd-card"><div class="pd-card-label">Published plans</div><div class="pd-card-value">{{ count(array_filter($examPlans, fn ($plan) => $plan['status'] === 'Published')) }}</div><div class="pd-card-foot">All papers published</div></div>
                    </div>

                    <div class="pd-exam-toolbar">
                        <div><h3 class="pd-section-title" style="margin-bottom:3px">Assessment plans</h3><p class="pd-muted">Choose a plan to inspect its date sheet for a specific class.</p></div>
                        <span class="pd-muted">{{ count($examPlans) }} plans listed</span>
                    </div>
                    <div class="pd-exam-plan-list">
                        @forelse ($examPlans as $plan)
                            <div class="pd-exam-plan">
                                <div>
                                    <div class="pd-exam-plan-name">{{ $plan['exam'] }}</div>
                                    <div class="pd-exam-plan-sub">{{ $plan['papers'] }} subject papers · {{ $plan['class_count'] }} class sections</div>
                                </div>
                                <div><div class="pd-exam-label">Date window</div><div>{{ \Illuminate\Support\Carbon::parse($plan['start_date'])->format('d M Y') }} <span class="pd-muted">→</span> {{ \Illuminate\Support\Carbon::parse($plan['end_date'])->format('d M Y') }}</div></div>
                                <div><div class="pd-exam-label">Stage</div><span class="pd-pill {{ $plan['status'] === 'Published' ? 'pd-ok' : ($plan['status'] === 'Review needed' ? 'pd-warn' : 'pd-info') }}">{{ $plan['status'] }}</span></div>
                                <div><div class="pd-exam-label">Progress</div><div class="pd-progress"><span style="width:{{ $plan['status'] === 'Published' ? 100 : ($plan['status'] === 'Review needed' ? 62 : 28) }}%"></span></div></div>
                                <div><button type="button" wire:click="viewExamPlan(@js($plan['exam']))" class="pd-btn">Review plan →</button></div>
                            </div>
                        @empty
                            <div class="pd-exam-empty">No examination plans are available.</div>
                        @endforelse
                    </div>

                @elseif ($examinationView === 'schedule')
                    <div class="pd-exam-toolbar">
                        <div><h3 class="pd-section-title" style="margin-bottom:3px">Date sheet and room assignments</h3><p class="pd-muted">Filter the examination schedule by class, section and assessment.</p></div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <label style="display:grid;gap:5px;min-width:110px"><span class="pd-muted">Class</span><select class="pd-input" wire:model.live="examinationClass" aria-label="Select examination class">@foreach ($schoolClasses as $schoolClass)<option value="{{ $schoolClass }}">Class {{ $schoolClass }}</option>@endforeach</select></label>
                            <label style="display:grid;gap:5px;min-width:110px"><span class="pd-muted">Section</span><select class="pd-input" wire:model.live="examinationSection" aria-label="Select examination section">@foreach ($sections as $section)<option value="{{ $section }}">Section {{ $section }}</option>@endforeach</select></label>
                            <label style="display:grid;gap:5px;min-width:190px"><span class="pd-muted">Assessment</span><select class="pd-input" wire:model.live="examinationName" aria-label="Select examination">@foreach ($examinationNames as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
                        </div>
                    </div>
                    <div class="pd-demo" style="margin-bottom:12px">Selected: Class {{ $examinationClass }}-{{ $examinationSection }} · {{ $examinationName }}</div>
                    <div class="pd-grid" style="margin-bottom:14px">
                        <div class="pd-card"><div class="pd-card-label">Papers in this selection</div><div class="pd-card-value">{{ $examSummary['papers'] }}</div><div class="pd-card-foot">Subject papers found</div></div>
                        <div class="pd-card"><div class="pd-card-label">Scheduled</div><div class="pd-card-value">{{ $examSummary['scheduled'] }}</div><div class="pd-card-foot">Ready for exam-day planning</div></div>
                        <div class="pd-card"><div class="pd-card-label">Marks entry</div><div class="pd-card-value">{{ $examSummary['marksEntry'] }}</div><div class="pd-card-foot">Awaiting completion or review</div></div>
                        <div class="pd-card"><div class="pd-card-label">Published</div><div class="pd-card-value">{{ $examSummary['published'] }}</div><div class="pd-card-foot">Published papers</div></div>
                    </div>
                    <div class="pd-table-wrap"><table class="pd-table"><thead><tr><th>Date</th><th>Subject</th><th>Time</th><th>Maximum marks</th><th>Teacher</th><th>Room</th><th>Status</th></tr></thead><tbody>
                        @forelse ($examRows as $row)
                            <tr><td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td><td><strong>{{ $row['subject'] }}</strong></td><td>{{ $row['start'] }}–{{ $row['end'] }}</td><td>{{ $row['max_marks'] }}</td><td>{{ $row['teacher'] }}</td><td>{{ $row['room'] }}</td><td><span class="pd-pill {{ $row['status'] === 'Published' ? 'pd-ok' : (in_array($row['status'], ['Marks entry', 'Moderation'], true) ? 'pd-warn' : 'pd-info') }}">{{ $row['status'] }}</span></td></tr>
                        @empty
                            <tr><td colspan="7" class="pd-muted" style="padding:22px;text-align:center">No schedule entries for this selection. Try another class, section or assessment.</td></tr>
                        @endforelse
                    </tbody></table></div>

                @elseif ($examinationView === 'marks')
                    <div class="pd-exam-toolbar"><div><h3 class="pd-section-title" style="margin-bottom:3px">Marks entry progress</h3><p class="pd-muted">Track which papers still need marks entry or moderation.</p></div><span class="pd-demo">Selected class: {{ $examinationClass }}-{{ $examinationSection }} · {{ $examinationName }}</span></div>
                    <div class="pd-table-wrap"><table class="pd-table"><thead><tr><th>Subject</th><th>Assessment</th><th>Maximum marks</th><th>Assigned teacher</th><th>Current stage</th><th>Review</th></tr></thead><tbody>
                        @forelse ($examRows as $row)
                            <tr><td><strong>{{ $row['subject'] }}</strong></td><td>{{ $row['exam'] }}</td><td>{{ $row['max_marks'] }}</td><td>{{ $row['teacher'] }}</td><td><span class="pd-pill {{ $row['status'] === 'Published' ? 'pd-ok' : (in_array($row['status'], ['Marks entry', 'Moderation'], true) ? 'pd-warn' : 'pd-info') }}">{{ $row['status'] }}</span></td><td><span class="pd-muted">Read-only</span></td></tr>
                        @empty
                            <tr><td colspan="6" class="pd-muted" style="padding:22px;text-align:center">No mark-entry items for the selected class and assessment.</td></tr>
                        @endforelse
                    </tbody></table></div>

                @elseif ($examinationView === 'results')
                    <div class="pd-grid" style="margin-bottom:14px">
                        <div class="pd-card"><div class="pd-card-label">Papers for review</div><div class="pd-card-value">{{ $examSummary['marksEntry'] + count(array_filter($examRows, fn ($row) => $row['status'] === 'Moderation')) }}</div><div class="pd-card-foot">Marks and moderation stages</div></div>
                        <div class="pd-card"><div class="pd-card-label">Published papers</div><div class="pd-card-value">{{ $examSummary['published'] }}</div><div class="pd-card-foot">For the selected class and assessment</div></div>
                        <div class="pd-card"><div class="pd-card-label">Result sign-off</div><div class="pd-card-value">Pending</div><div class="pd-card-foot">Approval gate</div></div>
                        <div class="pd-card"><div class="pd-card-label">Report cards</div><div class="pd-card-value">Not generated</div><div class="pd-card-foot">Production integration required</div></div>
                    </div>
                    <div class="pd-two">
                        <section class="pd-section"><h3 class="pd-section-title">Before results are released</h3><div class="pd-mini-list"><div class="pd-mini-row"><span>All students accounted for</span><span class="pd-pill pd-info">Required</span></div><div class="pd-mini-row"><span>Missing marks and absences checked</span><span class="pd-pill pd-warn">Verify</span></div><div class="pd-mini-row"><span>Totals and grade rules validated</span><span class="pd-pill pd-info">Required</span></div><div class="pd-mini-row"><span>Authorised reviewer sign-off</span><span class="pd-pill pd-warn">Pending</span></div></div></section>
                        <section class="pd-section"><h3 class="pd-section-title">Reports available in the planned module</h3><div class="pd-mini-list"><div class="pd-mini-row"><span>Individual report card</span><span class="pd-pill pd-info">Planned</span></div><div class="pd-mini-row"><span>Class and subject averages</span><span class="pd-pill pd-info">Planned</span></div><div class="pd-mini-row"><span>Pass-rate and performance trends</span><span class="pd-pill pd-info">Planned</span></div><div class="pd-mini-row"><span>Exportable marks register</span><span class="pd-pill pd-info">Planned</span></div></div></section>
                    </div>

                @elseif ($examinationView === 'grade-scale')
                    <div class="pd-exam-toolbar"><div><h3 class="pd-section-title" style="margin-bottom:3px">Grade scale and assessment rules</h3><p class="pd-muted">A school-configurable reference area for grading bands, pass criteria and weighting.</p></div><span class="pd-pill pd-warn">Example only</span></div>
                    <div class="pd-grade-grid">
                        <div class="pd-grade-item"><strong>A1</strong><span>91–100 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>A2</strong><span>81–90 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>B1</strong><span>71–80 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>B2</strong><span>61–70 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>C1</strong><span>51–60 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>C2</strong><span>41–50 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>D</strong><span>33–40 · Illustrative band</span></div>
                        <div class="pd-grade-item"><strong>E</strong><span>0–32 · Illustrative band</span></div>
                    </div>
                    <div class="pd-section" style="margin-top:14px"><h3 class="pd-section-title">Configuration notes</h3><p class="pd-muted">Before production, configure the scale for the school's board, academic year, class, subject and assessment type. Set pass criteria, internal/external weighting, exemptions and rounding rules with authorised academic staff. Do not use these illustrative bands as a policy default.</p></div>
                @endif
            </section>
        @elseif ($activeTab === 'fees')
            @include('filament.pages.principal.fees')
        @elseif ($activeTab === 'timetable')
            @if ($ttIsToday)
                @include('filament.pages.principal.substitutions')
            @endif
            <section class="pd-section">
                <div class="pd-tt-toolbar">
                    <div>
                        <h2 class="pd-section-title" style="margin-bottom:4px">Timetable · {{ $ttDate->format('l, d M Y') }}
                            @if ($ttIsToday)<span class="pd-pill pd-ok" style="margin-left:6px">Today</span>@elseif ($ttDate->isPast())<span class="pd-pill pd-info" style="margin-left:6px">Past day</span>@else<span class="pd-pill pd-warn" style="margin-left:6px">Upcoming</span>@endif
                        </h2>
                        <p class="pd-muted">Pick any date to review classes. Today's status follows the clock.</p>
                    </div>
                    <div class="pd-tt-controls">
                        <div class="pd-tt-date">
                            <button type="button" class="pd-btn" wire:click="shiftTimetableDate(-1)" title="Previous school day">‹</button>
                            <input type="date" class="pd-input" value="{{ $ttDate->toDateString() }}" wire:change="$set('timetableDate', $event.target.value)" aria-label="Timetable date" wire:key="tt-date-{{ $ttDate->toDateString() }}">
                            <button type="button" class="pd-btn" wire:click="shiftTimetableDate(1)" title="Next school day">›</button>
                            @unless ($ttIsToday)<button type="button" class="pd-btn pd-btn-primary" wire:click="resetTimetableDate">Today</button>@endunless
                        </div>
                        <label class="pd-tt-field"><span class="pd-muted">Class</span><select class="pd-input" wire:model.live="timetableClass" aria-label="Select timetable class">@foreach ($schoolClasses as $schoolClass)<option value="{{ $schoolClass }}">Class {{ $schoolClass }}</option>@endforeach</select></label>
                        <label class="pd-tt-field"><span class="pd-muted">Section</span><select class="pd-input" wire:model.live="timetableSection" aria-label="Select timetable section">@foreach ($sections as $section)<option value="{{ $section }}">Section {{ $section }}</option>@endforeach</select></label>
                    </div>
                </div>

                <div class="pd-table-wrap"><table class="pd-table"><thead><tr><th>Time</th><th>Class</th><th>Subject</th><th>Teacher</th><th>Room</th><th>Status</th></tr></thead><tbody>
                    @forelse ($timetableEntries as $row)
                        <tr wire:key="tt-{{ $row['class'] }}-{{ $row['start'] }}">
                            <td>{{ $row['time'] }}</td><td>{{ $row['class'] }}</td><td><strong>{{ $row['subject'] }}</strong></td>
                            <td>{{ $row['teacher'] }} @if ($row['teacher_on_leave'] ?? false)<span class="pd-pill pd-risk" style="margin-left:4px">On leave</span>@endif</td>
                            <td>{{ $row['room'] }}</td>
                            <td><span class="pd-pill {{ $row['status'] === 'Completed' ? 'pd-ok' : ($row['status'] === 'In progress' ? 'pd-info' : 'pd-warn') }}">{{ $row['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pd-muted" style="padding:22px;text-align:center">
                            {{ $ttDate->isSunday() ? 'Sunday — no classes.' : 'No classes scheduled for ' . $timetableClass . '-' . $timetableSection . ' on this day.' }}
                            <button type="button" class="pd-link" wire:click="shiftTimetableDate(1)">Next school day ›</button>
                        </td></tr>
                    @endforelse
                </tbody></table></div>
            </section>

            {{-- Whole week for the section — the principal sees the pattern at a glance --}}
            <section class="pd-section">
                <h3 class="pd-section-title">Week view · {{ $timetableClass }}-{{ $timetableSection }} <span class="pd-muted" style="font-weight:500">— {{ \Illuminate\Support\Arr::first($timetableWeek)['label'] ?? '' }} to {{ \Illuminate\Support\Arr::last($timetableWeek)['label'] ?? '' }} · click a day to open it</span></h3>
                <div class="pd-table-wrap"><table class="pd-table pd-week">
                    <thead><tr><th>Period</th>
                        @foreach ($timetableWeek as $date => $day)
                            <th class="{{ $date === $ttDate->toDateString() ? 'pd-week-on' : '' }}"><button type="button" class="pd-link" wire:click="$set('timetableDate', '{{ $date }}')">{{ $day['label'] }}</button></th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                    @foreach ($timetableSlots as $slot)
                        <tr>
                            <td class="pd-muted">{{ $slot }}</td>
                            @foreach ($timetableWeek as $date => $day)
                                @php $p = $day['periods'][$slot] ?? null; @endphp
                                <td class="{{ $date === $ttDate->toDateString() ? 'pd-week-on' : '' }}">
                                    @if ($p)
                                        <strong>{{ $p['subject'] }}</strong>
                                        <div class="pd-table-sub">{{ $p['teacher'] }}@if ($p['teacher_on_leave'] ?? false) · <span style="color:var(--pd-bad)">leave</span>@endif</div>
                                    @else
                                        <span class="pd-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </section>
        @elseif ($activeTab === 'syllabus')
            <section class="pd-section">
                <div class="flex flex-wrap items-end justify-between gap-3" style="margin-bottom:14px">
                    <div><h2 class="pd-section-title" style="margin-bottom:4px">Syllabus progress</h2><p class="pd-muted">Actual completion vs where the class should be by today.</p></div>
                    <div class="flex flex-wrap gap-2">
                        <label style="display:grid;gap:5px;min-width:125px"><span class="pd-muted">Class</span><select class="pd-input" wire:model.live="syllabusClass" aria-label="Select syllabus class">@foreach ($schoolClasses as $schoolClass)<option value="{{ $schoolClass }}">Class {{ $schoolClass }}</option>@endforeach</select></label>
                        <label style="display:grid;gap:5px;min-width:125px"><span class="pd-muted">Section</span><select class="pd-input" wire:model.live="syllabusSection" aria-label="Select syllabus section">@foreach ($sections as $section)<option value="{{ $section }}">Section {{ $section }}</option>@endforeach</select></label>
                    </div>
                </div>
                <div class="pd-demo" style="margin-bottom:12px">Selected class: {{ $syllabusClass }}-{{ $syllabusSection }}</div>
                <div class="pd-table-wrap"><table class="pd-table"><thead><tr><th>Subject</th><th>Chapter / unit</th><th>Current topic</th><th>Completion</th><th>Status</th><th>Target</th></tr></thead><tbody>
                    @forelse ($syllabusItems as $row)
                        <tr><td><strong>{{ $row['subject'] }}</strong></td><td>{{ $row['chapter'] }}</td><td>{{ $row['topic'] }}</td><td><div style="min-width:90px"><strong>{{ $row['completion'] }}%</strong><div class="pd-progress"><span style="width:{{ $row['completion'] }}%"></span></div></div></td><td><span class="pd-pill {{ $row['status'] === 'Completed' ? 'pd-ok' : ($row['status'] === 'Needs review' ? 'pd-warn' : 'pd-info') }}">{{ $row['status'] }}</span></td><td>{{ $row['target'] }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="pd-muted" style="padding:22px;text-align:center">No syllabus entries for {{ $syllabusClass }}-{{ $syllabusSection }}.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @elseif ($activeTab === 'approvals')
            @include('filament.pages.principal.approvals')
        @elseif ($activeTab === 'analytics')
            @include('filament.pages.principal.analytics')
        @elseif ($activeTab === 'circulars')
            @include('filament.pages.principal.circulars')
        @endif

        <p class="pd-muted">Principal workspace · Demo data generated from today's date. Connect to the approved tenant-scoped Laravel/PostgreSQL services before use with real school records.</p>
        </main>
      </div>
    </div>
</x-filament-panels::page>
