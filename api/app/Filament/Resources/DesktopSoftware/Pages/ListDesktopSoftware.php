<?php

namespace App\Filament\Resources\DesktopSoftware\Pages;

use App\Filament\Resources\DesktopSoftware\DesktopSoftwareResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesktopSoftware extends ListRecords
{
    protected static string $resource = DesktopSoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
