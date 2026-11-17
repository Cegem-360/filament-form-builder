<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Madbox99\FilamentFormBuilder\Support\SubmissionDataPresenter;
use Madbox99\FilamentFormBuilder\Support\SubmissionLinks;
use Override;

/**
 * @property FormSubmission $record
 */
class ViewFormSubmission extends ViewRecord
{
    protected static string $resource = FormSubmissionResource::class;

    /**
     * The submitter, not "View submission": that is what people look for.
     */
    #[Override]
    public function getTitle(): string|Htmlable
    {
        $contact = SubmissionDataPresenter::contact($this->getSubmission());

        return $contact['name'] ?? $contact['email']
            ?? __('filament-form-builder::form.submission_view.title_fallback', ['id' => $this->getSubmission()->getKey()]);
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        $submission = $this->getSubmission();
        $origin = SubmissionDataPresenter::origin($submission);

        return implode(' · ', array_filter([
            $submission->registrationForm?->name,
            $submission->created_at?->translatedFormat('Y. F j. H:i').' ('.$submission->created_at?->diffForHumans().')',
            $origin['form'] ?? null,
        ]));
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('replyByEmail')
                ->label(__('filament-form-builder::form.submission_view.reply'))
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(fn (): ?string => $this->replyUrl())
                ->visible(fn (): bool => $this->replyUrl() !== null),
            Action::make('openLead')
                ->label(__('filament-form-builder::form.submission_view.open_lead'))
                ->icon(Heroicon::OutlinedUser)
                ->color('gray')
                ->url(fn (): ?string => SubmissionLinks::leadUrl($this->getSubmission()->lead_id))
                ->visible(fn (): bool => SubmissionLinks::leadUrl($this->getSubmission()->lead_id) !== null),
        ];
    }

    private function replyUrl(): ?string
    {
        $email = SubmissionDataPresenter::contact($this->getSubmission())['email'];

        if ($email === null) {
            return null;
        }

        $subject = $this->getSubmission()->registrationForm?->name;

        return 'mailto:'.$email.($subject !== null ? '?subject='.rawurlencode('Re: '.$subject) : '');
    }

    private function getSubmission(): FormSubmission
    {
        /** @var FormSubmission $record */
        $record = $this->getRecord();

        return $record;
    }
}
