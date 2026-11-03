{{--
    Dynamic "Today" screen. Nothing here is hard-coded: every card, its order, its colour
    and its text come from $this->report (InsightEngine), which re-analyses the data on
    every launch and every click.
--}}
@php
    /** @var \App\Services\Principal\Insights\InsightReport $report */
    $report = $this->report;
    $hero = $report->hero();
    $queue = $report->actionQueue();
    $good = $report->goodNews();
    $categoryLabels = ['fees' => 'Fees', 'attendance' => 'Attendance', 'students' => 'Students', 'exams' => 'Exams', 'staff' => 'Staff', 'academics' => 'Academics'];
    $changeLabels = ['new' => 'New', 'worse' => 'Worse', 'better' => 'Improving', 'same' => null];
@endphp

<div class="pd-today" wire:poll.120s="refreshInsights">

    {{-- 1. Greeting + briefing: different every day, every time of day --}}
    <section class="pd-hello">
        <div class="pd-hello-top">
            <div>
                <div class="pd-eyebrow">{{ $report->generatedAt->format('l, d F') }} · {{ $report->focus }}</div>
                <h1 class="pd-hello-title">Today's Briefing</h1>
                <p class="pd-muted">{{ $report->sinceLastVisit }}</p>
            </div>
            <div class="pd-hello-actions">
                <span class="pd-muted" wire:loading.remove wire:target="refreshInsights">Analysed at {{ $report->generatedAt->format('H:i') }}</span>
                <span class="pd-muted" wire:loading wire:target="refreshInsights">Re-analysing…</span>
                <button type="button" class="pd-btn" wire:click="refreshInsights">↻ Re-analyse</button>
            </div>
        </div>
        <ol class="pd-brief">
            @foreach ($report->briefing as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ol>
    </section>

    {{-- 2. Live KPIs (click to drill down) --}}
    <div class="pd-kpis">
        @foreach ($report->kpis as $kpi)
            <button type="button" class="pd-kpi pd-kpi-{{ $kpi['tone'] }}" wire:click="setTab('{{ $kpi['tab'] }}')">
                <span class="pd-card-label"><i class="pd-dot"></i>{{ $kpi['label'] }}</span>
                <span class="pd-kpi-value">{{ $kpi['value'] }}
                    @if ($kpi['trend'])<small class="pd-kpi-trend">{{ $kpi['trend'] }}</small>@endif
                </span>
                <span class="pd-card-foot">{{ $kpi['foot'] }}</span>
                @if (count($kpi['spark']) > 1)
                    @php
                        $min = min($kpi['spark']); $max = max($kpi['spark']); $range = max(1, $max - $min);
                        $pts = collect($kpi['spark'])->values()->map(fn ($v, $i) => round($i * 100 / (count($kpi['spark']) - 1), 1) . ',' . round(28 - ($v - $min) / $range * 24, 1))->implode(' ');
                    @endphp
                    <svg class="pd-spark" viewBox="0 0 100 30" preserveAspectRatio="none" aria-hidden="true"><polyline points="{{ $pts }}" /></svg>
                @endif
            </button>
        @endforeach
    </div>

    {{-- 3. The single most important thing right now --}}
    @if ($hero)
        <section class="pd-hero pd-tone-{{ $hero->tone() }}" wire:key="hero-{{ $hero->key }}">
            <div class="pd-hero-head">
                <div class="pd-eyebrow">Top priority right now · {{ $categoryLabels[$hero->category] ?? $hero->category }}</div>
                @if ($hero->change && ($changeLabels[$hero->change] ?? null))
                    <span class="pd-pill pd-change-{{ $hero->change }}">{{ $changeLabels[$hero->change] }}{{ $hero->changeText ? ' · ' . $hero->changeText : '' }}</span>
                @endif
            </div>
            <div class="pd-hero-body">
                <div>
                    <h2 class="pd-hero-title">{{ $hero->title }}</h2>
                    <p class="pd-hero-summary">{{ $hero->summary }}</p>
                </div>
                @if ($hero->metric)
                    <div class="pd-hero-metric">{{ $hero->metric }}</div>
                @endif
            </div>

            @if ($hero->items)
                <div class="pd-items">
                    @foreach ($hero->items as $item)
                        <div class="pd-item">
                            <div>
                                @if (! empty($item['id']) && str_starts_with($item['id'], 'STU'))
                                    <button type="button" class="pd-link" wire:click="showStudentStrategy(@js($item['id']))">{{ $item['label'] }}</button>
                                @else
                                    <strong>{{ $item['label'] }}</strong>
                                @endif
                                @isset($item['meta'])<div class="pd-muted">{{ $item['meta'] }}</div>@endisset
                            </div>
                            @isset($item['badge'])<span class="pd-pill pd-{{ $item['tone'] ?? 'info' }}">{{ $item['badge'] }}</span>@endisset
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="pd-card-actions">
                @if ($hero->actionLabel)
                    <button type="button" class="pd-btn pd-btn-primary" wire:click="openInsight(@js($hero->key))">{{ $hero->actionLabel }} →</button>
                @endif
                <button type="button" class="pd-btn" wire:click="snoozeInsight(@js($hero->key))">Snooze till tomorrow</button>
                <button type="button" class="pd-btn" wire:click="dismissInsight(@js($hero->key))">Done</button>
                <details class="pd-why"><summary>Why is this first?</summary>
                    <ul>@foreach ($hero->why as $reason)<li>{{ $reason }}</li>@endforeach<li>Final priority: {{ $hero->finalScore }}/100</li></ul>
                </details>
            </div>
        </section>
    @else
        <section class="pd-hero pd-tone-ok"><h2 class="pd-hero-title">All clear</h2><p class="pd-hero-summary">No open issues right now. Enjoy a calm day.</p></section>
    @endif

    {{-- 4. Everything else that needs action — ordered by priority, so the layout changes with the data --}}
    @if ($queue)
        <div class="pd-feed-head">
            <h2 class="pd-section-title" style="margin:0">Needs your attention <span class="pd-muted">({{ count($queue) }})</span></h2>
            <span class="pd-muted">Ordered by priority · {{ collect($report->categoryOrder)->map(fn ($c) => $categoryLabels[$c] ?? $c)->implode(' → ') }}</span>
        </div>
        <div class="pd-feed">
            @foreach ($queue as $insight)
                <article class="pd-insight pd-tone-{{ $insight->tone() }}" wire:key="insight-{{ $insight->key }}">
                    <div class="pd-insight-top">
                        <span class="pd-insight-cat">{{ $categoryLabels[$insight->category] ?? $insight->category }}</span>
                        @if ($insight->change && ($changeLabels[$insight->change] ?? null))
                            <span class="pd-pill pd-change-{{ $insight->change }}" title="{{ $insight->changeText }}">{{ $changeLabels[$insight->change] }}</span>
                        @endif
                        @if ($insight->metric)<span class="pd-insight-metric">{{ $insight->metric }}</span>@endif
                    </div>
                    <h3 class="pd-insight-title">{{ $insight->title }}</h3>
                    <p class="pd-muted">{{ $insight->summary }}</p>
                    @if ($insight->changeText)<p class="pd-insight-change">{{ $insight->changeText }}</p>@endif

                    @if ($insight->items)
                        <div class="pd-items pd-items-compact">
                            @foreach (array_slice($insight->items, 0, 3) as $item)
                                <div class="pd-item">
                                    <span>{{ $item['label'] }}</span>
                                    @isset($item['badge'])<span class="pd-pill pd-{{ $item['tone'] ?? 'info' }}">{{ $item['badge'] }}</span>@endisset
                                </div>
                            @endforeach
                            @if (count($insight->items) > 3)<span class="pd-muted">+{{ count($insight->items) - 3 }} more</span>@endif
                        </div>
                    @endif

                    <div class="pd-card-actions">
                        @if ($insight->actionLabel)
                            <button type="button" class="pd-btn pd-btn-primary" wire:click="openInsight(@js($insight->key))">{{ $insight->actionLabel }}</button>
                        @endif
                        <button type="button" class="pd-btn pd-btn-ghost" wire:click="snoozeInsight(@js($insight->key))" title="Hide until 8:00 tomorrow">Snooze</button>
                        <button type="button" class="pd-btn pd-btn-ghost" wire:click="dismissInsight(@js($insight->key))" title="Hide until the numbers change">Done</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- 5. Good news — a principal also needs to know what is going well --}}
    @if ($good)
        <section class="pd-good">
            <strong>Going well</strong>
            @foreach ($good as $insight)
                <span class="pd-pill pd-ok">{{ $insight->title }}</span>
            @endforeach
        </section>
    @endif

    @if ($report->hiddenCount > 0)
        <p class="pd-muted">{{ $report->hiddenCount }} snoozed or completed {{ $report->hiddenCount === 1 ? 'item is' : 'items are' }} hidden.
            <button type="button" class="pd-link" wire:click="restoreInsights">Show again</button></p>
    @endif
</div>
