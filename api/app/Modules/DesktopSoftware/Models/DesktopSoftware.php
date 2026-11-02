<?php

namespace App\Modules\DesktopSoftware\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DesktopSoftware extends Model
{
    protected $table = 'desktop_software';

    protected $fillable = [
        'slug',
        'name',
        'summary',
        'readme',
        'platform',
        'platform_name',
        'environment',
        'is_open_source',
        'version',
        'status',
        'file_path',
        'ordering',
    ];

    protected $casts = [
        'is_open_source' => 'boolean',
        'ordering' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updated(function (self $software): void {
            if ($software->isDirty('file_path') && $software->getOriginal('file_path')) {
                Storage::disk('local')->delete($software->getOriginal('file_path'));
            }
        });

        static::deleting(function (self $software): void {
            if ($software->file_path) {
                Storage::disk('local')->delete($software->file_path);
            }
        });
    }

    public function downloadFilename(): string
    {
        $extension = pathinfo((string) $this->file_path, PATHINFO_EXTENSION);

        return $this->slug.($extension !== '' ? '.'.$extension : '');
    }
}
