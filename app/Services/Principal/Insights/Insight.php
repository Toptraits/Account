<?php

namespace App\Services\Principal\Insights;

/**
 * One thing the dashboard thinks the principal should know or do.
 *
 * Analyzers create these from data; the InsightEngine ranks them, compares them with the
 * principal's last visit and decides which ones appear (and in which order) on screen.
 */
final class Insight
{
    /** Filled in by the engine. */
    public int $finalScore = 0;

    public ?string $change = null;      // new | worse | better | same

    public ?string $changeText = null;  // "↑ 3 since your last visit"

    /** @var list<string> Why this card is ranked where it is (shown as a tooltip). */
    public array $why = [];

    /**
     * @param  string  $key  Stable id, e.g. "fees.defaulters". Used for dismiss / snooze / diff.
     * @param  string  $category  fees | attendance | academics | exams | staff | students
     * @param  string  $severity  critical | warning | info | positive
     * @param  int  $score  0–100 base priority from the analyzer
     * @param  float|null  $value  Main number, used to detect "got better / worse"
     * @param  string  $betterWhen  lower | higher
     * @param  list<array{label:string,meta?:string,badge?:string,tone?:string,id?:string}>  $items
     */
    public function __construct(
        public readonly string $key,
        public readonly string $category,
        public readonly string $severity,
        public readonly int $score,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $briefing = '',
        public readonly ?string $metric = null,
        public readonly ?float $value = null,
        public readonly string $betterWhen = 'lower',
        public readonly array $items = [],
        public readonly ?string $actionLabel = null,
        public readonly ?string $actionTab = null,
        public readonly array $actionParams = [],
    ) {
    }

    /** Changes whenever the underlying situation changes, so a dismissed card comes back if things change. */
    public function fingerprint(): string
    {
        return md5($this->key . '|' . $this->value . '|' . implode(',', array_column($this->items, 'label')));
    }

    public function tone(): string
    {
        return match ($this->severity) {
            'critical' => 'risk',
            'warning' => 'warn',
            'positive' => 'ok',
            default => 'info',
        };
    }
}
