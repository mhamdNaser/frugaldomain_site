<?php

namespace App\Filament\Resources\DesktopSoftware;

use App\Filament\Resources\DesktopSoftware\Pages\CreateDesktopSoftware;
use App\Filament\Resources\DesktopSoftware\Pages\EditDesktopSoftware;
use App\Filament\Resources\DesktopSoftware\Pages\ListDesktopSoftware;
use App\Filament\Resources\DesktopSoftware\Schemas\DesktopSoftwareForm;
use App\Filament\Resources\DesktopSoftware\Tables\DesktopSoftwareTable;
use App\Modules\DesktopSoftware\Models\DesktopSoftware as DesktopSoftwareModel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DesktopSoftwareResource extends Resource
{
    protected static ?string $model = DesktopSoftwareModel::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Desktop downloads';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DesktopSoftwareForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesktopSoftwareTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesktopSoftware::route('/'),
            'create' => CreateDesktopSoftware::route('/create'),
            'edit' => EditDesktopSoftware::route('/{record}/edit'),
        ];
    }
}
