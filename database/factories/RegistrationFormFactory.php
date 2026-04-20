<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;

/**
 * @extends Factory<RegistrationForm>
 */
final class RegistrationFormFactory extends Factory
{
    protected $model = RegistrationForm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenantForeignKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');

        return [
            $tenantForeignKey => null,
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'required' => true],
            ],
            'thank_you_message' => fake()->sentence(),
            'redirect_url' => null,
            'is_active' => true,
            'submissions_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
