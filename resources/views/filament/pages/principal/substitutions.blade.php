{{-- Shown at the top of the Timetable tab only when teachers are on leave today. --}}
@php $sub = $this->substitution; @endphp

@if ($sub['gaps'] !== [])
    <section class="pd-hero pd-tone-risk" style="margin-bottom:14px">
        <div class="pd-eyebrow">Cover needed today</div>
        <h2 class="pd-hero-title">{{ implode(', ', array_column($sub['on_leave'], 'name')) }} on leave · {{ count($sub['gaps']) }} {{ count($sub['gaps']) === 1 ? 'period' : 'periods' }} uncovered</h2>
        <p class="pd-hero-summary">A free teacher is suggested for each period — same subject first, then whoever has covered the fewest periods today.</p>
        <div class="pd-items">
            @foreach ($sub['gaps'] as $gap)
                <div class="pd-item" wire:key="gap-{{ $gap['class'] }}-{{ $gap['start'] }}">
                    <div>
                        <strong>{{ $gap['time'] }} · {{ $gap['class'] }} {{ $gap['subject'] }}</strong>
                        <div class="pd-muted">{{ $gap['absent_teacher'] }} · {{ $gap['room'] }}
                            @if ($gap['status'] === 'In progress')<span class="pd-pill pd-risk">Happening now</span>@endif
                        </div>
                    </div>
                    @if ($gap['suggested'])
                        <button type="button" class="pd-btn" wire:click="assignSubstitute(@js($gap['class']), @js($gap['start']))">
                            Assign {{ $gap['suggested'] }}{{ $gap['same_subject'] ? ' ✓ same subject' : '' }}
                        </button>
                    @else
                        <span class="pd-pill pd-risk">No one free</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
