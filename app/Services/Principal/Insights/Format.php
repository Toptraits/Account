<?php

namespace App\Services\Principal\Insights;

final class Format
{
    /** ₹1,25,000 → "₹1.25L", ₹45,600 → "₹45.6K" */
    public static function inr(int|float $amount): string
    {
        return match (true) {
            $amount >= 10000000 => '₹' . rtrim(rtrim(number_format($amount / 10000000, 2), '0'), '.') . 'Cr',
            $amount >= 100000 => '₹' . rtrim(rtrim(number_format($amount / 100000, 2), '0'), '.') . 'L',
            $amount >= 1000 => '₹' . rtrim(rtrim(number_format($amount / 1000, 1), '0'), '.') . 'K',
            default => '₹' . number_format($amount),
        };
    }

    public static function plural(int $count, string $singular, ?string $plural = null): string
    {
        return $count . ' ' . ($count === 1 ? $singular : ($plural ?? $singular . 's'));
    }

    /** verb(1, 'is', 'are') → 'is' */
    public static function verb(int $count, string $singular, string $plural): string
    {
        return $count === 1 ? $singular : $plural;
    }

    /** ["a", "b", "c"] → "a, b and c" */
    public static function list(array $items): string
    {
        $items = array_values($items);

        return match (count($items)) {
            0 => '',
            1 => $items[0],
            default => implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items),
        };
    }
}
