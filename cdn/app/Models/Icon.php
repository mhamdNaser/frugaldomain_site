<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Icon extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'download_count',
        'tags',
        'is_active',
        'file_svg',
        'file_png'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
    ];

    public function files()
    {
        return $this->hasMany(IconFiles::class, 'icon_id');
    }
}
