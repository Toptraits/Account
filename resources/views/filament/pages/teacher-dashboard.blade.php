<x-filament-panels::page>
<style>
:root{color-scheme:dark;--td-bg:#0e1420;--td-card:#172033;--td-ink:#eaf0fb;--td-mut:#9aa8bf;--td-line:#27324a;--td-blue:#7aa2ff;--td-blue-t:#1c2a4d;--td-green:#4fd195;--td-green-t:#12301f;--td-amber:#f2b254;--td-amber-t:#35280f;--td-red:#ff8a80;--td-red-t:#3a1a18;--td-cyan:#54c7c7;}
body:has(.td-teacher-page){background:var(--td-bg)!important;color:var(--td-ink)!important;color-scheme:dark}
body:has(.td-teacher-page) .fi-sidebar,body:has(.td-teacher-page) .fi-sidebar-close-overlay,body:has(.td-teacher-page) .fi-header,body:has(.td-teacher-page) .fi-breadcrumbs{display:none!important}
body:has(.td-teacher-page) .fi-main,body:has(.td-teacher-page) .fi-main-ctn,body:has(.td-teacher-page) .fi-page,body:has(.td-teacher-page) .fi-page-content{margin-inline-start:0!important;width:100%!important;max-width:none!important;background:var(--td-bg)!important;color:var(--td-ink)!important}
body:has(.td-teacher-page) .fi-topbar{background:var(--td-card)!important;border-bottom:1px solid var(--td-line)!important;color:var(--td-ink)!important}
body:has(.td-teacher-page) .fi-topbar .fi-topbar-start:has(.fi-logo),body:has(.td-teacher-page) .fi-topbar a[href="/admin"],body:has(.td-teacher-page) .fi-topbar .fi-logo,body:has(.td-teacher-page) .fi-topbar .fi-brand,body:has(.td-teacher-page) .fi-topbar .fi-logo-text{display:none!important}
.td-teacher-page,.td-teacher-page *{box-sizing:border-box}
.td-teacher-page{color:var(--td-ink);font:14px/1.5 system-ui,"Segoe UI",Arial,sans-serif;width:100%;max-width:none}
.td-workspace{display:grid;grid-template-columns:245px minmax(0,1fr);gap:18px;align-items:stretch;width:100%;max-width:none}
.td-side{position:sticky;top:14px;align-self:start;min-height:calc(100vh - 120px);border:1px solid var(--td-line);background:var(--td-card);border-radius:15px;padding:14px;display:flex;flex-direction:column;gap:3px}
.td-side-title{padding:5px 9px 4px;color:var(--td-mut);font-size:11px;font-weight:800;letter-spacing:.11em;text-transform:uppercase}
.td-side-group{padding:12px 10px 4px;color:var(--td-mut);font-size:10.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.td-nav{display:flex;align-items:center;gap:11px;width:100%;min-height:41px;padding:9px 10px;border:1px solid transparent;border-radius:10px;background:transparent;color:var(--td-mut);text-align:left;font-size:13px;font-weight:700;cursor:pointer}
.td-nav:hover{background:#111a2a;color:var(--td-ink)}.td-nav.active{background:var(--td-blue-t);border-color:#354b7a;color:var(--td-blue)}
.td-nav-icon{width:22px;flex:0 0 22px;display:grid;place-items:center}
.td-badge{margin-left:auto;font-size:10.5px;border-radius:999px;padding:1px 7px;font-weight:800;background:var(--td-red-t);color:var(--td-red)}.td-badge.info{background:var(--td-blue-t);color:var(--td-blue)}
.td-safe{margin-top:auto;border:1px solid #20564e;background:#102b2b;border-radius:12px;padding:12px;color:#8cded2;font-size:12px}
.td-main{min-width:0;display:grid;align-content:start;gap:16px}
.td-topbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 14px;background:var(--td-card);border:1px solid var(--td-line);border-radius:14px}
.td-brand{font-weight:800;white-space:nowrap;color:var(--td-ink)}.td-live{border-radius:999px;background:var(--td-green-t);color:var(--td-green);padding:5px 10px;font-size:12px;font-weight:800;white-space:nowrap}
.td-copilot{display:flex;align-items:center;gap:8px;flex:1;min-width:280px}.td-input{width:100%;min-width:0;padding:10px 12px;background:#0e1420;border:1px solid var(--td-line);border-radius:10px;color:var(--td-ink);outline:none;color-scheme:dark}.td-input:focus{border-color:var(--td-blue);box-shadow:0 0 0 3px #7aa2ff22}.td-copilot .td-input{flex:1;height:44px}
.td-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;padding:8px 13px;border:1px solid var(--td-line);border-radius:10px;background:var(--td-card);color:var(--td-ink);font-size:12px;font-weight:800;cursor:pointer;white-space:nowrap}.td-btn:hover{background:var(--td-blue-t)}.td-btn.primary{background:var(--td-blue);border-color:var(--td-blue);color:#111a2a}.td-btn.ghost{background:transparent;border-color:transparent;color:var(--td-mut)}.td-btn:disabled{opacity:.6;cursor:wait}
.td-chips{display:flex;gap:6px;flex-wrap:wrap}.td-chip{border:1px solid var(--td-line);background:transparent;color:var(--td-mut);border-radius:999px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer}.td-chip.active,.td-chip:hover{background:var(--td-blue-t);color:var(--td-blue);border-color:#354b7a}
.td-alert,.td-answer{border:1px solid var(--td-line);background:var(--td-card);border-radius:14px;padding:14px;color:var(--td-ink)}.td-alert.error{border-color:#8c3834;color:var(--td-red)}.td-answer-text{white-space:pre-line;line-height:1.7;overflow-wrap:anywhere}.td-answer-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px}
.td-eyebrow{font-size:11px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--td-blue)}.td-muted{color:var(--td-mut);font-size:13px}
.td-section{padding:17px;border:1px solid var(--td-line);border-radius:15px;background:var(--td-card);min-width:0}.td-section-title{font-weight:850;font-size:16px;margin:0 0 13px;color:var(--td-ink)}
.td-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.td-attention{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--td-line)}.td-attention:last-child{border-bottom:0;padding-bottom:0}.td-dot{width:10px;height:10px;flex:0 0 10px;border-radius:50%}.td-dot.risk{background:#fb7185}.td-dot.warn{background:#f59e0b}.td-dot.info{background:#38bdf8}.td-dot.ok{background:#4fd195}.td-att-content{flex:1;min-width:0}.td-att-title{font-weight:800;color:var(--td-ink)}.td-att-detail{font-size:12px;color:var(--td-mut);margin-top:2px}
.td-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.td-grid.four{grid-template-columns:repeat(4,minmax(0,1fr))}.td-card{min-width:0;padding:16px;border:1px solid var(--td-line);border-radius:15px;background:var(--td-card)}.td-card-label{font-size:11px;color:var(--td-mut);font-weight:800;text-transform:uppercase;letter-spacing:.04em}.td-card-title{font-size:19px;font-weight:850;margin-top:6px;color:var(--td-ink)}.td-card-sub{font-size:12px;color:var(--td-mut);margin-top:4px}
.td-progress{position:relative;height:8px;background:var(--td-line);border-radius:999px;overflow:visible;margin-top:8px}.td-progress span{display:block;height:100%;background:var(--td-blue);border-radius:999px}.td-progress i{position:absolute;top:-4px;width:2px;height:16px;background:var(--td-amber)}
.td-table-wrap{overflow-x:auto;border:1px solid var(--td-line);border-radius:12px}.td-table{width:100%;min-width:650px;border-collapse:collapse;color:var(--td-ink)}.td-table th{background:#111a2a;color:var(--td-mut);font-size:10px;letter-spacing:.05em;text-align:left;text-transform:uppercase;padding:11px 12px}.td-table td{padding:11px 12px;border-top:1px solid var(--td-line);font-size:13px;vertical-align:middle}.td-table tr:hover td{background:#1c2a4d55}
.td-pill{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:800;white-space:nowrap}.td-pill.ok{background:var(--td-green-t);color:var(--td-green)}.td-pill.warn{background:var(--td-amber-t);color:var(--td-amber)}.td-pill.risk{background:var(--td-red-t);color:var(--td-red)}.td-pill.info{background:var(--td-blue-t);color:var(--td-blue)}
.td-notice{margin-top:12px;border:1px solid #354b7a;background:var(--td-blue-t);color:#cbd9ff;padding:11px 12px;border-radius:10px;font-size:12px}
.td-field{display:grid;gap:6px;min-width:150px}.td-field label{font-size:12px;color:var(--td-mut);font-weight:750}
/* new: my day */
.td-now{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:12px}
.td-now-card{padding:16px;border:1px solid var(--td-line);border-radius:15px;background:linear-gradient(120deg,#1c2a4d,#172033 75%)}
.td-now-card.next{background:var(--td-card)}
.td-big{font-size:22px;font-weight:850;margin-top:4px}
.td-timeline{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
.td-slot{border:1px solid var(--td-line);border-radius:10px;padding:6px 9px;font-size:11.5px;background:#111a2a;color:var(--td-mut)}
.td-slot.done{opacity:.55}.td-slot.now{border-color:var(--td-blue);color:var(--td-ink);background:var(--td-blue-t)}.td-slot.cover{border-color:#6a5833;color:var(--td-amber)}
.td-kpi{display:grid;gap:3px;padding:14px;border:1px solid var(--td-line);border-radius:14px;background:var(--td-card);text-align:left;color:var(--td-ink);cursor:pointer}.td-kpi:hover{border-color:var(--td-blue)}.td-kpi b{font-size:22px}
/* register */
.td-last5{display:flex;gap:4px}.td-last5 span{width:18px;height:18px;border-radius:5px;font-size:10px;font-weight:800;display:grid;place-items:center}
.td-last5 .P{background:var(--td-green-t);color:var(--td-green)}.td-last5 .A{background:var(--td-red-t);color:var(--td-red)}.td-last5 .L{background:var(--td-amber-t);color:var(--td-amber)}
.td-toggle{display:inline-flex;border:1px solid var(--td-line);border-radius:10px;overflow:hidden}.td-toggle button{border:0;background:transparent;color:var(--td-mut);padding:7px 11px;font-size:12px;font-weight:800;cursor:pointer}
.td-toggle button.on.present{background:var(--td-green-t);color:var(--td-green)}.td-toggle button.on.absent{background:var(--td-red-t);color:var(--td-red)}.td-toggle button.on.late{background:var(--td-amber-t);color:var(--td-amber)}
/* worksheet */
.td-ws{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.td-ws ol{margin:8px 0 0;padding-left:18px}.td-ws li{margin:7px 0}
.td-ws .td-card{border-top:3px solid var(--td-blue)}.td-ws .support{border-top-color:var(--td-amber)}.td-ws .challenge{border-top-color:var(--td-green)}
.td-q-list{display:grid;gap:8px;margin-top:12px}.td-q{display:grid;grid-template-columns:22px minmax(0,1fr) auto;gap:6px;align-items:start}
.td-q-no{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;background:var(--td-blue-t);color:var(--td-blue);font-size:11px;font-weight:800;margin-top:8px}
.td-q textarea{resize:vertical;font:inherit;font-size:13px;line-height:1.45}
.td-print{display:none}
.td-prep{border-left:4px solid var(--td-blue);background:linear-gradient(110deg,#1c2a4d66,var(--td-card) 60%)}
.td-tips{margin:0;padding-left:18px;display:grid;gap:6px;list-style:disc}.td-tips li{color:var(--td-ink)}
.td-answer-box{border-style:dashed;color:#9fe3c0;font-size:12.5px}
.td-week td,.td-week th{text-align:center}.td-week td:first-child,.td-week th:first-child{text-align:left}.td-week .on{background:#1c2a4d55}
.td-link{background:none;border:0;padding:0;color:var(--td-blue);font-weight:700;cursor:pointer}
@media print{body *{visibility:hidden}.td-print,.td-print *{visibility:visible}.td-print{display:block!important;position:absolute;left:0;top:0;color:#000;background:#fff;padding:24px}.td-print ol{list-style:decimal;padding-left:24px}.td-print li{margin:10px 0}}
@media(max-width:1120px){.td-workspace{grid-template-columns:215px minmax(0,1fr)}.td-grid,.td-grid.four,.td-now,.td-ws{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:780px){.td-workspace{grid-template-columns:1fr}.td-side{position:static;min-height:0}.td-grid,.td-grid.four,.td-now,.td-ws{grid-template-columns:1fr}.td-copilot{min-width:100%}}
</style>

@php
    $icons = [
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'check' => '<rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.5 12.5l2.5 2.5 5-6"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'class' => '<path d="M3 7l9-4 9 4-9 4z"/><path d="M7 9v5c0 1.5 2.2 3 5 3s5-1.5 5-3V9"/>',
        'chart' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'book' => '<path d="M4 5c3-1 5-1 8 1 3-2 5-2 8-1v14c-3-1-5-1-8 1-3-2-5-2-8-1z"/><path d="M12 6v14"/>',
        'spark' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
        'scan' => '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M7 12h10"/>',
    ];
    $navGroups = [
        'My day' => ['today' => ['Today', 'sun', $badges['today'] ?: null], 'attendance' => ['Take Attendance', 'check', null], 'timetable' => ['My Timetable', 'calendar', null]],
        'Teaching' => ['classes' => ['My Classes', 'class', null], 'progress' => ['Student Progress', 'chart', null], 'homework' => ['Homework', 'book', null], 'worksheet' => ['AI Worksheet Maker', 'spark', null], 'scan' => ['Marks & Answer Sheets', 'scan', $badges['scan'] ? [$badges['scan'], 'info'] : null]],
    ];
    $statusPill = fn ($s) => match ($s) { 'Completed' => 'ok', 'In progress' => 'info', 'Marks entry' => 'warn', 'Moderation' => 'info', 'Published' => 'ok', default => 'warn' };
@endphp

<div class="td-teacher-page">
  <div class="td-workspace">
    <aside class="td-side" aria-label="Teacher navigation">
      <div class="td-side-title">Teacher Console</div>
      @foreach ($navGroups as $group => $items)
        <div class="td-side-group">{{ $group }}</div>
        @foreach ($items as $tab => [$label, $icon, $badge])
          <button type="button" wire:click="setTab('{{ $tab }}')" class="td-nav {{ $activeTab === $tab ? 'active' : '' }}" aria-current="{{ $activeTab === $tab ? 'page' : 'false' }}">
            <span class="td-nav-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$icon] !!}</svg></span>
            <span>{{ $label }}</span>
            @if ($badge)<span class="td-badge {{ is_array($badge) ? $badge[1] : '' }}">{{ is_array($badge) ? $badge[0] : $badge }}</span>@endif
          </button>
        @endforeach
      @endforeach
      <div class="td-safe"><strong>♢ Safe Education AI</strong><br>Suggestions are based on your classes' attendance and marks. Always use your own judgement before acting.</div>
    </aside>

    <main class="td-main">
      {{-- Copilot --}}
      <div class="td-topbar">
        <span class="td-brand">Toptraits · Teacher</span>
        <span class="td-live">● {{ $teacher['name'] }} · {{ $subject }}</span>
        <div class="td-copilot">
          <input class="td-input" type="text" wire:model="question" placeholder="✦ Ask Copilot… e.g. “who is absent in 8A”, “low marks in 6A”, “worksheet on Fractions for 7B”" aria-label="Ask teacher Copilot" wire:keydown.enter="askCopilot">
          <button type="button" class="td-btn primary" wire:click="askCopilot" wire:loading.attr="disabled" wire:target="askCopilot,askSuggested">➤ Ask</button>
        </div>
      </div>
      <div class="td-chips" style="margin-top:-6px">
        @foreach (["What is my day today?", "What's my next class?", 'Homework not submitted', 'Marks entry pending', 'Low marks in ' . ($classTeacherOf ?? ($mySections[0] ?? '8-A')), 'help'] as $chip)
          <button type="button" class="td-chip" wire:click="askSuggested(@js($chip))">{{ $chip }}</button>
        @endforeach
      </div>
      <div wire:loading.flex wire:target="askCopilot,askSuggested" class="td-answer">✦ Thinking…</div>
      @if ($copilotError !== '')<div class="td-alert error" role="alert">{{ $copilotError }}</div>@endif
      @if ($copilotAnswer !== '')
        <div class="td-answer" role="status" wire:loading.remove wire:target="askCopilot,askSuggested">
          <div class="td-answer-head"><strong>✦ Copilot · {{ $question }}</strong><button type="button" class="td-btn" wire:click="clearCopilot">Close</button></div>
          <div class="td-answer-text">{{ $copilotAnswer }}</div>
        </div>
      @endif

      {{-- ============================== TODAY ============================== --}}
      @if ($activeTab === 'today')
        @php $cur = $day['current']; $nxt = $day['next']; @endphp
        <div class="td-now">
          <div class="td-now-card">
            <div class="td-eyebrow">{{ $now->format('l, d M') }} · {{ $now->format('H:i') }}</div>
            @if ($day['on_leave'])
              <div class="td-big">You are on leave today</div><div class="td-muted">Substitutes are covering your periods.</div>
            @elseif ($cur)
              <div class="td-big">Now: {{ $cur['class'] }} {{ $cur['subject'] }}</div>
              <div class="td-muted">{{ $cur['time'] }} · {{ $cur['room'] }}{{ ! empty($cur['is_cover']) ? ' · cover for ' . $cur['absent_teacher'] : '' }}</div>
            @elseif ($nxt)
              <div class="td-big">Free now · next at {{ $nxt['start'] }}</div><div class="td-muted">Good time for marks entry or worksheets.</div>
            @else
              <div class="td-big">{{ $now->isSunday() ? 'No school today' : 'All periods done for today' }}</div><div class="td-muted">{{ $day['done'] }} periods completed.</div>
            @endif
            <div class="td-timeline">
              @forelse ($day['periods'] as $p)
                <span class="td-slot {{ $p['status'] === 'Completed' ? 'done' : '' }} {{ $p['status'] === 'In progress' ? 'now' : '' }} {{ ! empty($p['is_cover']) ? 'cover' : '' }}" title="{{ $p['subject'] }} · {{ $p['room'] }}">{{ $p['start'] }} · {{ $p['class'] }}{{ ! empty($p['is_cover']) ? ' · cover' : '' }}</span>
              @empty
                <span class="td-muted">No periods today.</span>
              @endforelse
            </div>
          </div>
          <div class="td-now-card next">
            <div class="td-card-label">Next period</div>
            @if ($nxt)
              <div class="td-card-title">{{ $nxt['class'] }} {{ $nxt['subject'] }}</div>
              <div class="td-card-sub">{{ $nxt['time'] }} · {{ $nxt['room'] }}{{ ! empty($nxt['is_cover']) ? ' · COVER for ' . $nxt['absent_teacher'] : '' }}</div>
            @else
              <div class="td-card-title">—</div><div class="td-card-sub">No more periods today</div>
            @endif
          </div>
          <div class="td-now-card next">
            <div class="td-card-label">Class teacher of</div>
            <div class="td-card-title">{{ $classTeacherOf ?? '—' }}</div>
            <div class="td-card-sub">You teach {{ $subject }} in {{ implode(', ', $mySections) }}</div>
          </div>
        </div>

        @if ($prep)
          @php $pp = $prep['period']; @endphp
          <section class="td-section td-prep">
            <div class="td-head" style="margin-bottom:8px">
              <div>
                <div class="td-eyebrow">{{ $pp['status'] === 'In progress' ? 'In class now' : 'Prepare for your next class' }}</div>
                <h2 class="td-section-title" style="margin:4px 0 0">{{ $pp['start'] }} · {{ $pp['class'] }} {{ $pp['subject'] }} · {{ $pp['room'] }}
                  @if ($prep['is_cover'])<span class="td-pill warn" style="margin-left:6px">Cover for {{ $pp['absent_teacher'] }}</span>@endif
                </h2>
              </div>
              <div class="td-chips">
                <span class="td-pill info">Topic: {{ $prep['chapter'] }}</span>
                @if ($prep['missed_last'])<span class="td-pill warn">{{ count($prep['missed_last']) }} missed last class</span>@endif
                @if ($prep['weak'])<span class="td-pill risk">{{ count($prep['weak']) }} need support</span>@endif
                @if ($prep['homework_missing'])<span class="td-pill warn">{{ $prep['homework_missing'] }} homework missing</span>@endif
              </div>
            </div>
            <ul class="td-tips">
              @foreach ($prep['tips'] as $tip)<li>{{ $tip }}</li>@endforeach
            </ul>
            @unless ($prep['is_cover'])
              <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
                <button type="button" class="td-btn" wire:click="setTab('progress', @js($pp['class']))">View {{ $pp['class'] }} students</button>
                <button type="button" class="td-btn" wire:click="setTab('worksheet', @js($pp['class']))">✦ Worksheet for {{ $pp['class'] }}</button>
              </div>
            @endunless
          </section>
        @endif

        <div class="td-grid four">
          <button type="button" class="td-kpi" wire:click="setTab('timetable')"><span class="td-card-label">Periods today</span><b>{{ $kpis['periods'] }}</b><span class="td-muted">{{ $kpis['done'] }} done</span></button>
          <button type="button" class="td-kpi" wire:click="setTab('progress')"><span class="td-card-label">Students to watch</span><b style="color:{{ $kpis['watch'] ? 'var(--td-amber)' : 'var(--td-green)' }}">{{ $kpis['watch'] }}</b><span class="td-muted">in {{ $progressSection }}</span></button>
          <button type="button" class="td-kpi" wire:click="setTab('scan')"><span class="td-card-label">Papers to mark</span><b style="color:{{ $kpis['marks'] ? 'var(--td-red)' : 'var(--td-green)' }}">{{ $kpis['marks'] }}</b><span class="td-muted">marks entry pending</span></button>
          <button type="button" class="td-kpi" wire:click="setTab('homework')"><span class="td-card-label">Homework missing</span><b>{{ $kpis['homework'] }}</b><span class="td-muted">due in the next 2 days</span></button>
        </div>

        <section class="td-section">
          <h2 class="td-section-title">Needs your attention <span class="td-muted" style="font-weight:500">— most urgent first</span></h2>
          @forelse ($attention as $item)
            <div class="td-attention">
              <span class="td-dot {{ $item['level'] }}"></span>
              <div class="td-att-content"><div class="td-att-title">{{ $item['title'] }}</div><div class="td-att-detail">{{ $item['detail'] }}</div></div>
              <button type="button" class="td-btn" wire:click="setTab('{{ $item['tab'] }}', @js($item['section'] ?? null))">{{ $item['action'] }}</button>
            </div>
          @empty
            <p class="td-muted">All caught up. 🎉</p>
          @endforelse
        </section>

      {{-- ============================== CLASSES ============================== --}}
      @elseif ($activeTab === 'classes')
        <section class="td-section">
          <h2 class="td-section-title">My Classes · {{ $subject }}</h2>
          <div class="td-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
            @foreach ($classCards as $c)
              <div class="td-card">
                <div class="td-card-label">Class {{ $c['section'] }} @if ($c['is_class_teacher'])<span class="td-pill info" style="margin-left:4px">Class teacher</span>@endif</div>
                <div class="td-card-title">{{ $c['subject'] }}</div>
                <div class="td-card-sub">{{ $c['students'] }} students · attendance {{ $c['attendance'] }}% · {{ $c['next'] }}</div>
                <div style="display:flex;justify-content:space-between;margin-top:12px"><span class="td-muted">Class average</span><strong>{{ $c['average'] }}%</strong></div>
                <div class="td-progress"><span style="width:{{ $c['average'] }}%"></span></div>
                @if ($c['syllabus'])
                  <div style="display:flex;justify-content:space-between;margin-top:12px"><span class="td-muted">Syllabus</span><strong>{{ $c['syllabus']['completion'] }}% <span class="td-muted" style="font-weight:500">/ {{ $c['syllabus']['expected'] }}% expected</span></strong></div>
                  <div class="td-progress"><span style="width:{{ $c['syllabus']['completion'] }}%;background:{{ $c['syllabus']['status'] === 'Needs review' ? 'var(--td-amber)' : 'var(--td-blue)' }}"></span><i style="left:{{ $c['syllabus']['expected'] }}%"></i></div>
                  <div class="td-card-sub">Now: {{ $c['syllabus']['chapter'] }}</div>
                @endif
                <div style="margin-top:10px">@if ($c['below40'])<span class="td-pill risk">{{ $c['below40'] }} below 40%</span>@else<span class="td-pill ok">Nobody below 40%</span>@endif</div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px">
                  <button type="button" class="td-btn" wire:click="setTab('progress', @js($c['section']))">Progress</button>
                  <button type="button" class="td-btn" wire:click="setTab('worksheet', @js($c['section']))">Worksheet</button>
                  @if ($c['is_class_teacher'])<button type="button" class="td-btn primary" wire:click="setTab('attendance', @js($c['section']))">Attendance</button>@endif
                </div>
              </div>
            @endforeach
          </div>
          <p class="td-muted" style="margin-top:10px">Amber line on the syllabus bar = where the class should be by today.</p>
        </section>

      {{-- ============================== ATTENDANCE ============================== --}}
      @elseif ($activeTab === 'attendance')
        @php $counts = array_count_values($attendance); @endphp
        <section class="td-section">
          <div class="td-head">
            <div><h2 class="td-section-title" style="margin-bottom:3px">Take Attendance · {{ $attendanceSection }} · {{ $now->format('D d M') }}</h2>
              <p class="td-muted">Hints come from the last 30 school days. Tap P / A / L for each student.</p></div>
            <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
              <div class="td-field"><label>Section</label><select class="td-input" wire:model.live="attendanceSection">@foreach ($mySections as $s)<option value="{{ $s }}">{{ $s }}{{ $s === $classTeacherOf ? ' (my class)' : '' }}</option>@endforeach</select></div>
              <button type="button" class="td-btn" wire:click="markAll('present')">Mark all present</button>
            </div>
          </div>
          @if ($registerSubmitted)
            <div class="td-notice" style="margin:0 0 12px">Submitted at {{ date('H:i', $registerSubmitted['at']) }}. You can change marks and submit again.</div>
          @endif
          <div class="td-table-wrap"><table class="td-table">
            <thead><tr><th>Student</th><th>Last 5 days</th><th>30-day %</th><th>Hint</th><th>Today</th></tr></thead>
            <tbody>
            @foreach ($registerRows as $r)
              @php $m = $attendance[$r['id']] ?? 'present'; @endphp
              <tr wire:key="reg-{{ $r['id'] }}">
                <td><strong>{{ $r['name'] }}</strong><div class="td-muted" style="font-size:11px">{{ $r['id'] }}</div></td>
                <td><div class="td-last5">@foreach ($r['last5'] as $date => $mark)<span class="{{ $mark }}" title="{{ $date }}">{{ $mark }}</span>@endforeach</div></td>
                <td>{{ $r['pct'] }}%</td>
                <td>@if ($r['hint'])<span class="td-pill {{ $r['hint'][0] }}">{{ $r['hint'][1] }}</span>@else<span class="td-muted">—</span>@endif</td>
                <td><div class="td-toggle">
                  @foreach (['present' => 'P', 'absent' => 'A', 'late' => 'L'] as $val => $short)
                    <button type="button" class="{{ $m === $val ? 'on ' . $val : '' }}" wire:click="setMark(@js($r['id']), '{{ $val }}')">{{ $short }}</button>
                  @endforeach
                </div></td>
              </tr>
            @endforeach
            </tbody>
          </table></div>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px">
            <span class="td-pill ok">{{ $counts['present'] ?? 0 }} present</span><span class="td-pill risk">{{ $counts['absent'] ?? 0 }} absent</span><span class="td-pill warn">{{ $counts['late'] ?? 0 }} late</span>
            <span style="margin-left:auto;display:flex;gap:8px">
              @if ($registerSubmitted && ($counts['absent'] ?? 0) > 0)<button type="button" class="td-btn" wire:click="notifyAbsentParents">Notify parents of absentees</button>@endif
              <button type="button" class="td-btn primary" wire:click="submitAttendance">{{ $registerSubmitted ? 'Update register' : 'Submit register' }}</button>
            </span>
          </div>
        </section>

      {{-- ============================== PROGRESS ============================== --}}
      @elseif ($activeTab === 'progress')
        <section class="td-section">
          <div class="td-head">
            <div><h2 class="td-section-title" style="margin-bottom:3px">Student Progress · {{ $progressSection }}</h2><p class="td-muted">Highest concern first. “{{ $subject }}” is your subject; “Other subjects” is their average elsewhere.</p></div>
            <div class="td-chips">@foreach ($mySections as $s)<button type="button" class="td-chip {{ $progressSection === $s ? 'active' : '' }}" wire:click="$set('progressSection', '{{ $s }}')">{{ $s }}</button>@endforeach</div>
          </div>
          <div class="td-table-wrap"><table class="td-table">
            <thead><tr><th>Student</th><th>Attendance</th><th>{{ $subject }}</th><th>Other subjects</th><th>Signal</th><th>Suggested next step</th></tr></thead>
            <tbody>
            @foreach ($progressRows as $r)
              <tr wire:key="pr-{{ $r['id'] }}">
                <td><strong>{{ $r['name'] }}</strong><div class="td-muted" style="font-size:11px">{{ $r['id'] }}</div></td>
                <td><span class="td-pill {{ $r['attendance'] < 75 ? 'risk' : ($r['attendance'] < 85 ? 'warn' : 'ok') }}">{{ $r['attendance'] }}%</span></td>
                <td><strong style="color:{{ ($r['my_subject'] ?? 100) < 40 ? 'var(--td-red)' : 'inherit' }}">{{ $r['my_subject'] ?? '—' }}%</strong></td>
                <td>{{ $r['others_avg'] ?? '—' }}%</td>
                <td><span class="td-pill {{ $r['signal_type'] }}" title="{{ implode(', ', $r['reasons']) }}">{{ $r['signal'] }}</span></td>
                <td style="max-width:320px">{{ $r['teacher_note'] }}</td>
              </tr>
            @endforeach
            </tbody>
          </table></div>
          <p class="td-muted" style="margin-top:8px">Signals combine attendance and marks. They are prompts for a conversation, not a diagnosis.</p>
        </section>

      {{-- ============================== HOMEWORK ============================== --}}
      @elseif ($activeTab === 'homework')
        <section class="td-section">
          <h2 class="td-section-title">Homework · due soonest first</h2>
          <div class="td-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
            @foreach ($homework as $h)
              @php $pct = $h['total'] ? round($h['submitted'] / $h['total'] * 100) : 0; @endphp
              <div class="td-card" wire:key="hw-{{ $h['id'] }}">
                <div class="td-card-label">{{ $h['class'] }} · due {{ $h['due'] }}{{ $h['due_in'] === 1 ? ' (tomorrow)' : '' }}</div>
                <div class="td-card-title" style="font-size:16px">{{ $h['title'] }}</div>
                <div style="display:flex;justify-content:space-between;margin-top:10px"><span class="td-muted">Submitted</span><strong>{{ $h['submitted'] }}/{{ $h['total'] }}</strong></div>
                <div class="td-progress"><span style="width:{{ $pct }}%;background:{{ $pct === 100 ? 'var(--td-green)' : 'var(--td-blue)' }}"></span></div>
                @if ($h['missing'])
                  <div class="td-card-sub" style="margin-top:10px">Missing: {{ implode(', ', array_column($h['missing'], 'name')) }}</div>
                  <button type="button" class="td-btn" style="margin-top:10px" wire:click="remindHomework(@js($h['id']))">Remind {{ count($h['missing']) }}</button>
                @else
                  <div style="margin-top:10px"><span class="td-pill ok">Everyone submitted</span></div>
                @endif
              </div>
            @endforeach
          </div>
        </section>

      {{-- ============================== WORKSHEET ============================== --}}
      @elseif ($activeTab === 'worksheet')
        <section class="td-section">
          <div class="td-head">
            <div><h2 class="td-section-title" style="margin-bottom:3px">AI Worksheet Maker · 3 levels</h2><p class="td-muted">Topic is suggested from where {{ $worksheetSection }} is in the syllabus. Students are grouped by their {{ $subject }} marks.</p></div>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div class="td-field"><label>Section</label><select class="td-input" wire:model.live="worksheetSection">@foreach ($mySections as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div>
            <div class="td-field" style="flex:1;min-width:220px"><label>Topic</label><input class="td-input" wire:model="worksheetTopic"></div>
            <div class="td-field" style="min-width:120px"><label>Questions per level</label><select class="td-input" wire:model="worksheetCount">@foreach ([2, 3, 4, 5] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach</select></div>
            <button type="button" class="td-btn primary" wire:click="generateWorksheet" wire:loading.attr="disabled" wire:target="generateWorksheet">
              <span wire:loading.remove wire:target="generateWorksheet">✦ Generate with AI</span>
              <span wire:loading wire:target="generateWorksheet">✦ Writing questions…</span>
            </button>
          </div>
          <div wire:loading.flex wire:target="generateWorksheet" class="td-notice" style="gap:8px;align-items:center">✦ AI is writing {{ $worksheetCount }} questions per level for {{ $worksheetSection }} on “{{ $worksheetTopic }}”. This can take up to a minute.</div>
          @if ($worksheetSource !== '')
            <div class="td-notice" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap" wire:loading.remove wire:target="generateWorksheet">
              <span class="td-pill {{ $worksheetSource === 'ai' ? 'ok' : 'warn' }}">{{ $worksheetSource === 'ai' ? '✦ AI generated' : 'Template questions' }}</span>
              <span>{{ $worksheetNote }}</span>
              <label style="margin-left:auto;display:flex;gap:6px;align-items:center;cursor:pointer"><input type="checkbox" wire:model.live="showAnswers"> Show answer key</label>
            </div>
          @endif
          <div class="td-ws" style="margin-top:14px">
            @foreach (['Support' => 'support', 'Core' => 'core', 'Challenge' => 'challenge'] as $g => $cls)
              <div class="td-card {{ $cls }}">
                <div class="td-card-label">{{ $g }} · {{ count($worksheetPlan['groups'][$g]) }} {{ count($worksheetPlan['groups'][$g]) === 1 ? 'student' : 'students' }}</div>
                <div class="td-card-sub">{{ ['Support' => 'Below 50% — basics and confidence', 'Core' => '50–79% — textbook level', 'Challenge' => '80%+ — thinking questions'][$g] }}</div>
                <div class="td-card-sub" style="color:var(--td-ink)">{{ $worksheetPlan['groups'][$g] ? implode(', ', $worksheetPlan['groups'][$g]) : 'Nobody in this group' }}</div>
                @if ($worksheetItems)
                  <div class="td-q-list">
                    @foreach ($worksheetItems[$g] ?? [] as $i => $qn)
                      <div class="td-q" wire:key="q-{{ $g }}-{{ $i }}">
                        <span class="td-q-no">{{ $i + 1 }}</span>
                        <div style="display:grid;gap:4px">
                          <textarea class="td-input" rows="3" wire:model.blur="worksheetItems.{{ $g }}.{{ $i }}" placeholder="Type a question…"></textarea>
                          @if ($showAnswers)
                            <textarea class="td-input td-answer-box" rows="2" wire:model.blur="worksheetAnswers.{{ $g }}.{{ $i }}" placeholder="Answer (for the teacher)"></textarea>
                          @endif
                        </div>
                        <button type="button" class="td-btn ghost" title="Remove question" wire:click="removeQuestion('{{ $g }}', {{ $i }})">✕</button>
                      </div>
                    @endforeach
                    <button type="button" class="td-btn" wire:click="addQuestion('{{ $g }}')">+ Add question</button>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
          @if ($worksheetItems)
            <div style="display:flex;gap:10px;align-items:center;margin-top:12px;flex-wrap:wrap">
              <button type="button" class="td-btn primary" onclick="window.print()">🖨 Print worksheet</button>
              <button type="button" class="td-btn" wire:click="generateWorksheet" wire:confirm="Replace your edited questions with fresh ones?">↻ Start again</button>
              <span class="td-muted">Click any question to change it. Changes are kept while you stay on this page.</span>
            </div>
            <div class="td-print">
              @foreach ($worksheetItems as $g => $qs)
                <h3>{{ $worksheetSection }} · {{ $worksheetTopic }} · {{ $g }} level</h3>
                <ol>@foreach (array_filter($qs, fn ($q) => trim($q) !== '') as $qn)<li>{{ $qn }}</li>@endforeach</ol>
              @endforeach
              @if ($showAnswers)
                <div style="page-break-before:always"></div>
                <h2>Answer key · {{ $worksheetSection }} · {{ $worksheetTopic }}</h2>
                @foreach ($worksheetItems as $g => $qs)
                  <h3>{{ $g }} level</h3>
                  <ol>@foreach ($qs as $i => $qn)@if (trim($qn) !== '')<li>{{ trim($worksheetAnswers[$g][$i] ?? '') !== '' ? $worksheetAnswers[$g][$i] : '—' }}</li>@endif @endforeach</ol>
                @endforeach
              @endif
            </div>
          @endif
        </section>

      {{-- ============================== MARKS / SCAN ============================== --}}
      @elseif ($activeTab === 'scan')
        <section class="td-section">
          <h2 class="td-section-title">Marks & Answer Sheets · your papers</h2>
          <div class="td-table-wrap"><table class="td-table">
            <thead><tr><th>Exam</th><th>Class</th><th>Subject</th><th>Exam date</th><th>Stage</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($marksQueue as $e)
              <tr>
                <td>{{ $e['exam'] }}</td><td>{{ $e['class'] }}</td><td>{{ $e['subject'] }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($e['date'])->format('d M') }}</td>
                <td><span class="td-pill {{ $statusPill($e['status']) }}">{{ $e['status'] }}</span></td>
                <td>@if ($e['status'] === 'Marks entry')<button type="button" class="td-btn primary" wire:click="startMarksEntry(@js($e['class']), @js($e['subject']))">Enter marks</button>@else<span class="td-muted">{{ $e['status'] === 'Scheduled' ? 'After the exam' : 'With moderator' }}</span>@endif</td>
              </tr>
            @empty
              <tr><td colspan="6" class="td-muted" style="text-align:center;padding:20px">No papers in the next 30 days.</td></tr>
            @endforelse
            </tbody>
          </table></div>
          @if ($scanMessage !== '')<div class="td-notice">{{ $scanMessage }}</div>@endif
          <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-top:14px">
            <div class="td-field" style="flex:1;min-width:240px"><label>Upload scanned answer sheets (image / PDF)</label><input class="td-input" type="file" accept="image/*,.pdf" multiple></div>
            <span class="td-pill warn">OCR not connected yet</span>
          </div>
        </section>

      {{-- ============================== TIMETABLE ============================== --}}
      @elseif ($activeTab === 'timetable')
        <section class="td-section">
          <div class="td-head">
            <div><h2 class="td-section-title" style="margin-bottom:3px">My Timetable · {{ $ttDateObj->format('l, d M Y') }}</h2><p class="td-muted">Your own periods, including cover duty. Pick any date.</p></div>
            <div style="display:flex;gap:6px;align-items:center">
              <button type="button" class="td-btn" wire:click="shiftTtDate(-1)">‹</button>
              <input type="date" class="td-input" style="width:auto" value="{{ $ttDateObj->toDateString() }}" wire:change="$set('ttDate', $event.target.value)" wire:key="ttd-{{ $ttDateObj->toDateString() }}">
              <button type="button" class="td-btn" wire:click="shiftTtDate(1)">›</button>
              @unless ($ttDay['is_today'])<button type="button" class="td-btn primary" wire:click="resetTtDate">Today</button>@endunless
            </div>
          </div>
          <div class="td-table-wrap"><table class="td-table">
            <thead><tr><th>Time</th><th>Class</th><th>Subject</th><th>Room</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($ttDay['periods'] as $p)
              <tr><td>{{ $p['time'] }}</td><td>{{ $p['class'] }}</td><td><strong>{{ $p['subject'] }}</strong>@if (! empty($p['is_cover']))<span class="td-pill warn" style="margin-left:6px">Cover for {{ $p['absent_teacher'] }}</span>@endif</td><td>{{ $p['room'] }}</td><td><span class="td-pill {{ $statusPill($p['status']) }}">{{ $p['status'] }}</span></td></tr>
            @empty
              <tr><td colspan="5" class="td-muted" style="text-align:center;padding:20px">{{ $ttDateObj->isSunday() ? 'Sunday — no classes.' : 'No periods for you on this day.' }}</td></tr>
            @endforelse
            </tbody>
          </table></div>
        </section>
        <section class="td-section">
          <h3 class="td-section-title">My week</h3>
          @php $slots = collect($ttWeek)->flatMap(fn ($d) => array_keys($d['periods']))->unique()->sort()->values(); @endphp
          <div class="td-table-wrap"><table class="td-table td-week">
            <thead><tr><th>Period</th>@foreach ($ttWeek as $d => $w)<th class="{{ $d === $ttDateObj->toDateString() ? 'on' : '' }}"><button type="button" class="td-link" wire:click="$set('ttDate', '{{ $d }}')">{{ $w['label'] }}</button></th>@endforeach</tr></thead>
            <tbody>
            @foreach ($slots as $slot)
              <tr><td class="td-muted">{{ $slot }}</td>
                @foreach ($ttWeek as $d => $w)
                  @php $p = $w['periods'][$slot] ?? null; @endphp
                  <td class="{{ $d === $ttDateObj->toDateString() ? 'on' : '' }}">@if ($p)<strong>{{ $p['class'] }}</strong><div class="td-muted" style="font-size:11px">{{ $p['room'] }}@if (! empty($p['is_cover'])) · <span style="color:var(--td-amber)">cover</span>@endif</div>@else<span class="td-muted">free</span>@endif</td>
                @endforeach
              </tr>
            @endforeach
            </tbody>
          </table></div>
        </section>
      @endif

      <div class="td-muted">Teacher workspace · demo data generated from today's date. Connect attendance, homework, marks entry and OCR to the school services before real use.</div>
    </main>
  </div>
</div>
</x-filament-panels::page>
