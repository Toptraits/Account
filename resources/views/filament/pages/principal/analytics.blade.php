{{-- School Analytics: the same live numbers as Home, plus trends and a section scorecard. --}}
@php
    $a = $this->analyticsData;
    $vals = array_values($a['daily']);
    $min = $vals ? min($vals) - 3 : 0;
    $range = max(1, 100 - $min);
    $y = fn ($v) => round(170 - ($v - $min) / $range * 150, 1);
    $points = collect($vals)->map(fn ($v, $i) => round($i * 1000 / max(1, count($vals) - 1), 1) . ',' . $y($v))->implode(' ');
@endphp

<div class="pd-today">
    <div class="pd-kpis" style="grid-template-columns:repeat(5,minmax(0,1fr))">
        @foreach ($a['kpis'] as $k)
            <button type="button" class="pd-kpi pd-kpi-{{ $k['tone'] }}" wire:click="setTab('{{ $k['tab'] }}')">
                <span class="pd-card-label"><i class="pd-dot"></i>{{ $k['label'] }}</span>
                <span class="pd-kpi-value">{{ $k['value'] }}</span>
                <span class="pd-card-foot">{{ $k['foot'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="pd-two">
        <section class="pd-section">
            <h3 class="pd-section-title">Attendance · last {{ count($vals) }} school days</h3>
            @if (count($vals) > 1)
                <svg viewBox="0 0 1000 180" preserveAspectRatio="none" style="width:100%;height:190px" aria-label="Attendance trend">
                    <line x1="0" x2="1000" y1="{{ $y(90) }}" y2="{{ $y(90) }}" stroke="#f2b25466" stroke-dasharray="6 6" />
                    <text x="994" y="{{ $y(90) - 6 }}" fill="#f2b254" font-size="14" text-anchor="end">90% target</text>
                    <polyline points="{{ $points }}" fill="none" stroke="#7aa2ff" stroke-width="3" vector-effect="non-scaling-stroke" />
                </svg>
                <p class="pd-muted">Lowest day {{ min($vals) }}% · best {{ max($vals) }}% · average {{ $a['avg30'] }}% · today {{ end($vals) }}%</p>
            @endif
        </section>

        <section class="pd-section">
            <h3 class="pd-section-title">What needs attention now</h3>
            <div class="pd-mini-list">
                @foreach ($a['changes'] as $c)
                    <button type="button" class="pd-mini-row" style="width:100%;text-align:left;cursor:pointer;color:var(--pd-ink)" wire:click="setTab('{{ $c['tab'] }}')">
                        <span>{{ $c['text'] }}</span><span class="pd-pill pd-{{ $c['tone'] }}">{{ $c['badge'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>
    </div>

    <section class="pd-section">
        <h3 class="pd-section-title">Section health scorecard <span class="pd-muted" style="font-weight:500">— weakest first · click a section to open its marks</span></h3>
        <div class="pd-table-wrap"><table class="pd-table">
            <thead><tr><th>Section</th><th>Attendance (30 days)</th><th>Marks average</th><th>Fee defaulters</th><th>At-risk students</th><th>Health</th></tr></thead>
            <tbody>
            @foreach ($a['scorecard'] as $s)
                <tr wire:key="score-{{ $s['section'] }}">
                    <td><button type="button" class="pd-link" wire:click="focusMarks(@js($s['section']))">{{ $s['section'] }}</button></td>
                    <td>{{ $s['attendance'] }}%<div class="pd-progress" style="max-width:120px"><span style="width:{{ $s['attendance'] }}%"></span></div></td>
                    <td>{{ $s['marks'] }}%<div class="pd-progress" style="max-width:120px"><span style="width:{{ $s['marks'] }}%"></span></div></td>
                    <td>{{ $s['fee_count'] }} · {{ \App\Services\Principal\Insights\Format::inr($s['fee_amount']) }}</td>
                    <td>{{ $s['risk'] }}</td>
                    <td><span class="pd-pill {{ $s['health'] < 62 ? 'pd-risk' : ($s['health'] < 70 ? 'pd-warn' : 'pd-ok') }}">{{ $s['health'] }}/100</span></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <p class="pd-muted" style="margin-top:8px">Health = attendance 35% + marks 45% + fee status 20%, minus 2 points per at-risk student.</p>
    </section>
</div>
