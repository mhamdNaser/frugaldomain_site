<?php

namespace App\Filament\Resources\DesktopSoftware\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DesktopSoftwareTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('platform')->badge()->sortable(),
                TextColumn::make('environment')->searchable()->toggleable(),
                TextColumn::make('programming_language')->searchable()->toggleable(),
                TextColumn::make('image_path')
                    ->label('Image')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Uploaded' : 'None'),
                IconColumn::make('is_open_source')->boolean()->label('Open source'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('ordering')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
