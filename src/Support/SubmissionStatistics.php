<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

use Illuminate\Database\Eloquent\Builder;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\ValueObjects\FieldStatistics;

/**
 * Per-field answer statistics of a form, computed in PHP from the submissions'
 * `data` payloads so it runs the same on MySQL and SQLite.
 */
final class SubmissionStatistics
{
    private const array CHOICE_TYPES = [
        FormFieldBlueprint::TYPE_SELECT,
        FormFieldBlueprint::TYPE_RADIO,
        FormFieldBlueprint::TYPE_CHECKBOX_LIST,
        FormFieldBlueprint::TYPE_CHECKBOX,
    ];

    private const array NUMERIC_TYPES = [
        FormFieldBlueprint::TYPE_NUMBER,
        FormFieldBlueprint::TYPE_RATING,
    ];

    /**
     * @param  Builder<FormSubmission>  $submissions  an already scoped/filtered query of this form's submissions
     * @return list<FieldStatistics>
     */
    public static function forForm(RegistrationForm $form, Builder $submissions): array
    {
        $blueprints = FormFieldBlueprint::fromForm($form);

        /** @var array<string, array{answered: int, counts: array<string, int>, numbers: list<int|float>}> $tally */
        $tally = [];
        foreach ($blueprints as $blueprint) {
            $tally[$blueprint->key] = ['answered' => 0, 'counts' => self::initialCounts($blueprint), 'numbers' => []];
        }

        $total = 0;

        $submissions->clone()->reorder()->select(['id', 'data'])->chunkById(500, function ($chunk) use ($blueprints, &$tally, &$total): void {
            foreach ($chunk as $submission) {
                $total++;
                $data = is_array($submission->data) ? $submission->data : [];

                foreach ($blueprints as $blueprint) {
                    self::tallyValue($blueprint, $data[$blueprint->key] ?? null, $tally[$blueprint->key]);
                }
            }
        });

        return array_map(
            static fn (FormFieldBlueprint $blueprint): FieldStatistics => self::summarise($blueprint, $tally[$blueprint->key], $total),
            $blueprints,
        );
    }

    /**
     * @return array<string, int>
     */
    private static function initialCounts(FormFieldBlueprint $blueprint): array
    {
        if ($blueprint->type === FormFieldBlueprint::TYPE_RATING) {
            $counts = [];
            for ($score = (int) $blueprint->min; $score <= (int) $blueprint->max; $score++) {
                $counts[(string) $score] = 0;
            }

            return $counts;
        }

        if ($blueprint->type === FormFieldBlueprint::TYPE_CHECKBOX) {
            return [self::yes() => 0, self::no() => 0];
        }

        $counts = [];
        foreach ($blueprint->options as $option) {
            $counts[$option['label']] = 0;
        }

        return $counts;
    }

    /**
     * @param  array{answered: int, counts: array<string, int>, numbers: list<int|float>}  $tally
     */
    private static function tallyValue(FormFieldBlueprint $blueprint, mixed $value, array &$tally): void
    {
        if (in_array($blueprint->type, self::NUMERIC_TYPES, true)) {
            if (! is_numeric($value)) {
                return;
            }

            $number = self::number($value);
            $tally['answered']++;
            $tally['numbers'][] = $number;
            $label = (string) $number;
            $tally['counts'][$label] = ($tally['counts'][$label] ?? 0) + 1;

            return;
        }

        if ($blueprint->type === FormFieldBlueprint::TYPE_CHECKBOX) {
            if ($value === null || $value === '') {
                return;
            }

            $tally['answered']++;
            $label = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? self::yes() : self::no();
            $tally['counts'][$label]++;

            return;
        }

        if (in_array($blueprint->type, self::CHOICE_TYPES, true)) {
            $values = array_values(array_filter(
                array_map(self::scalarString(...), is_array($value) ? $value : [$value]),
                static fn (string $item): bool => $item !== '',
            ));

            if ($values === []) {
                return;
            }

            $tally['answered']++;
            foreach ($values as $item) {
                $label = self::optionLabel($blueprint, $item);
                $tally['counts'][$label] = ($tally['counts'][$label] ?? 0) + 1;
            }

            return;
        }

        if (self::scalarString($value) !== '') {
            $tally['answered']++;
        }
    }

    /**
     * @param  array{answered: int, counts: array<string, int>, numbers: list<int|float>}  $tally
     */
    private static function summarise(FormFieldBlueprint $blueprint, array $tally, int $total): FieldStatistics
    {
        $numbers = $tally['numbers'];
        $isNumeric = in_array($blueprint->type, self::NUMERIC_TYPES, true);
        $counts = $tally['counts'];

        if ($blueprint->type === FormFieldBlueprint::TYPE_NUMBER) {
            uksort($counts, static fn (string $a, string $b): int => (float) $a <=> (float) $b);
        }

        return new FieldStatistics(
            key: $blueprint->key,
            label: $blueprint->label,
            type: $blueprint->type,
            answered: $tally['answered'],
            total: $total,
            distribution: $isNumeric || in_array($blueprint->type, self::CHOICE_TYPES, true) ? $counts : [],
            average: $isNumeric && $numbers !== [] ? round(array_sum($numbers) / count($numbers), 2) : null,
            min: $isNumeric && $numbers !== [] ? min($numbers) : null,
            max: $isNumeric && $numbers !== [] ? max($numbers) : null,
            nps: $blueprint->isNpsScale() && $numbers !== [] ? self::nps($numbers) : null,
            isChartable: $isNumeric || in_array($blueprint->type, self::CHOICE_TYPES, true),
        );
    }

    /**
     * Promoters score 9–10, passives 7–8, detractors 0–6;
     * NPS = % promoters − % detractors, rounded to a whole number.
     *
     * @param  non-empty-list<int|float>  $scores
     * @return array{promoters: int, passives: int, detractors: int, score: int}
     */
    private static function nps(array $scores): array
    {
        $promoters = count(array_filter($scores, static fn (int|float $s): bool => $s >= 9));
        $passives = count(array_filter($scores, static fn (int|float $s): bool => $s >= 7 && $s < 9));
        $detractors = count($scores) - $promoters - $passives;

        return [
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'score' => (int) round(($promoters - $detractors) / count($scores) * 100),
        ];
    }

    private static function optionLabel(FormFieldBlueprint $blueprint, string $value): string
    {
        foreach ($blueprint->options as $option) {
            if ($option['value'] === $value) {
                return $option['label'];
            }
        }

        return $value;
    }

    private static function number(mixed $value): int|float
    {
        $float = (float) $value;

        return floor($float) === $float ? (int) $float : $float;
    }

    private static function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function yes(): string
    {
        return (string) __('filament-form-builder::form.values.yes');
    }

    private static function no(): string
    {
        return (string) __('filament-form-builder::form.values.no');
    }
}
