<x-filament-panels::page>
@php
  $icons = [
    'home' => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'admissions' => '<path d="M15 19v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1"/><circle cx="8.5" cy="7" r="3.5"/><path d="M19 8v6M22 11h-6"/>',
    'students' => '<path d="M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="10" cy="7" r="3.5"/><path d="M21 20v-1a4 4 0 0 0-3-3.8M16 3.2a3.5 3.5 0 0 1 0 6.6"/>',
    'fees' => '<path d="M6 4h12M6 9h12M14 4c3 0 4 2.5 4 5s-2 5-6 5H7l8 7"/>',
    'requests' => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
    'staff' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
    'data' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
    'messages' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L3 21l1.9-6.4A8 8 0 1 1 21 12z"/>',
  ];
  $tabs = ['home' => 'Today', 'admissions' => 'Admissions', 'students' => 'Students', 'fees' => 'Fees & payments', 'requests' => 'Certificates', 'staff' => 'Staff', 'data' => 'Data & import', 'messages' => 'Smart messages'];
  $toneClass = fn ($t) => match ($t) { 'risk' => 'risk', 'warn' => 'warn', 'info' => 'info', default => '' };
@endphp
<style>
:root{color-scheme:dark;--ad-bg:#091411;--ad-card:#11221e;--ad-card2:#142823;--ad-ink:#eff8f5;--ad-mut:#98b0aa;--ad-line:#253d37;--ad-teal:#53bdbd;--ad-ok:#4fd195;--ad-warn:#f2b254;--ad-risk:#ff7f91;--ad-info:#7fb3ff}
body:has(.ad-admin-page){background:var(--ad-bg)!important;color:var(--ad-ink)!important}
body:has(.ad-admin-page) .fi-sidebar,body:has(.ad-admin-page) .fi-sidebar-close-overlay,body:has(.ad-admin-page) .fi-header,body:has(.ad-admin-page) .fi-breadcrumbs{display:none!important}
body:has(.ad-admin-page) .fi-main,body:has(.ad-admin-page) .fi-main-ctn,body:has(.ad-admin-page) .fi-page,body:has(.ad-admin-page) .fi-page-content{margin-inline-start:0!important;width:100%!important;max-width:none!important;background:var(--ad-bg)!important;color:var(--ad-ink)!important}
body:has(.ad-admin-page) .fi-topbar{background:var(--ad-bg)!important;border-bottom:1px solid var(--ad-line)!important}
body:has(.ad-admin-page) .fi-topbar .fi-topbar-start,body:has(.ad-admin-page) .fi-topbar .fi-logo{display:none!important}
.ad-admin-page,.ad-admin-page *{box-sizing:border-box}.ad-admin-page{font:14px/1.45 system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ad-ink)}
.ad-layout{display:grid;grid-template-columns:215px minmax(0,1fr);gap:18px;min-height:calc(100vh - 105px)}
.ad-sidebar{background:#0b1714;border:1px solid #172c26;border-radius:14px;padding:14px 10px;display:flex;flex-direction:column;gap:4px;position:sticky;top:12px;align-self:start}
.ad-brand{font-size:11px;letter-spacing:.12em;font-weight:800;color:var(--ad-mut);padding:6px 10px 12px}
.ad-nav{display:flex;align-items:center;gap:10px;border:1px solid transparent;background:transparent;color:var(--ad-mut);border-radius:9px;min-height:40px;padding:8px 10px;text-align:left;font-weight:650;cursor:pointer;width:100%}
.ad-nav:hover{background:#11231f;color:var(--ad-ink)}.ad-nav.active{background:#123b37;color:var(--ad-teal);border-color:#1d4b45}
.ad-nav svg{width:18px;height:18px;flex:0 0 18px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.ad-badge{margin-left:auto;min-width:20px;padding:1px 6px;border-radius:999px;background:#2b2817;color:var(--ad-warn);font-size:11px;font-weight:800;text-align:center}
.ad-side-note{margin-top:12px;border:1px solid #23564f;background:#102923;border-radius:11px;padding:11px;color:var(--ad-mut);font-size:12px}.ad-side-note b{display:block;color:var(--ad-teal);margin-bottom:3px}
.ad-main{min-width:0;display:flex;flex-direction:column;gap:14px;padding:2px 4px 24px}
.ad-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ad-h1{font-size:25px;font-weight:800;letter-spacing:-.02em;margin:0}.ad-sub{color:var(--ad-mut);font-size:13px;margin-top:3px}
.ad-since{display:inline-block;margin-top:6px;padding:4px 10px;border-radius:999px;background:#123b37;color:var(--ad-teal);font-size:12px;font-weight:650}
.ad-chip-demo{border:1px solid #4b4827;border-radius:999px;padding:4px 10px;color:var(--ad-warn);background:#2b2817;font-size:11px;font-weight:800;white-space:nowrap}
.ad-live{font-size:11px;color:var(--ad-mut);margin-top:6px;text-align:right}.ad-live i{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--ad-ok);margin-right:5px;animation:adp 2s infinite}@keyframes adp{50%{opacity:.3}}
.ad-copilot{background:linear-gradient(135deg,#10302b,#11221e);border:1px solid #23564f;border-radius:14px;padding:12px}
.ad-copilot form{display:flex;gap:8px}.ad-input{flex:1;min-width:0;padding:10px 12px;border:1px solid var(--ad-line);border-radius:10px;background:#091411;color:var(--ad-ink);outline:none}.ad-input:focus{border-color:var(--ad-teal)}
.ad-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}.ad-chip{border:1px solid var(--ad-line);background:#0d1c18;color:var(--ad-mut);border-radius:999px;padding:4px 10px;font-size:12px;cursor:pointer}.ad-chip:hover,.ad-chip.on{color:var(--ad-teal);border-color:#2a6155;background:#102b24}
.ad-answer{margin-top:10px;padding:11px 13px;border-radius:10px;background:#0b1714;border:1px solid var(--ad-line);white-space:pre-line;font-size:13px;position:relative}.ad-answer button{position:absolute;top:6px;right:8px;background:none;border:0;color:var(--ad-mut);cursor:pointer;font-size:16px}
.ad-grid{display:grid;gap:12px}.g4{grid-template-columns:repeat(4,minmax(0,1fr))}.g3{grid-template-columns:repeat(3,minmax(0,1fr))}.g2{grid-template-columns:repeat(2,minmax(0,1fr))}
.ad-card{background:var(--ad-card);border:1px solid var(--ad-line);border-radius:14px;padding:15px;min-width:0}.ad-card.click{cursor:pointer;text-align:left;color:inherit;font:inherit}.ad-card.click:hover{border-color:#3a6b60;background:var(--ad-card2)}
.ad-card h3{margin:0 0 10px;font-size:14px;font-weight:800;display:flex;justify-content:space-between;gap:8px;align-items:center}.ad-card h3 small{font-weight:600;color:var(--ad-mut);font-size:12px}
.ad-label{font-size:12px;color:var(--ad-mut)}.ad-val{margin-top:6px;font-size:25px;font-weight:800;letter-spacing:-.02em}.ad-val small{font-size:13px;font-weight:650;color:var(--ad-mut)}.ad-note{font-size:12px;color:var(--ad-mut);margin-top:3px}
.ad-kpi{border-top:3px solid var(--ad-teal)}.ad-kpi.warn{border-top-color:var(--ad-warn)}.ad-kpi.risk{border-top-color:var(--ad-risk)}.ad-kpi.ok{border-top-color:var(--ad-ok)}
.ad-bar{height:6px;border-radius:999px;background:#29423b;overflow:hidden;margin-top:8px}.ad-bar span{display:block;height:100%;background:var(--ad-teal);border-radius:999px}.ad-bar.warn span{background:var(--ad-warn)}.ad-bar.risk span{background:var(--ad-risk)}
.ad-list{background:var(--ad-card);border:1px solid var(--ad-line);border-radius:14px;overflow:hidden}.ad-list-head{padding:13px 16px;border-bottom:1px solid var(--ad-line);font-size:15px;font-weight:800;display:flex;justify-content:space-between;align-items:center;gap:8px}.ad-list-head small{font-weight:600;color:var(--ad-mut);font-size:12px}
.ad-row{display:flex;align-items:center;gap:13px;padding:12px 16px;border-bottom:1px solid #1e342e}.ad-row:last-child{border-bottom:0}
.ad-ico{width:32px;height:32px;flex:0 0 32px;border-radius:9px;display:grid;place-items:center;font-weight:800;background:#123b37;color:var(--ad-teal)}.ad-ico.warn{background:#3a301a;color:var(--ad-warn)}.ad-ico.risk{background:#3d2228;color:var(--ad-risk)}.ad-ico.info{background:#1a2a44;color:var(--ad-info)}
.ad-copy{flex:1;min-width:0}.ad-copy b{display:block;font-size:13px}.ad-copy span{display:block;margin-top:2px;color:var(--ad-mut);font-size:12px}
.ad-conf{font-size:11px;color:var(--ad-mut);white-space:nowrap}
.ad-btn{border:1px solid #2b4a43;background:#162c26;color:var(--ad-ink);border-radius:9px;padding:8px 12px;font-weight:750;font-size:12px;white-space:nowrap;cursor:pointer}.ad-btn:hover{background:#1b3830}.ad-btn.primary{background:var(--ad-teal);color:#07211e;border-color:var(--ad-teal)}.ad-btn.primary:hover{background:#71d2d2}.ad-btn.sm{padding:5px 9px;font-size:11px}.ad-btn.ghost{background:transparent}.ad-btn.danger{color:var(--ad-risk)}
.ad-btn[disabled]{opacity:.5;cursor:default}
.ad-pill{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:800;background:#183b32;color:var(--ad-ok);white-space:nowrap}.ad-pill.warn{background:#3a301a;color:var(--ad-warn)}.ad-pill.risk{background:#3d2228;color:var(--ad-risk)}.ad-pill.info{background:#1a2a44;color:var(--ad-info)}.ad-pill.mut{background:#1b2a26;color:var(--ad-mut)}
.ad-table-wrap{overflow:auto;border:1px solid var(--ad-line);border-radius:12px}.ad-table{width:100%;min-width:640px;border-collapse:collapse}.ad-table th{background:#0e1d19;color:var(--ad-mut);text-transform:uppercase;letter-spacing:.04em;font-size:11px;text-align:left;padding:10px 12px;white-space:nowrap}.ad-table td{border-top:1px solid var(--ad-line);padding:10px 12px;font-size:13px;vertical-align:top}.ad-table tr.click{cursor:pointer}.ad-table tr.click:hover td{background:#13271f}
.ad-small{font-size:12px;color:var(--ad-mut)}.ad-flag{display:block;font-size:12px;margin-top:2px}.ad-flag.risk{color:var(--ad-risk)}.ad-flag.warn{color:var(--ad-warn)}.ad-flag.ok{color:var(--ad-teal)}
.ad-hbar{display:grid;grid-template-columns:110px 1fr 54px;gap:8px;align-items:center;margin:7px 0;font-size:12px}.ad-hbar .ad-bar{margin:0;height:9px}
.ad-seg{display:inline-flex;border:1px solid var(--ad-line);border-radius:10px;overflow:hidden}.ad-seg button{background:transparent;border:0;color:var(--ad-mut);padding:7px 12px;font-weight:700;font-size:12px;cursor:pointer}.ad-seg button.on{background:#123b37;color:var(--ad-teal)}
.ad-empty{padding:22px;text-align:center;color:var(--ad-mut)}
.ad-pre{white-space:pre-wrap;font:13px/1.6 Georgia,serif;background:#f7f4ea;color:#1c1c1c;border-radius:10px;padding:18px;margin-top:10px}
.ad-textarea{width:100%;min-height:120px;padding:11px;border:1px solid var(--ad-line);border-radius:10px;background:#091411;color:var(--ad-ink);font:13px/1.5 ui-monospace,Consolas,monospace}
.ad-aud{cursor:pointer;text-align:left;color:inherit;font:inherit}.ad-aud.on{border-color:var(--ad-teal);background:#10302b}
.ad-profile{border:1px solid #2a6155;background:#0f2622}
.ad-toolbar{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
select.ad-input{flex:0 0 auto}
@media print{body *{visibility:hidden!important}.ad-pre,.ad-pre *{visibility:visible!important}.ad-pre{position:fixed;inset:0;margin:0;border-radius:0;font-size:15px;padding:48px}}
@media(max-width:1100px){.g4{grid-template-columns:repeat(2,minmax(0,1fr))}.g3{grid-template-columns:1fr 1fr}.ad-layout{grid-template-columns:185px minmax(0,1fr)}}
@media(max-width:760px){.ad-layout{grid-template-columns:1fr}.ad-sidebar{position:static;display:grid;grid-template-columns:1fr 1fr}.ad-brand,.ad-side-note{grid-column:1/-1}.g3,.g2{grid-template-columns:1fr}.ad-row{flex-wrap:wrap}}
@media(max-width:430px){.g4{grid-template-columns:1fr}.ad-sidebar{grid-template-columns:1fr}}
</style>

<div class="ad-admin-page" @if ($activeTab === 'home') wire:poll.120s @endif>
<div class="ad-layout">
  {{-- ============ SIDEBAR ============ --}}
  <aside class="ad-sidebar" aria-label="Admin navigation">
    <div class="ad-brand">ADMIN OFFICE</div>
    @foreach ($tabs as $tab => $label)
      <button type="button" class="ad-nav {{ $activeTab === $tab ? 'active' : '' }}" wire:click="setTab('{{ $tab }}')">
        <svg viewBox="0 0 24 24">{!! $icons[$tab] !!}</svg><span>{{ $label }}</span>
        @if (($badges[$tab] ?? 0) > 0)<span class="ad-badge">{{ $badges[$tab] }}</span>@endif
      </button>
    @endforeach
    <div class="ad-side-note"><b>◈ Live analysis</b>Every screen is worked out from today's data each time you open or click. Demo data until the backend is connected.</div>
  </aside>

  <main class="ad-main">
    {{-- ============ HEADER + COPILOT ============ --}}
    <div class="ad-head">
      <div>
        <h1 class="ad-h1">{{ $activeTab === 'home' ? $greeting : $tabs[$activeTab] }}</h1>
        <div class="ad-sub">{{ $now->format('l, d M Y') }} · {{ $focus }}</div>
        @if ($since && $activeTab === 'home')<span class="ad-since">{{ $since }}</span>@endif
      </div>
      <div><span class="ad-chip-demo">● DEMO DATA</span><div class="ad-live"><i></i>Analysed {{ $now->format('g:i a') }}</div></div>
    </div>

    <section class="ad-copilot">
      <form wire:submit="ask">
        <input class="ad-input" type="text" wire:model="question" placeholder="Ask the office assistant… e.g. “fee defaulters in 8A” or “seats left in class 7”">
        <button class="ad-btn primary" type="submit"><span wire:loading.remove wire:target="ask">Ask</span><span wire:loading wire:target="ask">Thinking…</span></button>
      </form>
      <div class="ad-chips">@foreach ($suggestions as $s)<button type="button" class="ad-chip" wire:click="ask(@js($s))">{{ $s }}</button>@endforeach</div>
      @if ($copilotAnswer !== '')<div class="ad-answer">{{ $copilotAnswer }}<button type="button" wire:click="clearAnswer" title="Close">×</button></div>@endif
    </section>

    {{-- ===================================================== TODAY ===================================================== --}}
    @if ($activeTab === 'home')
      <section class="ad-grid g4">
        @foreach ($kpis as $k)
          <button type="button" class="ad-card click ad-kpi {{ $k['tone'] }}" wire:click="setTab('{{ $k['tab'] }}')">
            <div class="ad-label">{{ $k['label'] }}</div>
            <div class="ad-val">{{ $k['value'] }} @isset($k['sub'])<small>{{ $k['sub'] }}</small>@endisset</div>
            @isset($k['progress'])<div class="ad-bar {{ $k['progress'] < 85 ? 'warn' : '' }}"><span style="width:{{ min(100, $k['progress']) }}%"></span></div>@endisset
            <div class="ad-note">{{ $k['note'] }}</div>
          </button>
        @endforeach
      </section>

      <section class="ad-list">
        <div class="ad-list-head">Needs your decision <small>ranked for {{ $now->format('g a') }} · done items hide until something changes</small></div>
        @forelse ($decisions as $d)
          <div class="ad-row" wire:key="dec-{{ $d['key'] }}">
            <span class="ad-ico {{ $toneClass($d['tone']) }}">{{ $d['icon'] }}</span>
            <div class="ad-copy"><b>{{ $d['title'] }}</b><span>{{ $d['detail'] }}</span></div>
            <span class="ad-conf">{{ $d['confidence'] }}</span>
            <button type="button" class="ad-btn {{ $d['primary'] ? 'primary' : '' }}" wire:click="runDecision(@js($d['key']), @js($d['action']), @js($d['title']))" wire:loading.attr="disabled">{{ $d['button'] }}</button>
          </div>
        @empty
          <div class="ad-empty">✓ All clear. Nothing needs a decision right now.</div>
        @endforelse
      </section>

      <section class="ad-grid g3">
        <div class="ad-card">
          <h3>Attendance by section <small>{{ $att['register_taken'] ? 'today' : 'last school day' }}</small></h3>
          @foreach (collect($att['sections'])->sortBy('rate')->take(5) as $s)
            <div class="ad-hbar"><span>{{ $s['class'] }} <span class="ad-small">· {{ $s['absent'] }} absent</span></span><div class="ad-bar {{ $s['drop'] >= 6 ? 'risk' : ($s['rate'] < 90 ? 'warn' : '') }}"><span style="width:{{ $s['rate'] }}%"></span></div><b>{{ $s['rate'] }}%</b></div>
          @endforeach
          <div class="ad-note">Lowest first. Red = {{ '6+' }} points below the usual for that class.</div>
        </div>
        <div class="ad-card">
          <h3>Fee collection by class <small>due so far</small></h3>
          @foreach (collect($fd['classes'])->sortBy('rate')->take(5) as $c)
            <div class="ad-hbar"><span>{{ $c['class'] }}</span><div class="ad-bar {{ $c['rate'] < 75 ? 'risk' : ($c['rate'] < 88 ? 'warn' : '') }}"><span style="width:{{ $c['rate'] }}%"></span></div><b>{{ $c['rate'] }}%</b></div>
          @endforeach
          <div class="ad-note">Next 7 days: {{ \App\Services\Principal\Insights\Format::inr($fd['summary']['due_next_7']) }} due · expect ≈ {{ \App\Services\Principal\Insights\Format::inr($fd['forecast_7']) }} ({{ $fd['on_time_rate'] }}% usually pay within a week).</div>
        </div>
        <div class="ad-card">
          <h3>Admissions {{ $adm['year'] }} <small>+{{ $adm['new_today'] }} today</small></h3>
          @php $top = max(1, $adm['funnel'][0]['count']); @endphp
          @foreach ($adm['funnel'] as $f)
            <div class="ad-hbar"><span>{{ $f['label'] }}</span><div class="ad-bar"><span style="width:{{ round($f['count'] / $top * 100) }}%"></span></div><b>{{ $f['count'] }}</b></div>
          @endforeach
        </div>
      </section>

      @if ($sentLog)
        <section class="ad-list"><div class="ad-list-head">Recent office activity</div>
          @foreach (array_slice($sentLog, 0, 4) as $l)<div class="ad-row"><span class="ad-ico">✉</span><div class="ad-copy"><b>{{ $l['audience'] }} · {{ $l['count'] }} messages</b><span>{{ $l['at'] }} · “{{ $l['text'] }}…”</span></div></div>@endforeach
        </section>
      @endif

    {{-- ===================================================== ADMISSIONS ===================================================== --}}
    @elseif ($activeTab === 'admissions')
      <section class="ad-grid g4">
        <div class="ad-card ad-kpi"><div class="ad-label">Active applications</div><div class="ad-val">{{ $adm['active'] }}</div><div class="ad-note">Open since {{ $adm['open_since'] }} · {{ $adm['total'] - $adm['active'] }} withdrawn</div></div>
        <div class="ad-card ad-kpi ok"><div class="ad-label">New</div><div class="ad-val">+{{ $adm['new_today'] }} <small>today</small></div><div class="ad-note">{{ $adm['last7'] }} in the last 7 days</div></div>
        <div class="ad-card ad-kpi info"><div class="ad-label">Ready to verify</div><div class="ad-val">{{ count($adm['ready']) }}</div><div class="ad-note">All documents in, no mismatch</div></div>
        <div class="ad-card ad-kpi {{ count($adm['stuck']) ? 'warn' : 'ok' }}"><div class="ad-label">Stuck 7+ days</div><div class="ad-val">{{ count($adm['stuck']) }}</div><div class="ad-note">{{ count($adm['no_contact']) }} enquiries not called in 3 days</div></div>
      </section>

      <section class="ad-grid g2">
        <div class="ad-card">
          <h3>Seats for {{ $adm['year'] }} <small>offers vs seats</small></h3>
          @foreach ($adm['seats'] as $s)
            <div class="ad-hbar" style="grid-template-columns:70px 1fr 150px"><span>Class {{ $s['class'] }}</span><div class="ad-bar {{ $s['state'] === 'over' ? 'risk' : ($s['state'] === 'full' ? 'warn' : '') }}"><span style="width:{{ min(100, round($s['offers'] / max(1, $s['seats']) * 100)) }}%"></span></div>
              <span>{{ $s['offers'] }}/{{ $s['seats'] }} · <span class="ad-pill {{ $s['state'] === 'over' ? 'risk' : ($s['state'] === 'full' ? 'warn' : '') }}">{{ $s['state'] === 'over' ? 'over-booked ' . abs($s['left']) : ($s['left'] . ' left') }}</span></span></div>
          @endforeach
          <div class="ad-note">{{ array_sum(array_column($adm['seats'], 'pipeline')) }} more applicants are still in the pipeline. Watch classes marked amber before sending offers.</div>
        </div>
        <div class="ad-card">
          <h3>Pipeline <small>conversion to offer {{ $adm['conversion'] }}%</small></h3>
          @php $top = max(1, $adm['funnel'][0]['count']); @endphp
          @foreach ($adm['funnel'] as $i => $f)
            <div class="ad-hbar"><span>{{ $f['label'] }}</span><div class="ad-bar"><span style="width:{{ round($f['count'] / $top * 100) }}%"></span></div><b>{{ $f['count'] }}</b></div>
          @endforeach
        </div>
      </section>

      <section class="ad-card">
        <div class="ad-toolbar" style="justify-content:space-between;margin-bottom:12px">
          <div class="ad-chips" style="margin:0">
            @foreach (['all' => 'All', 'ready' => 'Ready to verify · ' . count($adm['ready']), 'docs' => 'Docs pending', 'issues' => 'AI flagged · ' . count($adm['issues']), 'offers' => 'Offers', 'enquiry' => 'Enquiries'] as $k => $l)
              <button type="button" class="ad-chip {{ $admFilter === $k ? 'on' : '' }}" wire:click="setAdmFilter('{{ $k }}')">{{ $l }}</button>
            @endforeach
          </div>
          <div class="ad-toolbar">
            <select class="ad-input" wire:model.live="admClass"><option value="all">All classes</option>@foreach (array_keys($adm['seats']) as $c)<option value="{{ $c }}">Class {{ $c }}</option>@endforeach</select>
            @if (count($adm['ready']))<button type="button" class="ad-btn primary" wire:click="verifyAll" wire:confirm="Verify all {{ count($adm['ready']) }} ready applications?">Verify all ready</button>@endif
          </div>
        </div>
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Applicant</th><th>Class</th><th>Applied</th><th>Stage</th><th>AI document check</th><th></th></tr></thead><tbody>
          @forelse ($apps as $a)
            <tr wire:key="app-{{ $a['id'] }}">
              <td><b>{{ $a['name'] }}</b><div class="ad-small">{{ $a['id'] }} · {{ $a['phone'] }}</div></td>
              <td>{{ $a['class'] }}</td>
              <td>{{ $a['applied_label'] }}<div class="ad-small">{{ $a['age_days'] }}d ago</div></td>
              <td><span class="ad-pill {{ $a['stage'] < 0 ? 'mut' : ($a['ready'] ? 'info' : ($a['stuck'] ? 'warn' : '')) }}">{{ $a['status'] }}</span></td>
              <td>
                @foreach ($a['issues'] as $i)<span class="ad-flag risk">⚠ {{ $i }}</span>@endforeach
                @if ($a['missing'])<span class="ad-flag warn">Missing: {{ implode(', ', $a['missing']) }}</span>@endif
                @foreach ($a['notes'] as $n)<span class="ad-flag ok">✓ {{ $n }}</span>@endforeach
                @if ($a['ready'])<span class="ad-flag ok">All documents match the form · {{ $a['confidence'] }}% sure</span>@endif
                @if (! $a['issues'] && ! $a['missing'] && ! $a['notes'] && ! $a['ready'])<span class="ad-small">—</span>@endif
              </td>
              <td>@if ($a['ready'])<button type="button" class="ad-btn sm primary" wire:click="verifyApplicant('{{ $a['id'] }}')">Verify</button>@endif</td>
            </tr>
          @empty
            <tr><td colspan="6" class="ad-empty">No applicants match this filter.</td></tr>
          @endforelse
        </tbody></table></div>
        @if ($appsTotal > count($apps))<div class="ad-note" style="margin-top:8px">Showing {{ count($apps) }} of {{ $appsTotal }}. Use the filters to narrow down.</div>@endif
      </section>

    {{-- ===================================================== STUDENTS ===================================================== --}}
    @elseif ($activeTab === 'students')
      @if ($profile)
        <section class="ad-card ad-profile">
          <h3>{{ $profile['name'] }} · {{ $profile['class'] }} <button type="button" class="ad-btn sm ghost" wire:click="closeProfile">Close ×</button></h3>
          <div class="ad-small">{{ $profile['id'] }} · Parent {{ $profile['parent'] }} · {{ $profile['phone'] }} · {{ $profile['area'] }} · DOB {{ $profile['dob'] ?? 'missing' }}</div>
          <div class="ad-grid g4" style="margin-top:12px">
            <div class="ad-card"><div class="ad-label">Attendance (30 days)</div><div class="ad-val">{{ $profile['attendance'] }}%</div><div class="ad-note">Today: {{ ['P' => 'present', 'A' => 'absent', 'L' => 'late'][$profile['today']] ?? 'not marked' }}{{ $profile['streak'] >= 2 ? ' · absent ' . $profile['streak'] . ' days running' : '' }}</div></div>
            <div class="ad-card"><div class="ad-label">Average marks</div><div class="ad-val">{{ $profile['average'] }}%</div><div class="ad-note">@foreach ($profile['marks'] as $sub => $m){{ $sub === array_key_first($profile['marks']) ? '' : ' · ' }}{{ ['Mathematics' => 'Maths', 'Science' => 'Sci', 'English' => 'Eng', 'Social Studies' => 'SSt'][$sub] ?? $sub }} {{ $m }}@endforeach</div></div>
            <div class="ad-card"><div class="ad-label">Fees</div>@if ($profile['fee'])<div class="ad-val" style="color:var(--ad-risk)">{{ \App\Services\Principal\Insights\Format::inr($profile['fee']['amount']) }}</div><div class="ad-note">{{ $profile['fee']['days'] }} days · {{ $profile['fee']['heads'] }}</div>@else<div class="ad-val" style="color:var(--ad-ok)">Clear</div><div class="ad-note">Last paid {{ $profile['last_payment'] ? \Carbon\Carbon::parse($profile['last_payment']['paid_on'])->format('d M') : '—' }}</div>@endif</div>
            <div class="ad-card"><div class="ad-label">Office record</div><div class="ad-val">{{ $profile['missing'] ? \App\Services\Principal\Insights\Format::plural(count($profile['missing']), 'gap') : 'Complete' }}</div><div class="ad-note">{{ $profile['missing'] ? 'Missing: ' . implode(', ', $profile['missing']) : 'All fields filled' }}</div></div>
          </div>
          <div class="ad-grid g2" style="margin-top:12px">
            <div><div class="ad-label">What the system suggests</div>
              <p style="margin:6px 0 0;font-size:13px">{{ $profile['risk']['strategy'] ?? 'On track.' }}</p>
              @if ($profile['fee'])<p style="margin:8px 0 0;font-size:13px"><span class="ad-pill {{ $profile['fee']['tone'] }}">{{ $profile['fee']['action'] }}</span> {{ $profile['fee']['why'] }}</p>@endif
            </div>
            <div><div class="ad-label">Linked</div>
              @if ($profile['siblings'])<p style="margin:6px 0 0;font-size:13px">Same parent also has: {{ implode(', ', array_map(fn ($s) => $s['name'] . ' (' . $s['class'] . ')', array_filter($profile['siblings'], fn ($s) => $s['id'] !== $profile['id']))) }}</p>@endif
              @foreach ($profile['requests'] as $r)<p style="margin:6px 0 0;font-size:13px">{{ $r['type'] }} · {{ $r['status'] }} ({{ $r['requested'] }})</p>@endforeach
              @if (! $profile['siblings'] && ! $profile['requests'])<p class="ad-small" style="margin-top:6px">No sibling or open request.</p>@endif
              <div class="ad-toolbar" style="margin-top:10px">
                @if ($profile['fee'] && ! $profile['fee']['sensitive'])<button type="button" class="ad-btn sm" wire:click="markReminded('{{ $profile['id'] }}')">Log fee reminder</button>@endif
                <button type="button" class="ad-btn sm" wire:click="setTab('requests')">Certificates</button>
              </div>
            </div>
          </div>
        </section>
      @endif

      <section class="ad-card">
        <div class="ad-toolbar" style="justify-content:space-between">
          <input class="ad-input" style="max-width:380px" type="search" wire:model.live.debounce.250ms="search" placeholder="Search name, admission no., class or signal">
          <div class="ad-chips" style="margin:0">
            @foreach (['all' => 'All', 'risk' => 'Needs attention', 'fees' => 'Fees overdue', 'attendance' => 'Attendance < 75%', 'records' => 'Record gaps'] as $k => $l)
              <button type="button" class="ad-chip {{ $studentFilter === $k ? 'on' : '' }}" wire:click="setStudentFilter('{{ $k }}')">{{ $l }}</button>
            @endforeach
          </div>
        </div>
        <div class="ad-table-wrap" style="margin-top:12px"><table class="ad-table"><thead><tr><th>Student</th><th>Class</th><th>Today</th><th>Attendance</th><th>Average</th><th>Fees</th><th>Signal</th></tr></thead><tbody>
          @forelse ($students as $s)
            <tr class="click" wire:key="st-{{ $s['id'] }}" wire:click="openProfile('{{ $s['id'] }}')">
              <td><b>{{ $s['name'] }}</b><div class="ad-small">{{ $s['id'] }}@if ($s['missing']) · <span style="color:var(--ad-warn)">{{ count($s['missing']) }} record gap(s)</span>@endif</div></td>
              <td>{{ $s['class'] }}</td>
              <td>@if ($s['today'] === 'A')<span class="ad-pill risk">Absent</span>@elseif ($s['today'] === 'L')<span class="ad-pill warn">Late</span>@elseif ($s['today'] === 'P')<span class="ad-pill">Present</span>@else<span class="ad-small">—</span>@endif</td>
              <td style="color:{{ $s['attendance'] < 75 ? 'var(--ad-risk)' : ($s['attendance'] < 85 ? 'var(--ad-warn)' : 'inherit') }}">{{ $s['attendance'] }}%</td>
              <td>{{ $s['marks'] }}%</td>
              <td>@if ($s['fee'])<span style="color:var(--ad-risk)">{{ \App\Services\Principal\Insights\Format::inr($s['fee']['amount']) }}</span><div class="ad-small">{{ $s['fee']['days'] }}d</div>@else<span class="ad-small">Clear</span>@endif</td>
              <td><span class="ad-pill {{ $toneClass($s['tone']) }}">{{ $s['signal'] }}</span></td>
            </tr>
          @empty
            <tr><td colspan="7" class="ad-empty">No students match.</td></tr>
          @endforelse
        </tbody></table></div>
        <div class="ad-note" style="margin-top:8px">{{ count($students) }} students · sorted by who needs attention first · click a row for the full picture.</div>
      </section>

    {{-- ===================================================== FEES ===================================================== --}}
    @elseif ($activeTab === 'fees')
      @php $fs = $fd['summary']; @endphp
      <section class="ad-grid g4">
        <div class="ad-card ad-kpi {{ $fs['rate'] < 85 ? 'warn' : 'ok' }}"><div class="ad-label">Collected (due so far)</div><div class="ad-val">{{ \App\Services\Principal\Insights\Format::inr($fs['collected']) }} <small>of {{ \App\Services\Principal\Insights\Format::inr($fs['expected']) }}</small></div><div class="ad-bar"><span style="width:{{ $fs['rate'] }}%"></span></div><div class="ad-note">{{ $fs['rate'] }}% collection rate</div></div>
        <div class="ad-card ad-kpi risk"><div class="ad-label">Overdue</div><div class="ad-val">{{ \App\Services\Principal\Insights\Format::inr($fs['overdue']) }}</div><div class="ad-note">{{ \App\Services\Principal\Insights\Format::plural(count($fs['defaulters']), 'family', 'families') }} · {{ count($fs['new_this_week']) }} new this week</div></div>
        <div class="ad-card ad-kpi info"><div class="ad-label">Due in next 7 days</div><div class="ad-val">{{ \App\Services\Principal\Insights\Format::inr($fs['due_next_7']) }}</div><div class="ad-note">Forecast ≈ {{ \App\Services\Principal\Insights\Format::inr($fd['forecast_7']) }} will arrive</div></div>
        <div class="ad-card ad-kpi"><div class="ad-label">Ageing</div>
          @foreach ($fs['buckets'] as $b => $amt)<div class="ad-hbar" style="grid-template-columns:72px 1fr 56px;margin:4px 0"><span>{{ $b }}</span><div class="ad-bar {{ str_starts_with($b, '60') ? 'risk' : (str_starts_with($b, '31') ? 'warn' : '') }}"><span style="width:{{ $fs['overdue'] ? round($amt / $fs['overdue'] * 100) : 0 }}%"></span></div><b>{{ \App\Services\Principal\Insights\Format::inr($amt) }}</b></div>@endforeach
        </div>
      </section>

      <div class="ad-toolbar" style="justify-content:space-between">
        <div class="ad-seg">
          <button type="button" class="{{ $feeView === 'followup' ? 'on' : '' }}" wire:click="setFeeView('followup')">Smart follow-up</button>
          <button type="button" class="{{ $feeView === 'upi' ? 'on' : '' }}" wire:click="setFeeView('upi')">UPI matching · {{ count($upi) }}</button>
          <button type="button" class="{{ $feeView === 'classes' ? 'on' : '' }}" wire:click="setFeeView('classes')">By class</button>
        </div>
        @if ($feeView === 'followup')
          <div class="ad-toolbar">
            <select class="ad-input" wire:model.live="feeClass"><option value="all">All classes</option>@foreach ($fd['classes'] as $c)<option value="{{ $c['class'] }}">{{ $c['class'] }}</option>@endforeach</select>
            <button type="button" class="ad-btn primary" wire:click="runDecision('none', 'msg:fees', '')">Prepare reminders · {{ count($fd['to_remind']) }}</button>
          </div>
        @endif
      </div>

      @if ($feeView === 'followup')
        <section class="ad-grid g3">
          <div class="ad-card"><div class="ad-label">Send a reminder</div><div class="ad-val">{{ count($fd['to_remind']) }}</div><div class="ad-note">Usually pay — a polite SMS is enough.</div></div>
          <div class="ad-card"><div class="ad-label">Call the parent</div><div class="ad-val" style="color:var(--ad-risk)">{{ count($fd['to_call']) }}</div><div class="ad-note">No payment at all this year.</div></div>
          <div class="ad-card"><div class="ad-label">Counsellor first — don't chase</div><div class="ad-val" style="color:var(--ad-warn)">{{ count($fd['sensitive']) }}</div><div class="ad-note">Fees late AND attendance/marks falling. Possible family difficulty.</div></div>
        </section>
        <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Family</th><th>Overdue</th><th>Pay history</th><th>Likely to pay in 7 days</th><th>Suggested action</th><th></th></tr></thead><tbody>
          @forelse ($feeList as $r)
            <tr wire:key="fee-{{ $r['id'] }}">
              <td><b class="click" style="cursor:pointer" wire:click="ask(@js($r['name']))">{{ $r['name'] }}</b> · {{ $r['class'] }}<div class="ad-small">{{ $r['parent'] }} · {{ $r['phone'] }}</div></td>
              <td><b>{{ \App\Services\Principal\Insights\Format::inr($r['amount']) }}</b><div class="ad-small">{{ $r['days'] }} days · {{ $r['heads'] }}</div></td>
              <td class="ad-small">{{ $r['paid_count'] ? $r['paid_count'] . ' paid · avg ' . $r['avg_delay'] . 'd late' : 'Nothing paid this year' }}</td>
              <td><div class="ad-bar {{ $r['likely'] < 35 ? 'risk' : ($r['likely'] < 60 ? 'warn' : '') }}" style="width:110px"><span style="width:{{ $r['likely'] }}%"></span></div><span class="ad-small">{{ $r['likely'] }}%</span></td>
              <td><span class="ad-pill {{ $r['tone'] }}">{{ $r['action'] }}</span><div class="ad-small" style="max-width:300px;margin-top:3px">{{ $r['why'] }}</div></td>
              <td>@if ($r['recently_reminded'])<span class="ad-small">Reminded {{ \Carbon\Carbon::parse($r['reminded'])->format('d M') }}</span>@elseif (! $r['sensitive'])<button type="button" class="ad-btn sm" wire:click="markReminded('{{ $r['id'] }}')">{{ $r['action'] === 'Call parent' ? 'Log call' : 'Log reminder' }}</button>@endif</td>
            </tr>
          @empty
            <tr><td colspan="6" class="ad-empty">No overdue fees{{ $feeClass !== 'all' ? ' in ' . $feeClass : '' }}. 🎉</td></tr>
          @endforelse
        </tbody></table></div>

      @elseif ($feeView === 'upi')
        <section class="ad-list">
          <div class="ad-list-head">Today's UPI / bank lines <small>matched on amount + payer surname + remark</small>
            <button type="button" class="ad-btn primary sm" wire:click="confirmAllUpi">Confirm all auto-matched</button></div>
          @foreach ($upi as $l)
            <div class="ad-row" wire:key="upi-{{ $l['ref'] }}">
              <span class="ad-ico {{ $l['status'] === 'Unmatched' || $l['status'] === 'Rejected' ? 'risk' : ($l['status'] === 'Check' ? 'warn' : '') }}">₹</span>
              <div class="ad-copy">
                <b>₹{{ number_format($l['amount']) }} from {{ $l['payer'] }} <span class="ad-small">· {{ $l['time'] }} · “{{ $l['remark'] ?: 'no remark' }}”</span></b>
                <span>@if ($l['student'])→ {{ $l['student'] }} · {{ $l['head'] }} (₹{{ number_format($l['due']) }})@if ($l['due'] !== $l['amount']) · <b style="display:inline;color:var(--ad-warn)">part payment, ₹{{ number_format($l['due'] - $l['amount']) }} short</b>@endif · @endif{{ $l['why'] }}</span>
              </div>
              <div style="width:90px"><div class="ad-bar {{ $l['confidence'] < 55 ? 'risk' : ($l['confidence'] < 85 ? 'warn' : '') }}"><span style="width:{{ $l['confidence'] }}%"></span></div><span class="ad-small">{{ $l['confidence'] }}% sure</span></div>
              @if ($l['decided'])
                <span class="ad-pill {{ $l['status'] === 'Confirmed' ? '' : 'risk' }}">{{ $l['status'] }}</span>
              @elseif ($l['student'])
                <button type="button" class="ad-btn sm {{ $l['status'] === 'Auto-matched' ? 'primary' : '' }}" wire:click="decideUpi('{{ $l['ref'] }}', 'confirm')">Confirm</button>
                <button type="button" class="ad-btn sm ghost danger" wire:click="decideUpi('{{ $l['ref'] }}', 'reject')">Not this</button>
              @else
                <span class="ad-pill risk">Find manually</span>
              @endif
            </div>
          @endforeach
        </section>

      @else
        <section class="ad-card"><h3>Collection by class <small>instalments already due</small></h3>
          @foreach ($fd['classes'] as $c)
            <div class="ad-hbar" style="grid-template-columns:60px 1fr 210px"><span>{{ $c['class'] }}</span><div class="ad-bar {{ $c['rate'] < 75 ? 'risk' : ($c['rate'] < 88 ? 'warn' : '') }}"><span style="width:{{ $c['rate'] }}%"></span></div><span><b>{{ $c['rate'] }}%</b> <span class="ad-small">· {{ \App\Services\Principal\Insights\Format::inr($c['collected']) }} of {{ \App\Services\Principal\Insights\Format::inr($c['expected']) }}</span></span></div>
          @endforeach
        </section>
      @endif

    {{-- ===================================================== CERTIFICATES ===================================================== --}}
    @elseif ($activeTab === 'requests')
      <section class="ad-list">
        <div class="ad-list-head">Certificate & service requests <small>eligibility checked automatically · draft filled from records</small></div>
        @forelse ($requests as $r)
          <div wire:key="rq-{{ $r['id'] }}">
            <div class="ad-row">
              <span class="ad-ico {{ $r['status'] === 'Blocked' ? 'risk' : ($r['status'] === 'Issued' ? '' : ($r['overdue'] ? 'warn' : 'info')) }}">▤</span>
              <div class="ad-copy"><b>{{ $r['type'] }} · {{ $r['student'] }} ({{ $r['class'] }})</b><span>Requested {{ $r['requested'] }}{{ $r['overdue'] ? ' · waiting ' . $r['age'] . ' days' : '' }} · {{ $r['check'] }}</span></div>
              <span class="ad-pill {{ $r['status'] === 'Blocked' ? 'risk' : ($r['status'] === 'Issued' ? 'mut' : 'info') }}">{{ $r['status'] }}{{ $r['issued_on'] ? ' ' . $r['issued_on'] : '' }}</span>
              @if ($r['status'] !== 'Issued' && ! $r['blocked'])
                <button type="button" class="ad-btn sm" wire:click="showDraft('{{ $r['id'] }}')">{{ $draftId === $r['id'] ? 'Hide draft' : 'View draft' }}</button>
                <button type="button" class="ad-btn sm primary" wire:click="issueRequest('{{ $r['id'] }}')">Mark issued</button>
              @elseif ($r['blocked'])
                <button type="button" class="ad-btn sm" wire:click="openStudent('{{ $r['student_id'] }}')">Open student</button>
              @endif
            </div>
            @if ($draftId === $r['id'] && $draft)
              <div style="padding:0 16px 14px"><div class="ad-pre">{{ $draft }}</div><div class="ad-toolbar" style="margin-top:8px"><button type="button" class="ad-btn sm" onclick="window.print()">Print</button><span class="ad-small">Check the details before printing.</span></div></div>
            @endif
          </div>
        @empty
          <div class="ad-empty">No requests.</div>
        @endforelse
      </section>

    {{-- ===================================================== STAFF ===================================================== --}}
    @elseif ($activeTab === 'staff')
      @php $sd = $staffDesk; @endphp
      <section class="ad-grid g4">
        <div class="ad-card ad-kpi ok"><div class="ad-label">Present today</div><div class="ad-val">{{ $sd['present'] }} <small>of {{ count($sd['staff']) }}</small></div></div>
        <div class="ad-card ad-kpi {{ $sd['on_leave'] ? 'warn' : 'ok' }}"><div class="ad-label">On leave</div><div class="ad-val">{{ count($sd['on_leave']) }}</div><div class="ad-note">{{ implode(', ', array_column($sd['on_leave'], 'name')) ?: 'Nobody' }}</div></div>
        <div class="ad-card ad-kpi {{ $sd['unassigned'] ? 'risk' : 'info' }}"><div class="ad-label">Periods needing cover</div><div class="ad-val">{{ count($sd['gaps']) }}</div><div class="ad-note">{{ count($sd['unassigned']) }} with no free teacher</div></div>
        <div class="ad-card ad-kpi {{ collect($sd['compliance'])->where('due', '<', 0)->count() ? 'risk' : 'ok' }}"><div class="ad-label">Compliance due ≤ 30 days</div><div class="ad-val">{{ count($sd['compliance']) }}</div><div class="ad-note">Verification, training, contracts</div></div>
      </section>
      <section class="ad-grid g2">
        <div class="ad-list"><div class="ad-list-head">Cover needed today <small>principal assigns</small></div>
          @forelse ($sd['gaps'] as $g)<div class="ad-row"><span class="ad-ico {{ $g['suggested'] ? 'info' : 'risk' }}">◷</span><div class="ad-copy"><b>{{ $g['time'] }} · {{ $g['class'] }} {{ $g['subject'] }}</b><span>{{ $g['absent_teacher'] }} on leave → {{ $g['suggested'] ? 'suggest ' . $g['suggested'] . ($g['same_subject'] ? ' (same subject)' : '') : 'no free teacher' }}</span></div></div>
          @empty<div class="ad-empty">No cover needed.</div>@endforelse
        </div>
        <div class="ad-list"><div class="ad-list-head">Coming up</div>
          @foreach ($sd['leave_requests'] as $l)<div class="ad-row"><span class="ad-ico warn">✈</span><div class="ad-copy"><b>{{ $l['name'] }} · {{ $l['type'] }}</b><span>{{ $l['date'] }} · {{ $l['department'] }} — plan cover {{ $l['days_away'] <= 2 ? 'now' : ($l['days_away'] <= 7 ? 'this week' : 'next week') }}</span></div></div>@endforeach
          @foreach ($sd['compliance'] as $c)<div class="ad-row"><span class="ad-ico {{ $c['due'] < 0 ? 'risk' : 'info' }}">✓</span><div class="ad-copy"><b>{{ $c['name'] }} · {{ $c['item'] }}</b><span>{{ $c['label'] }}</span></div></div>@endforeach
          @if (! $sd['leave_requests'] && ! $sd['compliance'])<div class="ad-empty">Nothing coming up.</div>@endif
        </div>
      </section>
      <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Name</th><th>Role</th><th>Department</th><th>Classes</th><th>Today</th></tr></thead><tbody>
        @foreach ($sd['staff'] as $m)<tr><td><b>{{ $m['name'] }}</b><div class="ad-small">{{ $m['id'] }}</div></td><td>{{ $m['designation'] }}</td><td>{{ $m['department'] }}</td><td class="ad-small">{{ $m['classes'] }}</td><td><span class="ad-pill {{ $m['status'] === 'Present' ? '' : 'warn' }}">{{ $m['status'] }}</span></td></tr>@endforeach
      </tbody></table></div>

    {{-- ===================================================== DATA & IMPORT ===================================================== --}}
    @elseif ($activeTab === 'data')
      <section class="ad-grid g3">
        <div class="ad-card ad-kpi {{ $health['score'] < 97 ? 'warn' : 'ok' }}"><div class="ad-label">Student records complete</div><div class="ad-val">{{ $health['score'] }}%</div><div class="ad-note">{{ count($health['missing']) }} students with missing fields</div></div>
        <div class="ad-card ad-kpi info"><div class="ad-label">Same parent, more than one child</div><div class="ad-val">{{ count($health['siblings']) }}</div><div class="ad-note">Matched by parent phone</div></div>
        <div class="ad-card ad-kpi {{ $health['duplicates'] ? 'warn' : 'ok' }}"><div class="ad-label">Possible duplicates</div><div class="ad-val">{{ count($health['duplicates']) }}</div><div class="ad-note">Same full name twice</div></div>
      </section>

      <section class="ad-card">
        <h3>Import students <small>paste from Excel or CSV — every row is checked before import</small></h3>
        <textarea class="ad-textarea" wire:model.blur="importText" placeholder="Paste here, with the header row. Any column names work — e.g. Student Name, Std, Div, Mobile, DOB"></textarea>
        <div class="ad-toolbar" style="margin-top:10px">
          <button type="button" class="ad-btn primary" wire:click="checkImport"><span wire:loading.remove wire:target="checkImport">Check rows</span><span wire:loading wire:target="checkImport">Checking…</span></button>
          <button type="button" class="ad-btn" wire:click="loadSample">Try a sample file</button>
          @if (($importResult['auto_fix'] ?? 0) > 0 || ($importResult['duplicates'] ?? 0) > 0)<button type="button" class="ad-btn" wire:click="applyFixes">Apply safe fixes{{ ($importResult['auto_fix'] ?? 0) + ($importResult['duplicates'] ?? 0) ? ' · ' . (($importResult['auto_fix'] ?? 0) + ($importResult['duplicates'] ?? 0)) : '' }}</button>@endif
          @if (($importResult['ready'] ?? 0) > 0)<button type="button" class="ad-btn" wire:click="importReady">Import {{ $importResult['ready'] }} ready rows</button>@endif
        </div>

        @if ($importResult)
          @if (! ($importResult['ok'] ?? false))
            <div class="ad-answer">{{ $importResult['message'] }}</div>
          @else
            <div class="ad-label" style="margin-top:14px">Columns understood ({{ $importResult['confidence'] }}% sure overall · separator “{{ $importResult['delimiter'] }}”)</div>
            <div class="ad-chips">@foreach ($importResult['mapping'] as $m)<span class="ad-pill {{ $m['field'] ? ($m['confidence'] >= 80 ? '' : 'warn') : 'mut' }}">{{ $m['column'] }} → {{ $m['field'] ? str_replace('_', ' ', $m['field']) . ' · ' . $m['confidence'] . '%' : 'ignored' }}</span>@endforeach</div>
            @if ($importResult['missing_fields'])<div class="ad-flag risk" style="margin-top:6px">⚠ Required column not found: {{ implode(', ', $importResult['missing_fields']) }}</div>@endif
            <div class="ad-grid g4" style="margin:12px 0">
              <div class="ad-card"><div class="ad-label">Rows</div><div class="ad-val">{{ $importResult['total'] }}</div></div>
              <div class="ad-card"><div class="ad-label">Ready</div><div class="ad-val" style="color:var(--ad-ok)">{{ $importResult['ready'] }}</div><div class="ad-note">{{ $importResult['auto_fix'] }} after auto-fix</div></div>
              <div class="ad-card"><div class="ad-label">Need you</div><div class="ad-val" style="color:var(--ad-risk)">{{ $importResult['errors'] }}</div></div>
              <div class="ad-card"><div class="ad-label">Duplicates</div><div class="ad-val" style="color:var(--ad-warn)">{{ $importResult['duplicates'] }}</div></div>
            </div>
            <div class="ad-table-wrap"><table class="ad-table"><thead><tr><th>Row</th><th>Name</th><th>Class</th><th>Phone</th><th>Result</th></tr></thead><tbody>
              @foreach ($importResult['rows'] as $r)
                <tr><td>{{ $r['line'] }}</td><td>{{ $r['name'] }}</td><td>{{ $r['class'] }}</td><td>{{ $r['phone'] }}</td>
                  <td><span class="ad-pill {{ ['ok' => '', 'fix' => 'info', 'error' => 'risk', 'duplicate' => 'warn'][$r['status']] }}">{{ ['ok' => 'Ready', 'fix' => 'Auto-fix', 'error' => 'Fix needed', 'duplicate' => 'Duplicate'][$r['status']] }}</span>
                    @foreach ($r['problems'] as $p)<span class="ad-flag risk">{{ $p }}</span>@endforeach
                    @foreach ($r['fixes'] as $f => $v)<span class="ad-flag ok">{{ str_replace('_', ' ', $f) }} → {{ $v }}</span>@endforeach</td></tr>
              @endforeach
            </tbody></table></div>
          @endif
        @endif
      </section>

      <section class="ad-grid g2">
        <div class="ad-list"><div class="ad-list-head">Same parent <small>link → sibling discount + one message per parent</small></div>
          @forelse ($health['siblings'] as $g)
            <div class="ad-row"><span class="ad-ico {{ $g['sure'] === 'strong' ? 'info' : 'warn' }}">⇄</span><div class="ad-copy"><b>{{ $g['parent'] }} · {{ $g['phone'] }}</b><span>{{ implode(' · ', array_map(fn ($s) => $s['name'] . ' (' . $s['class'] . ')', $g['students'])) }}</span><span style="color:{{ $g['sure'] === 'strong' ? 'var(--ad-teal)' : 'var(--ad-warn)' }}">{{ $g['sure'] === 'strong' ? '✓ ' : '? ' }}{{ $g['reason'] }}</span></div><button type="button" class="ad-btn sm" wire:click="linkSiblings(@js($g['key']))">Link</button></div>
          @empty<div class="ad-empty">No parent with more than one unlinked child.</div>@endforelse
        </div>
        <div class="ad-list"><div class="ad-list-head">Records with gaps</div>
          @foreach (array_slice($health['missing'], 0, 10) as $m)
            <div class="ad-row" style="cursor:pointer" wire:click="openStudent('{{ $m['id'] }}')"><span class="ad-ico warn">!</span><div class="ad-copy"><b>{{ $m['name'] }} · {{ $m['class'] }}</b><span>Missing {{ implode(', ', $m['missing']) }}</span></div></div>
          @endforeach
          @foreach ($health['duplicates'] as $g)<div class="ad-row"><span class="ad-ico risk">≡</span><div class="ad-copy"><b>Same name: {{ $g[0]['name'] }}</b><span>{{ implode(' · ', array_map(fn ($s) => $s['id'] . ' (' . $s['class'] . ')', $g)) }} — check if this is one child entered twice</span></div></div>@endforeach
        </div>
      </section>

    {{-- ===================================================== MESSAGES ===================================================== --}}
    @elseif ($activeTab === 'messages')
      <section class="ad-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
        @foreach ($audiences as $key => $a)
          <button type="button" class="ad-card ad-aud {{ $audience === $key ? 'on' : '' }}" wire:click="chooseAudience('{{ $key }}')">
            <div class="ad-label">{{ $a['label'] }}</div><div class="ad-val">{{ count($a['recipients']) }}</div><div class="ad-note">{{ $a['best_time'] }}</div>
          </button>
        @endforeach
      </section>

      <section class="ad-grid g2">
        <div class="ad-card">
          <h3>{{ $aud['label'] }} <small>{{ count($aud['recipients']) }} recipients</small></h3>
          <textarea class="ad-textarea" style="font-family:inherit" wire:model.live.debounce.400ms="messageText"></textarea>
          <div class="ad-small" style="margin-top:6px">Personal fields: {parent} {student} {class} {amount} {days} {pct} {missing} {date} — filled for each family.</div>
          <div class="ad-toolbar" style="margin-top:10px">
            <button type="button" class="ad-btn" wire:click="improveMessage"><span wire:loading.remove wire:target="improveMessage">✦ Improve with AI</span><span wire:loading wire:target="improveMessage">Rewriting…</span></button>
            <button type="button" class="ad-btn primary" wire:click="sendMessage" wire:confirm="Queue {{ count($aud['recipients']) }} messages? (Demo — nothing is delivered.)" @disabled(! count($aud['recipients']))>Send to {{ count($aud['recipients']) }}</button>
            <span class="ad-small">{{ $aud['best_time'] }}</span>
          </div>
          @if ($messageNote !== '')<div class="ad-answer">{{ $messageNote }}</div>@endif
          @if ($aud['excluded'])
            <div class="ad-label" style="margin-top:14px">Not sending to ({{ count($aud['excluded']) }}) — and why</div>
            @foreach (array_slice($aud['excluded'], 0, 8) as $x)<span class="ad-flag warn">{{ $x }}</span>@endforeach
          @endif
        </div>
        <div class="ad-card">
          <h3>Preview <small>exactly what families receive</small></h3>
          @forelse ($previews as $p)
            <div style="margin-bottom:10px"><div class="ad-small">To {{ $p['to'] }}</div><div class="ad-answer" style="margin-top:4px;background:#0f2a24">{{ $p['text'] }}</div></div>
          @empty
            <div class="ad-empty">Nobody in this group today.</div>
          @endforelse
          @if (count($aud['recipients']) > 3)<div class="ad-small">+ {{ count($aud['recipients']) - 3 }} more</div>@endif
        </div>
      </section>

      @if ($sentLog)
        <section class="ad-list"><div class="ad-list-head">Sent log <small>demo</small></div>
          @foreach ($sentLog as $l)<div class="ad-row"><span class="ad-ico">✉</span><div class="ad-copy"><b>{{ $l['audience'] }} · {{ $l['count'] }}</b><span>{{ $l['at'] }} · “{{ $l['text'] }}…”</span></div></div>@endforeach
        </section>
      @endif
    @endif
  </main>
</div>
</div>
</x-filament-panels::page>
