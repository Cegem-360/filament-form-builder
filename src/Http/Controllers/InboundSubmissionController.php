<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Madbox99\FilamentFormBuilder\Actions\SubmitRegistrationForm;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\InboundPayloadMapper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Receives submissions from external forms (WordPress, Zapier, any HTTP
 * client) on a form's secret inbound URL and runs them through the same
 * pipeline as the embedded form.
 */
final class InboundSubmissionController extends Controller
{
    public function __invoke(Request $request, SubmitRegistrationForm $submitter, string $slug, string $token): JsonResponse
    {
        $rateLimited = $this->rateLimit($request, $slug);
        if ($rateLimited instanceof JsonResponse) {
            return $rateLimited;
        }

        $form = RegistrationForm::query()->where('slug', $slug)->first();

        // One identical 404 for every rejection, so the response never tells
        // whether the slug, the switch or the token was wrong.
        if (! $form instanceof RegistrationForm || ! $form->is_active || ! $form->inbound_enabled || ! $form->inboundTokenMatches($token)) {
            throw new NotFoundHttpException(__('filament-form-builder::form.errors.not_found'));
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->all();
        $maxFields = (int) config('filament-form-builder.inbound.max_fields', 100);

        if (count(InboundPayloadMapper::flatten($payload)) > $maxFields) {
            return new JsonResponse([
                'message' => __('filament-form-builder::form.errors.too_many_fields', ['max' => $maxFields]),
            ], 413);
        }

        $mappedPayload = InboundPayloadMapper::map($form, $payload);
        $blueprints = FormFieldBlueprint::fromForm($form);

        Validator::make($mappedPayload->mapped, $this->rules($blueprints), [], $this->attributes($blueprints))->validate();

        $formData = [];
        foreach ($blueprints as $blueprint) {
            $formData[$blueprint->key] = $mappedPayload->mapped[$blueprint->key] ?? $blueprint->defaultValue();
        }

        $submission = $submitter->handle($form, $formData, $mappedPayload->unmapped, 'inbound');

        return new JsonResponse(['status' => 'ok', 'submission_id' => $submission?->getKey()], 201);
    }

    /**
     * Throttled here rather than with the `throttle` middleware: route
     * middleware priority runs the throttle before `ExpectsJson`, so a form
     * posted without an Accept header would get an HTML 429 page. Counted
     * before the form lookup so token guessing is throttled too.
     */
    private function rateLimit(Request $request, string $slug): ?JsonResponse
    {
        [$attempts, $minutes] = array_map(
            intval(...),
            explode(',', (string) config('filament-form-builder.routes.throttle.inbound', '30,1')) + [1 => '1'],
        );

        $key = 'form-builder-inbound:'.$slug.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, max(1, $attempts))) {
            return new JsonResponse(
                ['message' => __('filament-form-builder::form.errors.rate_limit', ['seconds' => RateLimiter::availableIn($key)])],
                429,
                ['Retry-After' => (string) RateLimiter::availableIn($key)],
            );
        }

        RateLimiter::hit($key, max(1, $minutes) * 60);

        return null;
    }

    /**
     * @param  list<FormFieldBlueprint>  $blueprints
     * @return array<string, list<string>>
     */
    private function rules(array $blueprints): array
    {
        $rules = [];

        foreach ($blueprints as $blueprint) {
            $rules[$blueprint->key] = $blueprint->validationRules();

            $itemRules = $blueprint->arrayItemValidationRules();
            if ($itemRules !== []) {
                $rules["{$blueprint->key}.*"] = $itemRules;
            }
        }

        return $rules;
    }

    /**
     * @param  list<FormFieldBlueprint>  $blueprints
     * @return array<string, string>
     */
    private function attributes(array $blueprints): array
    {
        $attributes = [];

        foreach ($blueprints as $blueprint) {
            $attributes[$blueprint->key] = $blueprint->label;
        }

        return $attributes;
    }
}
