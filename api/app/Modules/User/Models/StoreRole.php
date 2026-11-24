<?php

namespace App\Modules\User\Models;

use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A role a store owner defines for their staff, e.g. "Accountant" with orders.view.
 * permissions holds keys from App\Modules\User\Team\StorePermissions.
 */
class StoreRole extends Model
{
    protected $fillable = ['store_id', 'name', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_role_user');
    }
}
