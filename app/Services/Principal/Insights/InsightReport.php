<?php

namespace App\Services\Principal\Insights;

use Carbon\CarbonImmutable;

/**
 * Everything the overview screen needs for ONE launch: ranked insights, the morning
 * briefing, "since your last visit" and live KPIs.
 */
final class InsightReport
{
    /**
     * @param  list<Insight>  $insights  visible, highest priority first
     * @param  list<Insight>  $all  including dismissed/snoozed (used for the snapshot)
     * @param  list<string>  $briefing
     * @param  list<array{key:string,label:string,value:string,foot:string,tone:string,trend:?string,spark:list<float>,tab:string}>  $kpis
     * @param  list<string>  $categoryOrder
     */
    public function __construct(
        public readonly array $insights,
        public readonly array $all,
        public readonly int $hiddenCount,
        public readonly string $greeting,
        public readonly string $focus,
        public readonly array $briefing,
        public readonly string $sinceLastVisit,
        public readonly array $kpis,
        public readonly array $categoryOrder,
        public readonly CarbonImmutable $generatedAt,
    ) {
    }

    /** The single most important thing right now. */
    public function hero(): ?Insight
    {
        foreach ($this->insights as $insight) {
            if ($insight->severity !== 'positive') {
                return $insight;
            }
        }

        return null;
    }

    /** @return list<Insight> everything that needs action, except the hero */
    public function actionQueue(): array
    {
        $hero = $this->hero();

        return array_values(array_filter(
            $this->insights,
            fn (Insight $i) => $i->severity !== 'positive' && $i !== $hero,
        ));
    }

    /** @return list<Insight> */
    public function goodNews(): array
    {
        return array_values(array_filter($this->insights, fn (Insight $i) => $i->severity === 'positive'));
    }

    public function find(string $key): ?Insight
    {
        foreach ($this->all as $insight) {
            if ($insight->key === $key) {
                return $insight;
            }
        }

        return null;
    }

    /** Plain-text summary handed to Copilot so its answers are grounded in today's analysis. */
    public function asContext(int $limit = 8): string
    {
        $lines = ["Date: {$this->generatedAt->format('l, d M Y H:i')}"];
        foreach ($this->kpis as $kpi) {
            $lines[] = "{$kpi['label']}: {$kpi['value']} ({$kpi['foot']})";
        }
        foreach (array_slice($this->insights, 0, $limit) as $i) {
            $lines[] = "[{$i->severity}] {$i->title}. {$i->summary}";
            foreach (array_slice($i->items, 0, 4) as $item) {
                $lines[] = "  - {$item['label']}" . (isset($item['meta']) ? " ({$item['meta']})" : '') . (isset($item['badge']) ? " [{$item['badge']}]" : '');
            }
        }

        return implode("\n", $lines);
    }
}
