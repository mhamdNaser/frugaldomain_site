<?php

namespace App\Filament\Resources\SiteRatings;

use App\Filament\Resources\SiteRatings\Pages\ListSiteRatings;
use App\Modules\Rating\Models\SiteRating;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Visitors' ratings and comments: read and delete only, nothing is edited. */
class SiteRatingsResource extends Resource
{
    protected static ?string $model = SiteRating::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Ratings';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('path')->searchable()->sortable(),
                TextColumn::make('stars')->sortable()->formatStateUsing(fn (int $state) => str_repeat('★', $state)),
                TextColumn::make('comment')->limit(80)->wrap()->searchable(),
                TextColumn::make('locale')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('stars')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
                SelectFilter::make('locale')->options(['en' => 'en', 'ar' => 'ar', 'hi' => 'hi', 'zh' => 'zh', 'tr' => 'tr']),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListSiteRatings::route('/')];
    }
}
