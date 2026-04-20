<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Schemas\FormSubmissionInfolist;
use Madbox99\FilamentFormBuilder\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;
use Madbox99\FilamentFormBuilder\Models\FormSubmission;
use Override;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        $group = config('filament-form-builder.filament.navigation_group');

        return is_string($group) ? $group : null;
    }

    public static function isScopedToTenant(): bool
    {
        return (bool) config('filament-form-builder.filament.scoped_to_tenant', true);
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('filament-form-builder::form.resource.submission_singular');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('filament-form-builder::form.resource.submission_plural');
    }

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return FormSubmissionInfolist::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'view' => ViewFormSubmission::route('/{record}'),
        ];
    }
}
