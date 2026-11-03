{{-- Fees: calculated from the fee ledger against today's date. --}}
@php
    use App\Services\Principal\Insights\Format;
    $f = $this->feeSummary;
    $list = match ($feeFilter) {
        'new' => $f['new_this_week'],
        '60' => array_values(array_filter($f['defaulters'], fn ($d) => $d['days'] > 60)),
        default => $f['defaulters'],
    };
    $bucketTotal = max(1, array_sum($f['buckets']));
@endphp

<section class="pd-section">
    <div class="pd-grid" style="margin-bottom:14px">
        <div class="pd-card"><div class="pd-card-label">Due so far this year</div><div class="pd-card-value">{{ Format::inr($f['expected']) }}</div><div class="pd-card-foot">All instalments past their due date</div></div>
        <div class="pd-card"><div class="pd-card-label">Collected</div><div class="pd-card-value">{{ Format::inr($f['collected']) }}</div><div class="pd-card-foot">Collection rate {{ $f['rate'] }}%</div><div class="pd-progress"><span style="width:{{ $f['rate'] }}%"></span></div></div>
        <div class="pd-card"><div class="pd-card-label">Overdue</div><div class="pd-card-value">{{ Format::inr($f['overdue']) }}</div><div class="pd-card-foot">{{ count($f['defaulters']) }} families past the grace period</div></div>
        <div class="pd-card"><div class="pd-card-label">Falls due in 7 days</div><div class="pd-card-value">{{ Format::inr($f['due_next_7']) }}</div><div class="pd-card-foot">Send an advance reminder</div></div>
    </div>

    <div class="pd-two" style="margin-bottom:14px">
        <section class="pd-section">
            <h3 class="pd-section-title">How old is the overdue money?</h3>
            @foreach ($f['buckets'] as $label => $amount)
                <div class="pd-bar-row" style="grid-template-columns:90px minmax(100px,1fr) 70px">
                    <span>{{ $label }}</span>
                    <div class="pd-progress"><span style="width:{{ round($amount / $bucketTotal * 100) }}%;background:{{ $label === '60+ days' ? 'var(--pd-bad)' : ($label === '31–60 days' ? 'var(--pd-warn)' : 'var(--pd-pri)') }}"></span></div>
                    <strong>{{ Format::inr($amount) }}</strong>
                </div>
            @endforeach
            <p class="pd-muted">Money older than 60 days is the hardest to recover. Work the red bar first.</p>
        </section>
        <section class="pd-section">
            <h3 class="pd-section-title">Suggested approach</h3>
            <div class="pd-mini-list">
                <div class="pd-mini-row"><span>{{ Format::plural(count($f['new_this_week']), 'new defaulter') }} this week</span><span class="pd-pill pd-info">Gentle SMS</span></div>
                <div class="pd-mini-row"><span>{{ count(array_filter($f['defaulters'], fn ($d) => $d['days'] > 30 && $d['days'] <= 60)) }} at 31–60 days</span><span class="pd-pill pd-warn">Phone call</span></div>
                <div class="pd-mini-row"><span>{{ count(array_filter($f['defaulters'], fn ($d) => $d['days'] > 60)) }} at 60+ days</span><span class="pd-pill pd-risk">Parent meeting</span></div>
            </div>
        </section>
    </div>

    <div class="pd-exam-toolbar">
        <div><h3 class="pd-section-title" style="margin-bottom:3px">Fee defaulters</h3><p class="pd-muted">Sorted by amount × days overdue — call from the top.</p></div>
        <div class="pd-chips">
            @foreach (['all' => 'All (' . count($f['defaulters']) . ')', 'new' => 'New this week (' . count($f['new_this_week']) . ')', '60' => '60+ days'] as $key => $label)
                <button type="button" class="pd-chip {{ $feeFilter === $key ? 'active' : '' }}" wire:click="setFeeFilter('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="pd-table-wrap"><table class="pd-table">
        <thead><tr><th>Student</th><th>Pending heads</th><th>Amount</th><th>Overdue</th><th>Parent</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($list as $d)
            <tr wire:key="fee-{{ $d['id'] }}">
                <td><div class="pd-table-name">{{ $d['name'] }}</div><div class="pd-table-sub">{{ $d['id'] }} · {{ $d['class'] }}</div></td>
                <td>{{ $d['heads'] }}</td>
                <td><strong>{{ Format::inr($d['amount']) }}</strong></td>
                <td><span class="pd-pill {{ $d['days'] > 60 ? 'pd-risk' : ($d['days'] > 30 ? 'pd-warn' : 'pd-info') }}">{{ $d['days'] }} days</span></td>
                <td style="white-space:nowrap">{{ $d['phone'] }}</td>
                <td style="white-space:nowrap"><button type="button" class="pd-btn" wire:click="sendFeeReminder(@js($d['id']))">Send reminder</button></td>
            </tr>
        @empty
            <tr><td colspan="6" class="pd-muted" style="padding:22px;text-align:center">No families in this group. 🎉</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
