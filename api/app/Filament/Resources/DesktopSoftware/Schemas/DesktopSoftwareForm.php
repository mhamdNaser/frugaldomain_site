<?php

namespace App\Filament\Resources\DesktopSoftware\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DesktopSoftwareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->required()
                ->maxLength(120)
                ->unique(ignoreRecord: true)
                ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/']),
            TextInput::make('version')
                ->maxLength(40),
            Select::make('platform')
                ->options([
                    'windows' => 'Windows',
                    'linux' => 'Linux',
                    'macos' => 'macOS',
                    'other' => 'Other',
                ])
                ->live()
                ->afterStateUpdated(function (Set $set, string $state): void {
                    if ($state !== 'other') {
                        $set('platform_name', null);
                    }
                })
                ->required(),
            TextInput::make('platform_name')
                ->label('Other operating system')
                ->visible(fn (Get $get): bool => $get('platform') === 'other')
                ->required(fn (Get $get): bool => $get('platform') === 'other')
                ->dehydrated()
                ->maxLength(120),
            TextInput::make('environment')
                ->label('Runtime environment')
                ->placeholder('e.g. .NET 8, Java 21, Electron')
                ->required()
                ->maxLength(255),
            TextInput::make('programming_language')
                ->label('Programming language')
                ->placeholder('e.g. C#, JavaScript, Python')
                ->required()
                ->maxLength(120),
            Toggle::make('is_open_source')
                ->label('Open source')
                ->default(false),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])
                ->default('draft')
                ->required(),
            TextInput::make('ordering')
                ->numeric()
                ->minValue(0)
                ->default(0),
            Textarea::make('summary')
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('readme')
                ->label('README')
                ->helperText('Paste the README text here; it is shown below the public download link.')
                ->rows(12)
                ->required()
                ->columnSpanFull(),
            FileUpload::make('file_path')
                ->label('Application installer or archive')
                ->disk('local')
                ->directory('desktop-software')
                ->visibility('private')
                ->acceptedFileTypes([
                    'application/vnd.microsoft.portable-executable',
                    'application/x-msdownload',
                    'application/zip',
                    'application/x-7z-compressed',
                    'application/x-tar',
                    'application/gzip',
                    'application/x-msi',
                    'application/x-debian-package',
                    'application/vnd.debian.binary-package',
                    'application/x-rpm',
                    'application/octet-stream',
                ])
                ->rules(['mimes:exe,zip,7z,tar,gz,msi,deb,rpm,appimage', 'max:204800'])
                ->maxSize(204800)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->columnSpanFull(),
            FileUpload::make('image_path')
                ->label('Application image')
                ->image()
                ->disk('local')
                ->directory('desktop-software/images')
                ->visibility('private')
                ->maxSize(4096)
                ->columnSpanFull(),
        ]);
    }
}
