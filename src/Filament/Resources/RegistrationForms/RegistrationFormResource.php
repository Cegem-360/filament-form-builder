<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages\CreateRegistrationForm;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages\EditRegistrationForm;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Pages\ListRegistrationForms;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Schemas\RegistrationFormForm;
use Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Tables\RegistrationFormsTable;
use Madbox99\FilamentFormBuilder\Models\RegistrationForm;
use Override;

class RegistrationFormResource extends Resource
{
    protected static ?string $model = RegistrationForm::class;

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

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
        return __('filament-form-builder::form.resource.form_singular');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('filament-form-builder::form.resource.form_plural');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return RegistrationFormForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return RegistrationFormsTable::configure($table);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationForms::route('/'),
            'create' => CreateRegistrationForm::route('/create'),
            'edit' => EditRegistrationForm::route('/{record}/edit'),
        ];
    }
}
