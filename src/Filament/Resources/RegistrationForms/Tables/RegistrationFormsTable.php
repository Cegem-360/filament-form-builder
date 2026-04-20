<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Filament\Resources\RegistrationForms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RegistrationFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-form-builder::form.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('filament-form-builder::form.fields.slug'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('filament-form-builder::form.fields.active'))
                    ->boolean(),
                TextColumn::make('submissions_count')
                    ->label(__('filament-form-builder::form.fields.submissions_count'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament-form-builder::form.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
