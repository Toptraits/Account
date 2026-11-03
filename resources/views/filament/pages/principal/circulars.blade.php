{{-- Alerts & Circulars: Copilot drafts a notice for each alert. Nothing is sent without "Review & send". --}}
@php $c = $this->circularsData; @endphp

<div class="pd-two" style="grid-template-columns:minmax(0,1.5fr) minmax(300px,1fr)">
    <section class="pd-section">
        <h3 class="pd-section-title">Suggested circulars ({{ count($c['drafts']) }})</h3>
        <div style="display:grid;gap:10px">
            @forelse ($c['drafts'] as $d)
                <div class="pd-draft" wire:key="draft-{{ $d['id'] }}">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center">
                        <div class="pd-table-name">{{ $d['title'] }}</div>
                        <span class="pd-pill pd-{{ $d['tone'] }}">{{ $d['audience'] }} {{ $d['audience'] === 1 ? 'parent' : 'parents' }}</span>
                    </div>
                    <div class="pd-muted" style="font-size:12.5px">Why: {{ $d['why'] }}</div>
                    <div class="pd-msg">{{ $d['message'] }}</div>
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                        @foreach (['SMS', 'WhatsApp', 'App', 'Email'] as $ch)
                            <span class="pd-chan {{ in_array($ch, $d['channels'], true) ? 'on' : '' }}">{{ $ch }}</span>
                        @endforeach
                        <span style="margin-left:auto;display:flex;gap:6px">
                            <button type="button" class="pd-btn pd-btn-primary" wire:click="sendCircular(@js($d['id']))" wire:confirm="Send “{{ $d['title'] }}” to {{ $d['audience'] }} parents?">Review & send</button>
                            <button type="button" class="pd-btn pd-btn-ghost" wire:click="dismissCircular(@js($d['id']))">Dismiss</button>
                        </span>
                    </div>
                </div>
            @empty
                <p class="pd-muted">No notices needed right now. New drafts appear here when an alert comes up.</p>
            @endforelse
        </div>
    </section>

    <section class="pd-section">
        <h3 class="pd-section-title">Live alerts</h3>
        <div class="pd-mini-list">
            @forelse ($c['alerts'] as $i)
                <button type="button" class="pd-mini-row" style="width:100%;text-align:left;cursor:pointer;color:var(--pd-ink)" wire:click="openInsight(@js($i->key))">
                    <span style="font-size:13px">{{ $i->title }}</span>
                    <span class="pd-pill pd-{{ $i->tone() }}">{{ ucfirst($i->severity) }}</span>
                </button>
            @empty
                <p class="pd-muted">No alerts right now.</p>
            @endforelse
        </div>

        <h3 class="pd-section-title" style="margin-top:16px">Sent this week</h3>
        <div class="pd-mini-list">
            @forelse ($c['sent'] as $s)
                <div class="pd-mini-row"><span style="font-size:13px">{{ $s['title'] ?? 'Circular' }}</span><span class="pd-muted">{{ \Illuminate\Support\Carbon::createFromTimestamp($s['at'])->format('D H:i') }} · {{ $s['audience'] ?? 0 }} parents</span></div>
            @empty
                <p class="pd-muted">Nothing sent yet this week.</p>
            @endforelse
        </div>
    </section>
</div>
