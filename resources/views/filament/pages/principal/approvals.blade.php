{{-- Approvals: one smart inbox. Each request shows context and a suggested decision. --}}
@php
    $ap = $this->approvalsData;
    $items = $approvalFilter === 'all' ? $ap['items'] : array_values(array_filter($ap['items'], fn ($i) => $i['group'] === $approvalFilter));
    $count = fn ($g) => $ap['counts'][$g] ?? 0;
@endphp

<div class="pd-today">
    <div class="pd-kpis" style="grid-template-columns:repeat(4,minmax(0,1fr))">
        <div class="pd-kpi pd-kpi-{{ count($ap['items']) ? 'risk' : 'ok' }}"><span class="pd-card-label"><i class="pd-dot"></i>Waiting for you</span><span class="pd-kpi-value">{{ count($ap['items']) }}</span><span class="pd-card-foot">{{ $ap['oldest'] ? "Oldest waiting {$ap['oldest']} days" : 'Nothing old' }}</span></div>
        <div class="pd-kpi pd-kpi-info"><span class="pd-card-label"><i class="pd-dot"></i>Result sign-offs</span><span class="pd-kpi-value">{{ $count('results') }}</span><span class="pd-card-foot">Moderated papers</span></div>
        <div class="pd-kpi pd-kpi-warn"><span class="pd-card-label"><i class="pd-dot"></i>Fee concessions</span><span class="pd-kpi-value">{{ $count('fees') }}</span><span class="pd-card-foot">From 60+ day defaulters</span></div>
        <div class="pd-kpi pd-kpi-ok"><span class="pd-card-label"><i class="pd-dot"></i>Decided this week</span><span class="pd-kpi-value">{{ $ap['decided_week'] }}</span><span class="pd-card-foot">By you</span></div>
    </div>

    <div class="pd-chips">
        @foreach (['all' => 'All (' . count($ap['items']) . ')', 'results' => 'Results (' . $count('results') . ')', 'fees' => 'Fees (' . $count('fees') . ')', 'leave' => 'Leave (' . $count('leave') . ')'] as $key => $label)
            <button type="button" class="pd-chip {{ $approvalFilter === $key ? 'active' : '' }}" wire:click="setApprovalFilter('{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    <div style="display:grid;gap:10px">
        @forelse ($items as $item)
            <div class="pd-appr" wire:key="appr-{{ $item['id'] }}">
                <div>
                    <span class="pd-pill pd-{{ $item['tone'] }}">{{ $item['type'] }}</span>
                    <div class="pd-table-sub" style="margin-top:6px">{{ $item['waiting'] === 0 ? 'Since today' : 'Waiting ' . $item['waiting'] . ' ' . ($item['waiting'] === 1 ? 'day' : 'days') }}</div>
                </div>
                <div>
                    <button type="button" class="pd-link" wire:click="setTab('{{ $item['tab'] }}')">{{ $item['title'] }}</button>
                    <div class="pd-muted" style="font-size:12.5px;margin-top:3px">{{ $item['context'] }}</div>
                </div>
                <div class="pd-ai pd-ai-{{ $item['ai_tone'] }}">✦ <span>{{ $item['ai'] }}</span></div>
                <div class="pd-appr-actions">
                    <button type="button" class="pd-btn pd-btn-primary" wire:click="decideApproval(@js($item['id']), 'approved')">Approve</button>
                    <button type="button" class="pd-btn" wire:click="decideApproval(@js($item['id']), 'declined')">Decline</button>
                    <button type="button" class="pd-btn pd-btn-ghost" wire:click="decideApproval(@js($item['id']), 'info')">Ask info</button>
                </div>
            </div>
        @empty
            <section class="pd-section" style="text-align:center"><strong>Inbox clear 🎉</strong><p class="pd-muted">No requests are waiting for you in this group.</p></section>
        @endforelse
    </div>
</div>
