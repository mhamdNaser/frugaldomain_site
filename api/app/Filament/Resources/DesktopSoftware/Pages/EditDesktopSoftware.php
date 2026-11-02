<?php

namespace App\Filament\Resources\DesktopSoftware\Pages;

use App\Filament\Resources\DesktopSoftware\DesktopSoftwareResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDesktopSoftware extends EditRecord
{
    protected static string $resource = DesktopSoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
