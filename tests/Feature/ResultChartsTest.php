<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\ResultCharts;
use Madbox99\FilamentFormBuilder\Support\SubmissionStatistics;
use Madbox99\FilamentFormBuilder\ValueObjects\FieldStatistics;

uses(RefreshDatabase::class);

function fieldStats(string $type, array $distribution, ?array $nps = null, int $answered = 4): FieldStatistics
{
    return new FieldStatistics('k', 'Label', $type, $answered, $answered, $distribution, null, null, null, $nps, true);
}

it('draws a doughnut for a single-choice field with few options', function (): void {
    $chart = ResultCharts::forField(fieldStats(FormFieldBlueprint::TYPE_RADIO, ['Igen' => 3, 'Nem' => 1]));

    expect($chart['type'])->toBe('doughnut')
        ->and($chart['data']['labels'])->toBe(['Igen', 'Nem'])
        ->and($chart['data']['datasets'][0]['data'])->toBe([3, 1])
        ->and($chart['data']['datasets'][0]['backgroundColor'])->toHaveCount(2);
});

it('draws horizontal bars for a field with many options or multiple answers', function (): void {
    $many = ResultCharts::forField(fieldStats(FormFieldBlueprint::TYPE_SELECT, ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5]));
    $multi = ResultCharts::forField(fieldStats(FormFieldBlueprint::TYPE_CHECKBOX_LIST, ['A' => 1, 'B' => 2]));

    expect($many['type'])->toBe('bar')
        ->and($many['options']['indexAxis'])->toBe('y')
        ->and($multi['type'])->toBe('bar');
});

it('colours a 0-10 rating by NPS group', function (): void {
    $distribution = array_fill_keys(array_map('strval', range(0, 10)), 1);
    $chart = ResultCharts::forField(fieldStats(FormFieldBlueprint::TYPE_RATING, $distribution, ['promoters' => 2, 'passives' => 2, 'detractors' => 7, 'score' => -45]));

    $colors = $chart['data']['datasets'][0]['backgroundColor'];

    expect($chart['type'])->toBe('bar')
        ->and($colors[0])->toBe(ResultCharts::DETRACTOR)
        ->and($colors[6])->toBe(ResultCharts::DETRACTOR)
        ->and($colors[7])->toBe(ResultCharts::PASSIVE)
        ->and($colors[9])->toBe(ResultCharts::PROMOTER);
});

it('draws the NPS split as a doughnut', function (): void {
    $chart = ResultCharts::npsSplit(['promoters' => 5, 'passives' => 3, 'detractors' => 2, 'score' => 30]);

    expect($chart['type'])->toBe('doughnut')
        ->and($chart['data']['datasets'][0]['data'])->toBe([5, 3, 2])
        ->and($chart['data']['datasets'][0]['backgroundColor'])->toBe([ResultCharts::PROMOTER, ResultCharts::PASSIVE, ResultCharts::DETRACTOR]);
});

it('counts submissions per day for the timeline, filling empty days', function (): void {
    $form = RegistrationForm::factory()->create(['fields' => [['type' => FormFieldBlueprint::TYPE_TEXT, 'data' => ['name' => 't', 'label' => 'T']]]]);
    FormSubmission::factory()->for($form)->create(['created_at' => '2026-11-10 09:00:00']);
    FormSubmission::factory()->for($form)->create(['created_at' => '2026-11-10 17:00:00']);
    FormSubmission::factory()->for($form)->create(['created_at' => '2026-11-12 08:00:00']);

    expect(SubmissionStatistics::timeline($form->submissions()->getQuery()))->toBe([
        '2026-11-10' => 2,
        '2026-11-11' => 0,
        '2026-11-12' => 1,
    ]);
});

it('draws the timeline as a filled line', function (): void {
    $chart = ResultCharts::timeline(['2026-11-10' => 2, '2026-11-11' => 0]);

    expect($chart['type'])->toBe('line')
        ->and($chart['data']['datasets'][0]['data'])->toBe([2, 0])
        ->and($chart['data']['datasets'][0]['fill'])->toBeTrue();
});
