<?php

namespace App\Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LearningMedia extends Model
{
    protected $table = 'learning_media';

    protected $fillable = ['course_id', 'kind', 'path', 'original_name', 'mime_type', 'size', 'alt'];

    protected $casts = ['size' => 'integer'];

    protected static function booted(): void
    {
        static::deleting(function (self $media): void {
            Storage::disk('local')->delete($media->path);
        });
    }

    public function url(): string
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        return url('/api/learn/media/'.$this->id.($extension !== '' ? '.'.$extension : ''));
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'kind' => $this->kind,
            'url' => $this->url(),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'alt' => $this->alt,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
