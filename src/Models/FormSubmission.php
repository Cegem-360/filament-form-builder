<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Madbox99\FilamentFormBuilder\Database\Factories\FormSubmissionFactory;
use Override;

class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    protected $table = 'form_submissions';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected static function newFactory(): FormSubmissionFactory
    {
        return FormSubmissionFactory::new();
    }

    /**
     * @return BelongsTo<RegistrationForm, $this>
     */
    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class);
    }

    /**
     * Generic tenant relationship used by Filament for automatic scoping.
     *
     * @return BelongsTo<Model, $this>
     */
    public function tenant(): BelongsTo
    {
        /** @var class-string<Model> $tenantModel */
        $tenantModel = (string) config('filament-form-builder.tenant_model', Model::class);
        $foreignKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');

        return $this->belongsTo($tenantModel, $foreignKey);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
