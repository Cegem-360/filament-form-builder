<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Madbox99\FilamentFormBuilder\ValueObjects\FieldStatistics;

/**
 * Chart.js configurations (type, data, options) for the form results page,
 * built from field statistics. Kept free of Livewire so the choices are testable.
 *
 * @phpstan-type ChartConfig array{type: string, data: array{labels: list<string>, datasets: list<array<string, mixed>>}, options: array<string, mixed>}
 */
final class ResultCharts
{
    public const string PROMOTER = '#16a34a';

    public const string PASSIVE = '#f59e0b';

    public const string DETRACTOR = '#dc2626';

    /** Distinct, colour-blind friendlier categorical palette. */
    public const array PALETTE = [
        '#6366f1', '#06b6d4', '#f59e0b', '#ec4899', '#10b981',
        '#8b5cf6', '#f97316', '#14b8a6', '#ef4444', '#84cc16',
    ];

    private const int DOUGHNUT_MAX_OPTIONS = 4;

    /**
     * @return ChartConfig
     */
    public static function forField(FieldStatistics $field): array
    {
        $labels = array_map(strval(...), array_keys($field->distribution));
        $counts = array_values($field->distribution);

        if ($field->type === FormFieldBlueprint::TYPE_RATING) {
            return self::bars($labels, $counts, self::ratingColors($labels, $field->nps !== null), horizontal: false);
        }

        if ($field->type === FormFieldBlueprint::TYPE_NUMBER) {
            return self::bars($labels, $counts, array_fill(0, count($labels), self::PALETTE[0]), horizontal: false);
        }

        $singleChoice = $field->type !== FormFieldBlueprint::TYPE_CHECKBOX_LIST;

        if ($singleChoice && count($labels) <= self::DOUGHNUT_MAX_OPTIONS) {
            return self::doughnut($labels, $counts, self::palette(count($labels)));
        }

        return self::bars($labels, $counts, self::palette(count($labels)), horizontal: true);
    }

    /**
     * @param  array{promoters: int, passives: int, detractors: int, score: int}  $nps
     * @return ChartConfig
     */
    public static function npsSplit(array $nps): array
    {
        return self::doughnut(
            [
                (string) __('filament-form-builder::form.results.promoters'),
                (string) __('filament-form-builder::form.results.passives'),
                (string) __('filament-form-builder::form.results.detractors'),
            ],
            [$nps['promoters'], $nps['passives'], $nps['detractors']],
            [self::PROMOTER, self::PASSIVE, self::DETRACTOR],
        );
    }

    /**
     * @param  array<string, int>  $perDay  Y-m-d ⇒ count
     * @return ChartConfig
     */
    public static function timeline(array $perDay): array
    {
        return [
            'type' => 'line',
            'data' => [
                'labels' => array_map(
                    static fn (string $day): string => (string) date('m.d.', (int) strtotime($day)),
                    array_keys($perDay),
                ),
                'datasets' => [[
                    'label' => (string) __('filament-form-builder::form.results.submissions'),
                    'data' => array_values($perDay),
                    'borderColor' => self::PALETTE[0],
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => count($perDay) > 45 ? 0 : 3,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                    'x' => ['grid' => ['display' => false]],
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $labels
     * @param  list<int>  $counts
     * @param  list<string>  $colors
     * @return ChartConfig
     */
    private static function bars(array $labels, array $counts, array $colors, bool $horizontal): array
    {
        $valueAxis = $horizontal ? 'x' : 'y';
        $categoryAxis = $horizontal ? 'y' : 'x';

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => (string) __('filament-form-builder::form.results.answers'),
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderRadius' => 6,
                    'maxBarThickness' => 42,
                ]],
            ],
            'options' => [
                'indexAxis' => $horizontal ? 'y' : 'x',
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    $valueAxis => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                    $categoryAxis => ['grid' => ['display' => false]],
                ],
            ],
        ];
    }

    /**
     * @param  list<string>  $labels
     * @param  list<int>  $counts
     * @param  list<string>  $colors
     * @return ChartConfig
     */
    private static function doughnut(array $labels, array $counts, array $colors): array
    {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                    'hoverOffset' => 6,
                ]],
            ],
            'options' => [
                'cutout' => '62%',
                'plugins' => ['legend' => ['position' => 'bottom']],
                'scales' => [
                    'x' => ['display' => false],
                    'y' => ['display' => false],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private static function palette(int $count): array
    {
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = self::PALETTE[$i % count(self::PALETTE)];
        }

        return $colors;
    }

    /**
     * NPS groups on a 0–10 scale; otherwise a red → green ramp over the scale.
     *
     * @param  list<string>  $labels
     * @return list<string>
     */
    private static function ratingColors(array $labels, bool $isNps): array
    {
        $scores = array_map(floatval(...), $labels);

        if ($isNps) {
            return array_map(
                static fn (float $score): string => match (true) {
                    $score >= 9 => self::PROMOTER,
                    $score >= 7 => self::PASSIVE,
                    default => self::DETRACTOR,
                },
                $scores,
            );
        }

        $min = $scores === [] ? 0 : min($scores);
        $max = $scores === [] ? 1 : max($scores);
        $span = max(1, $max - $min);

        return array_map(static function (float $score) use ($min, $span): string {
            $hue = (int) round(($score - $min) / $span * 120);

            return "hsl({$hue}, 70%, 45%)";
        }, $scores);
    }
}
