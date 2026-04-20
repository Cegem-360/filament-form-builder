<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

/**
 * @extends Factory<FormSubmission>
 */
final class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenantForeignKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');

        return [
            'registration_form_id' => RegistrationForm::factory(),
            $tenantForeignKey => null,
            'lead_id' => null,
            'data' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function withoutLead(): static
    {
        return $this->state(fn (array $attributes): array => [
            'lead_id' => null,
        ]);
    }
}
