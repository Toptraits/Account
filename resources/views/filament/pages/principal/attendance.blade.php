{{-- Attendance: last 30 school days, calculated per student and per section. --}}
@php
    $a = $this->attendanceSummary;
    $threshold = config('principal-insights.attendance.defaulter_below', 75);
    $daily = array_slice($a['daily'], -14, null, true);
    $minDay = min($daily ?: [0]);
@endphp

<section class="pd-section">
    @unless ($a['register_taken'])
        <div class="pd-copilot-answer" style="margin-bottom:12px">Today's register opens at 09:30. Showing the last school day ({{ \Illuminate\Support\Carbon::parse($a['latest_date'])->format('D d M') }}).</div>
    @endunless

    <div class="pd-grid" style="margin-bottom:14px">
        <div class="pd-card"><div class="pd-card-label">Present</div><div class="pd-card-value">{{ $a['present_today'] }}</div><div class="pd-card-foot">of {{ $a['total'] }} students</div></div>
        <div class="pd-card"><div class="pd-card-label">Absent</div><div class="pd-card-value">{{ $a['absent_today'] }}</div><div class="pd-card-foot">{{ count($a['streaks']) }} absent 3+ days running</div></div>
        <div class="pd-card"><div class="pd-card-label">Late arrivals</div><div class="pd-card-value">{{ $a['late_today'] }}</div><div class="pd-card-foot">Counted as present</div></div>
        <div class="pd-card"><div class="pd-card-label">Attendance rate</div><div class="pd-card-value">{{ $a['latest_rate'] }}%</div><div class="pd-card-foot">Best in 30 days: {{ $a['best_rate'] }}%</div><div class="pd-progress"><span style="width:{{ $a['latest_rate'] }}%"></span></div></div>
    </div>

    <section class="pd-section" style="margin-bottom:14px">
        <h3 class="pd-section-title">Last 14 school days</h3>
        <div class="pd-columns">
            @foreach ($daily as $date => $rate)
                <div class="pd-column" title="{{ \Illuminate\Support\Carbon::parse($date)->format('D d M') }}: {{ $rate }}%">
                    <span style="height:{{ max(6, ($rate - max(0, $minDay - 5)) / max(1, 100 - max(0, $minDay - 5)) * 100) }}%" class="{{ $rate < 90 ? 'low' : '' }}"></span>
                    <small>{{ \Illuminate\Support\Carbon::parse($date)->format('d') }}</small>
                </div>
            @endforeach
        </div>
    </section>

    <div class="pd-exam-toolbar">
        <div><h3 class="pd-section-title" style="margin-bottom:3px">Who needs follow-up?</h3><p class="pd-muted">Status compares today with each section's usual attendance (last 29 school days): <span style="color:var(--pd-bad)">red</span> = 6+ points below usual · <span style="color:var(--pd-warn)">Watch</span> = below 90% · <span style="color:var(--pd-ok)">Normal</span> = fine. Defaulters are students below {{ $threshold }}% over 30 school days.</p></div>
        <div class="pd-chips">
            @foreach (['sections' => 'By section', 'defaulters' => 'Defaulters (' . count($a['defaulters']) . ')', 'streaks' => 'Absent 3+ days (' . count($a['streaks']) . ')'] as $key => $label)
                <button type="button" class="pd-chip {{ $attendanceFilter === $key ? 'active' : '' }}" wire:click="setAttendanceFilter('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if ($attendanceFilter === 'sections')
        <div class="pd-table-wrap"><table class="pd-table">
            <thead><tr><th>Class</th><th>Strength</th><th>Present</th><th>Absent</th><th>Today</th><th>Usual</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($a['sections'] as $row)
                <tr><td><strong>{{ $row['class'] }}</strong></td><td>{{ $row['strength'] }}</td><td>{{ $row['present'] }}</td><td>{{ $row['absent'] }}</td>
                    <td><strong>{{ $row['rate'] }}%</strong></td><td class="pd-muted">{{ $row['usual'] }}%</td>
                    <td><span class="pd-pill {{ $row['drop'] >= 6 ? 'pd-risk' : ($row['rate'] < 90 ? 'pd-warn' : 'pd-ok') }}">{{ $row['drop'] >= 6 ? '−' . $row['drop'] . ' pts vs usual' : ($row['rate'] < 90 ? 'Watch' : 'Normal') }}</span></td></tr>
            @endforeach
            </tbody>
        </table></div>
    @else
        @php $list = $attendanceFilter === 'streaks' ? $a['streaks'] : $a['defaulters']; @endphp
        <div class="pd-table-wrap"><table class="pd-table">
            <thead><tr><th>Student</th><th>30-day attendance</th><th>Absences</th><th>Current streak</th><th>Parent</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($list as $s)
                <tr wire:key="att-{{ $s['id'] }}">
                    <td><button type="button" class="pd-link" wire:click="showStudentStrategy(@js($s['id']))">{{ $s['name'] }}</button><div class="pd-table-sub">{{ $s['id'] }} · {{ $s['class'] }}</div></td>
                    <td><span class="pd-pill {{ $s['pct'] < $threshold ? 'pd-risk' : 'pd-warn' }}">{{ $s['pct'] }}%</span></td>
                    <td>{{ $s['absences'] }} · {{ $s['lates'] }} late</td>
                    <td>{{ $s['streak'] > 0 ? $s['streak'] . ' days' : '—' }}</td>
                    <td style="white-space:nowrap">{{ $s['phone'] }}</td>
                    <td><button type="button" class="pd-btn" wire:click="markFamilyCalled(@js($s['id']))">Log call</button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="pd-muted" style="padding:22px;text-align:center">Nobody in this list today. 🎉</td></tr>
            @endforelse
            </tbody>
        </table></div>
    @endif
</section>
