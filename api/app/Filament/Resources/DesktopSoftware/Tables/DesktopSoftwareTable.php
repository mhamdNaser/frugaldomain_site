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
