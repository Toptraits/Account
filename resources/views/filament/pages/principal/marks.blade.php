{{-- Marks: pick a class & section → subject-wise % and the students with low marks. --}}
@php
    $m = $this->marksReport;
    $scope = $this->marksScope();
    $scopeLabel = $scope === 'all' ? 'Whole school' : (str_contains($scope, '-') ? "Class {$scope}" : "Class {$scope} (all sections)");
    $cell = fn ($v) => $v >= 75 ? 'pd-heat-hi' : ($v >= 60 ? 'pd-heat-mid' : ($v >= 50 ? 'pd-heat-low' : 'pd-heat-bad'));
    $short = ['Mathematics' => 'Maths', 'Science' => 'Sci', 'English' => 'Eng', 'Social Studies' => 'SST', 'Computer Science' => 'CS'];
    $tone = fn ($v) => $v < $marksThreshold ? 'pd-risk' : ($v < 60 ? 'pd-warn' : 'pd-ok');
@endphp

<style>
.pd-heat{width:100%;border-collapse:separate;border-spacing:4px;min-width:560px}
.pd-heat th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--pd-mut);text-align:center;padding:6px}
.pd-heat th:first-child{text-align:left}
.pd-heat td{text-align:center;padding:0}
.pd-heat button{width:100%;border:1px solid transparent;border-radius:8px;padding:9px 4px;font-weight:800;font-size:13px;cursor:pointer;color:var(--pd-ink)}
.pd-heat button:hover{border-color:var(--pd-pri)}
.pd-heat tr.selected button{outline:2px solid var(--pd-pri)}
.pd-heat-hi{background:#12301f}.pd-heat-mid{background:#1c2a4d}.pd-heat-low{background:#35280f}.pd-heat-bad{background:#3a1a18}
.pd-heat .pd-heat-name{background:transparent;text-align:left;color:var(--pd-mut)}
.pd-marks-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:end}
.pd-marks-filters label{display:grid;gap:5px;min-width:120px}
.pd-subject-marks{display:flex;gap:6px;flex-wrap:wrap}
</style>

