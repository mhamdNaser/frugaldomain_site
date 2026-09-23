<?php

namespace App\Modules\Component\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentFeature extends Model
{
    use HasFactory;

    protected $table = 'component_features';

    protected $fillable = ['component_id', 'label', 'label_ar', 'ordering'];

    protected $casts = ['ordering' => 'integer'];

    public function component()
    {
        return $this->belongsTo(Component::class, 'component_id');
    }
}
