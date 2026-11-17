<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\RegistrationFormResource;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Madbox99\FilamentFormBuilder\Support\FormFieldBlueprint;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;
use Madbox99\FilamentFormBuilder\Support\SubmissionStatistics;
use Madbox99\FilamentFormBuilder\ValueObjects\FieldStatistics;
use Override;

/**
 * Per-field results of one form: answer distribution, averages and, for a
 * 0–10 rating, NPS. Optionally limited to a submission date range.
 *
 * @property RegistrationForm $record
 */
class FormResults extends Page
{
    use InteractsWithRecord;

    protected static string $resource = RegistrationFormResource::class;

    protected string $view = 'filament-form-builder::pages.form-results';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $until = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('filament-form-builder::form.results.title', ['form' => $this->getRecord()->name]);
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-form-builder::form.results.navigation_label');
    }

    /**
     * Every field except the submitter's name, e-mail and phone: those are
     * contact details, not answers worth charting.
     *
     * @return list<FieldStatistics>
     */
    public function getStatistics(): array
    {
        $contactKeys = SubmissionDataPresenter::contactKeys(FormFieldBlueprint::fromForm($this->getRecord()));

        return array_values(array_filter(
            SubmissionStatistics::forForm($this->getRecord(), $this->submissionsQuery()),
            static fn (FieldStatistics $field): bool => ! in_array($field->key, $contactKeys, true),
        ));
    }

    /**
     * @return array<string, int> Y-m-d ⇒ submissions that day
     */
    public function getTimeline(): array
    {
        return SubmissionStatistics::timeline($this->submissionsQuery());
    }

    /**
     * Headline numbers: the first rating field's average and, when it is a
     * 0–10 scale, its NPS; plus how much of the form people fill in.
     *
     * @param  list<FieldStatistics>  $statistics
     * @return array{rating: FieldStatistics|null, nps: FieldStatistics|null, completion: int|null}
     */
    public function getHighlights(array $statistics): array
    {
        $ratings = array_values(array_filter($statistics, static fn (FieldStatistics $field): bool => $field->type === FormFieldBlueprint::TYPE_RATING && $field->average !== null));
        $nps = array_values(array_filter($ratings, static fn (FieldStatistics $field): bool => $field->nps !== null));
        // The NPS question already has its own card; the average comes from another rating.
        $ratings = array_values(array_filter($ratings, static fn (FieldStatistics $field): bool => $field->nps === null));
        $withTotal = array_filter($statistics, static fn (FieldStatistics $field): bool => $field->total > 0);

        return [
            'rating' => $ratings[0] ?? null,
            'nps' => $nps[0] ?? null,
            'completion' => $withTotal === [] ? null : (int) round(array_sum(array_map(
                static fn (FieldStatistics $field): float => $field->answered / $field->total,
                $withTotal,
            )) / count($withTotal) * 100),
        ];
    }

    /**
     * Quick ranges for the date filter: last 7/30/90 days, or everything.
     */
    public function setRange(string $range): void
    {
        $days = ['7' => 7, '30' => 30, '90' => 90][$range] ?? null;

        $this->from = $days !== null ? now()->subDays($days - 1)->toDateString() : null;
        $this->until = null;
    }

    public function activeRange(): ?string
    {
        if ($this->until !== null && $this->until !== '') {
            return null;
        }

        if ($this->from === null || $this->from === '') {
            return 'all';
        }

        // Numeric-string array keys become ints in PHP, hence the cast.
        foreach (['7' => 7, '30' => 30, '90' => 90] as $key => $days) {
            if ($this->from === now()->subDays($days - 1)->toDateString()) {
                return (string) $key;
            }
        }

        return null;
    }

    public function getSubmissionTotal(): int
    {
        return SubmissionStatistics::total($this->submissionsQuery());
    }

    /**
     * @return Builder<FormSubmission>
     */
    private function submissionsQuery(): Builder
    {
        /** @var RegistrationForm $form */
        $form = $this->getRecord();

        return $form->submissions()->getQuery()
            ->when(self::validDate($this->from), fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
            ->when(self::validDate($this->until), fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date));
    }

    private static function validDate(?string $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label(__('filament-form-builder::form.results.edit_form'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->url(fn (): string => RegistrationFormResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