<section class="pd-section">
    {{-- Filters --}}
    <div class="pd-exam-toolbar" style="margin-top:0">
        <div>
            <h2 class="pd-section-title" style="margin-bottom:3px">Marks · {{ $scopeLabel }}</h2>
            <p class="pd-muted">Latest assessment marks. Choose a class and section, or click any cell in the heatmap below.</p>
        </div>
        <div class="pd-marks-filters">
            <label><span class="pd-muted">Class</span>
                <select class="pd-input" wire:model.live="marksClass">
                    <option value="all">All classes</option>
                    @foreach (['6', '7', '8', '9'] as $c)<option value="{{ $c }}">Class {{ $c }}</option>@endforeach
                </select>
            </label>
            <label><span class="pd-muted">Section</span>
                <select class="pd-input" wire:model.live="marksSection" @disabled($marksClass === 'all')>
                    <option value="all">All sections</option>
                    @foreach (['A', 'B'] as $sec)<option value="{{ $sec }}">Section {{ $sec }}</option>@endforeach
                </select>
            </label>
            <label><span class="pd-muted">Low marks below</span>
                <select class="pd-input" wire:model.live="marksThreshold">
                    @foreach (collect([33, 40, 50, 60, (int) $marksThreshold])->unique()->sort() as $t)<option value="{{ $t }}">{{ $t }}%</option>@endforeach
                </select>
            </label>
        </div>
    </div>

    {{-- KPIs for the selected scope --}}
    <div class="pd-grid" style="margin-bottom:14px">
        <div class="pd-card"><div class="pd-card-label">Average marks</div><div class="pd-card-value">{{ $m['scope_average'] }}%</div>
            <div class="pd-card-foot">School average {{ $m['school_average'] }}%
                @if ($scope !== 'all')
                    · <strong style="color:{{ $m['scope_average'] >= $m['school_average'] ? 'var(--pd-ok)' : 'var(--pd-bad)' }}">{{ $m['scope_average'] >= $m['school_average'] ? '▲' : '▼' }} {{ abs(round($m['scope_average'] - $m['school_average'], 1)) }}</strong>
                @endif
            </div>
            <div class="pd-progress"><span style="width:{{ $m['scope_average'] }}%"></span></div></div>
        <div class="pd-card"><div class="pd-card-label">Students</div><div class="pd-card-value">{{ $m['student_count'] }}</div><div class="pd-card-foot">{{ $m['rank'] ? "Rank {$m['rank']} of {$m['section_count']} sections" : 'In this selection' }}</div></div>
        <div class="pd-card"><div class="pd-card-label">Low marks (below {{ $marksThreshold }}%)</div><div class="pd-card-value" style="color:{{ count($m['low_students']) ? 'var(--pd-bad)' : 'var(--pd-ok)' }}">{{ count($m['low_students']) }}</div><div class="pd-card-foot">Students listed below</div></div>
        <div class="pd-card"><div class="pd-card-label">Top performer</div><div class="pd-card-value" style="font-size:18px">{{ $m['top_students'][0]['name'] ?? '—' }}</div><div class="pd-card-foot">{{ isset($m['top_students'][0]) ? $m['top_students'][0]['class'] . ' · ' . $m['top_students'][0]['average'] . '%' : '' }}</div></div>
    </div>

    {{-- Subject-wise for the selected scope --}}
    <h3 class="pd-section-title">Subject-wise · {{ $scopeLabel }}</h3>
    <div class="pd-table-wrap" style="margin-bottom:16px"><table class="pd-table">
        <thead><tr><th>Subject</th><th>Average</th><th>vs school</th><th>Pass rate</th><th>Highest</th><th>Lowest</th><th>Below {{ $marksThreshold }}%</th></tr></thead>
        <tbody>
        @foreach ($m['subject_rows'] as $row)
            <tr>
                <td><strong>{{ $row['subject'] }}</strong></td>
                <td><strong>{{ $row['average'] }}%</strong><div class="pd-progress" style="max-width:120px"><span style="width:{{ $row['average'] }}%"></span></div></td>
                <td><span class="pd-pill {{ $row['vs_school'] >= 0 ? 'pd-ok' : 'pd-risk' }}">{{ $row['vs_school'] >= 0 ? '▲' : '▼' }} {{ abs($row['vs_school']) }}</span></td>
                <td>{{ $row['pass_rate'] }}%</td>
                <td>{{ $row['highest'] }}% <div class="pd-table-sub">{{ $row['highest_name'] }}</div></td>
                <td>{{ $row['lowest'] }}% <div class="pd-table-sub">{{ $row['lowest_name'] }}</div></td>
                <td><span class="pd-pill {{ $row['below'] > 0 ? 'pd-warn' : 'pd-ok' }}">{{ $row['below'] }} {{ $row['below'] === 1 ? 'student' : 'students' }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>

    {{-- Students with low marks --}}
    <h3 class="pd-section-title">Students with low marks · {{ $scopeLabel }}</h3>
    <div class="pd-table-wrap" style="margin-bottom:16px"><table class="pd-table">
        <thead><tr><th>Student</th><th>Class</th><th>Average</th><th>Subject marks</th><th>Weak in</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($m['low_students'] as $s)
            <tr wire:key="low-{{ $s['id'] }}">
                <td><div class="pd-table-name">{{ $s['name'] }}</div><div class="pd-table-sub">{{ $s['id'] }}</div></td>
                <td>{{ $s['class'] }}</td>
                <td><span class="pd-pill {{ $tone($s['average']) }}">{{ $s['average'] }}%</span></td>
                <td><div class="pd-subject-marks">
                    @foreach ($s['marks'] as $subject => $mark)
                        <span class="pd-pill {{ $tone($mark) }}" title="{{ $subject }}">{{ $short[$subject] ?? $subject }} {{ $mark }}</span>
                    @endforeach
                </div></td>
                <td>{{ implode(', ', array_keys($s['weak_subjects'])) ?: '—' }}</td>
                <td><button type="button" class="pd-btn" wire:click="showStudentStrategy(@js($s['id']))">View strategy</button></td>
            </tr>
        @empty
            <tr><td colspan="6" class="pd-muted" style="padding:22px;text-align:center">No student in {{ $scopeLabel }} is below {{ $marksThreshold }}% in any subject. 🎉</td></tr>
        @endforelse
        </tbody>
    </table></div>

    {{-- Heatmap: every section × subject. Click to drill in. --}}
    <h3 class="pd-section-title">All sections at a glance</h3>
    <p class="pd-muted" style="margin:-6px 0 8px">Green ≥75% · Blue 60–74% · Amber 50–59% · Red below 50%. Click a row to open that section.</p>
    <div class="pd-table-wrap" style="padding:6px"><table class="pd-heat">
        <thead><tr><th>Section</th>@foreach ($m['subjects'] as $subject)<th>{{ $subject }}</th>@endforeach<th>Overall</th></tr></thead>
        <tbody>
        @foreach ($m['heatmap'] as $section => $row)
            <tr class="{{ $scope === $section ? 'selected' : '' }}">
                <td><button type="button" class="pd-heat-name" wire:click="focusMarks(@js($section))"><strong>{{ $section }}</strong></button></td>
                @foreach ($m['subjects'] as $subject)
                    <td><button type="button" class="{{ $cell($row[$subject]) }}" wire:click="focusMarks(@js($section))" title="{{ $section }} {{ $subject }}">{{ $row[$subject] }}%</button></td>
                @endforeach
                <td><button type="button" class="{{ $cell($row['Overall']) }}" wire:click="focusMarks(@js($section))">{{ $row['Overall'] }}%</button></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
