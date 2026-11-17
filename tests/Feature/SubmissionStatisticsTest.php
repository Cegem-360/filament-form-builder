<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionStatistics;
use Madbox99\FilamentFormBuilder\ValueObjects\FieldStatistics;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
});

/**
 * @param  list<array<string, mixed>>  $fields
 * @param  list<array<string, mixed>>  $payloads
 * @return array<string, FieldStatistics>
 */
function statsFor(array $fields, array $payloads): array
{
    $form = RegistrationForm::factory()->create(['fields' => $fields]);

    foreach ($payloads as $payload) {
        FormSubmission::factory()->for($form)->create(['data' => $payload]);
    }

    $stats = SubmissionStatistics::forForm($form, $form->submissions()->getQuery());

    return collect($stats)->keyBy(fn (FieldStatistics $s): string => $s->key)->all();
}

$ab = [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']];

it('counts radio answers by option label and leaves missing answers out', function () use ($ab): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_RADIO, 'data' => ['name' => 'r', 'label' => 'R', 'options' => $ab]]],
        [['r' => 'a'], ['r' => 'a'], ['r' => 'b'], []],
    )['r'];

    expect($stats->distribution)->toBe(['A' => 2, 'B' => 1])
        ->and($stats->answered)->toBe(3)
        ->and($stats->total)->toBe(4)
        ->and($stats->isChartable)->toBeTrue();
});

it('counts every ticked option of a checkbox list', function () use ($ab): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_CHECKBOX_LIST, 'data' => ['name' => 'c', 'label' => 'C', 'options' => $ab]]],
        [['c' => ['a', 'b']], ['c' => ['a']]],
    )['c'];

    expect($stats->distribution)->toBe(['A' => 2, 'B' => 1])
        ->and($stats->answered)->toBe(2);
});

it('counts checkboxes as yes or no', function (): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_CHECKBOX, 'data' => ['name' => 'ok', 'label' => 'OK']]],
        [['ok' => true], ['ok' => false], ['ok' => true]],
    )['ok'];

    expect($stats->distribution)->toBe(['Yes' => 2, 'No' => 1]);
});

it('keeps option values that are no longer on the form under their raw value', function () use ($ab): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_SELECT, 'data' => ['name' => 's', 'label' => 'S', 'options' => $ab]]],
        [['s' => 'a'], ['s' => 'z']],
    )['s'];

    expect($stats->distribution)->toBe(['A' => 1, 'B' => 0, 'z' => 1]);
});

it('summarises a 0-10 rating with an NPS score', function (): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_RATING, 'data' => ['name' => 'nps', 'label' => 'NPS', 'min' => 0, 'max' => 10]]],
        [['nps' => 10], ['nps' => 9], ['nps' => '8'], ['nps' => 3], ['nps' => null]],
    )['nps'];

    expect($stats->answered)->toBe(4)
        ->and($stats->average)->toBe(7.5)
        ->and($stats->min)->toBe(3)
        ->and($stats->max)->toBe(10)
        ->and($stats->distribution)->toBe(['0' => 0, '1' => 0, '2' => 0, '3' => 1, '4' => 0, '5' => 0, '6' => 0, '7' => 0, '8' => 1, '9' => 1, '10' => 1])
        ->and($stats->nps)->toBe(['promoters' => 2, 'passives' => 1, 'detractors' => 1, 'score' => 25]);
});

it('does not compute NPS for a 1-10 rating', function (): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_RATING, 'data' => ['name' => 'q', 'label' => 'Q']]],
        [['q' => 9]],
    )['q'];

    expect($stats->nps)->toBeNull()
        ->and($stats->average)->toBe(9.0);
});

it('summarises number fields with average, min and max', function (): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_NUMBER, 'data' => ['name' => 'n', 'label' => 'N']]],
        [['n' => 2], ['n' => '4.5'], ['n' => 'x']],
    )['n'];

    expect($stats->answered)->toBe(2)
        ->and($stats->average)->toBe(3.25)
        ->and($stats->min)->toBe(2)
        ->and($stats->max)->toBe(4.5)
        ->and($stats->distribution)->toBe(['2' => 1, '4.5' => 1]);
});

it('only counts answers for text fields', function (): void {
    $stats = statsFor(
        [['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 't', 'label' => 'T']]],
        [['t' => 'hello'], ['t' => ''], ['t' => '  ']],
    )['t'];

    expect($stats->answered)->toBe(1)
        ->and($stats->isChartable)->toBeFalse()
        ->and($stats->distribution)->toBe([]);
});

it('handles a form without submissions', function () use ($ab): void {
    $stats = statsFor(
        [
            ['type' => FormFieldBlueprint::TYPE_RATING, 'data' => ['name' => 'nps', 'label' => 'NPS', 'min' => 0, 'max' => 10]],
            ['type' => FormFieldBlueprint::TYPE_RADIO, 'data' => ['name' => 'r', 'label' => 'R', 'options' => $ab]],
        ],
        [],
    );

    expect($stats['nps']->total)->toBe(0)
        ->and($stats['nps']->average)->toBeNull()
        ->and($stats['nps']->nps)->toBeNull()
        ->and($stats['r']->distribution)->toBe(['A' => 0, 'B' => 0]);
});

it('only counts the submissions in the given query', function (): void {
    $form = RegistrationForm::factory()->create(['fields' => [
        ['type' => FormFieldBlueprint::TYPE_RATING, 'data' => ['name' => 'q', 'label' => 'Q']],
    ]]);
    FormSubmission::factory()->for($form)->create(['data' => ['q' => 2], 'created_at' => now()->subDays(10)]);
    FormSubmission::factory()->for($form)->create(['data' => ['q' => 8]]);

    $stats = SubmissionStatistics::forForm($form, $form->submissions()->getQuery()->where('created_at', '>=', now()->subDay()));

    expect($stats[0]->total)->toBe(1)
        ->and($stats[0]->average)->toBe(8.0);
});
