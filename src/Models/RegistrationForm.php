<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Madbox99\FilamentFormBuilder\Casts\SubmissionActionsCast;
use Madbox99\FilamentFormBuilder\Contracts\FormTenantResolver;
use Madbox99\FilamentFormBuilder\Database\Factories\RegistrationFormFactory;
use Override;

class RegistrationForm extends Model
{
    /** @use HasFactory<RegistrationFormFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'registration_forms';

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected static function newFactory(): RegistrationFormFactory
    {
        return RegistrationFormFactory::new();
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
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function getPublicUrl(): string
    {
        return route('form-builder.form.show', $this->slug);
    }

    public function getEmbedUrl(): string
    {
        return route('form-builder.form.embed', $this->slug);
    }

    public function getWidgetScriptUrl(): string
    {
        return route('form-builder.widget.script');
    }

    public function getIframeSnippet(): string
    {
        return sprintf(
            '<iframe src="%s" width="100%%" height="600" frameborder="0"></iframe>',
            $this->getEmbedUrl(),
        );
    }

    public function getWidgetSnippet(): string
    {
        return sprintf(
            '<div id="marketinghub-form-%s"></div>' . "\n" . '<script src="%s" data-form="%s" async></script>',
            $this->slug,
            $this->getWidgetScriptUrl(),
            $this->slug,
        );
    }

    public function tenantSlug(): ?string
    {
        $foreignKey = (string) config('filament-form-builder.tenant_foreign_key', 'team_id');
        $tenantKey = $this->getAttribute($foreignKey);

        if ($tenantKey === null) {
            return null;
        }

        return app(FormTenantResolver::class)->resolveSlugByTenantKey($tenantKey);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'submission_actions' => SubmissionActionsCast::class,
            'is_active' => 'boolean',
            'submissions_count' => 'integer',
        ];
    }
}
