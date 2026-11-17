<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\ValueObjects;

/**
 * Aggregated answers of one form field across a set of submissions.
 *
 * @phpstan-type NpsBreakdown array{promoters: int, passives: int, detractors: int, score: int}
 */
final readonly class FieldStatistics
{
    /**
     * @param  array<string, int>  $distribution  answer label ⇒ count; choice fields keep their option order
     * @param  NpsBreakdown|null  $nps
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public int $answered,
        public int $total,
        public array $distribution,
        public ?float $average,
        public int|float|null $min,
        public int|float|null $max,
        public ?array $nps,
        public bool $isChartable,
    ) {}
}
