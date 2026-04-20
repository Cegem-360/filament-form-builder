<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\RegistrationFormResource;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Override;

class EditRegistrationForm extends EditRecord
{
    protected static string $resource = RegistrationFormResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('embedCode')
                ->label(__('filament-form-builder::form.actions.embed_code'))
                ->color('info')
                ->modalHeading(__('filament-form-builder::form.actions.embed_code'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament-form-builder::form.actions.close'))
                ->schema(fn (): array => $this->getEmbedCodeSchema()),
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<int, Section>
     */
    private function getEmbedCodeSchema(): array
    {
        /** @var RegistrationForm $record */
        $record = $this->getRecord();

        return [
            Section::make(__('filament-form-builder::form.sections.embed_direct'))
                ->description(__('filament-form-builder::form.embed.direct_desc'))
                ->schema([
                    Textarea::make('direct_link')
                        ->label(__('filament-form-builder::form.sections.embed_direct'))
                        ->default($record->getPublicUrl())
                        ->readOnly()
                        ->rows(1)
                        ->extraAttributes(['class' => 'font-mono text-sm'])
                        ->columnSpanFull(),
                ]),
            Section::make(__('filament-form-builder::form.sections.embed_widget'))
                ->description(__('filament-form-builder::form.embed.widget_desc'))
                ->schema([
                    Textarea::make('widget_code')
                        ->label(__('filament-form-builder::form.sections.embed_widget'))
                        ->default($record->getWidgetSnippet())
                        ->readOnly()
                        ->rows(3)
                        ->extraAttributes(['class' => 'font-mono text-sm'])
                        ->columnSpanFull(),
                ]),
            Section::make(__('filament-form-builder::form.sections.embed_iframe'))
                ->description(__('filament-form-builder::form.embed.iframe_desc'))
                ->schema([
                    Textarea::make('iframe_code')
                        ->label(__('filament-form-builder::form.sections.embed_iframe'))
                        ->default($record->getIframeSnippet())
                        ->readOnly()
                        ->rows(3)
                        ->extraAttributes(['class' => 'font-mono text-sm'])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
