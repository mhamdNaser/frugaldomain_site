<?php

namespace App\Modules\User\Models;

// use Filament\Panel; if you are using Filament and want to implement access control for the admin panel, you can import the Panel class and implement the canAccessPanel method in your User model.

use App\Modules\Locale\Models\City;
use App\Modules\Locale\Models\Country;
use App\Modules\Locale\Models\State;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Team\StorePermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // users.id is an auto-increment bigint (see create_users_table) and every
    // foreign key to users is a bigint, so the model must not generate UUIDs.
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    // If you are using UUIDs, you should set the $incrementing property to false and the $keyType property to 'string':
    // public $incrementing = false;
    // protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'medium_name',
        'last_name',
        'password',
        'phone',
        'address_1',
        'address_2',
        'address_3',
        'country_id',
        'state_id',
        'city_id',
        'status',
        'email',
        'image',
        'store_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * The store this user works on: the one they own, or, for a staff member, their
     * employer's. Everything scoped by $user->store works for staff unchanged.
     */
    public function store()
    {
        return $this->store_id
            ? $this->belongsTo(Store::class, 'store_id')
            : $this->hasOne(Store::class, 'owner_id');
    }

    public function storeRoles()
    {
        return $this->belongsToMany(StoreRole::class, 'store_role_user');
    }

    /** A staff member added by a store owner (not the owner, not a platform admin). */
    public function isStoreStaff(): bool
    {
        return $this->store_id !== null;
    }

    /**
     * What this user may do in their store dashboard. Owners get every key; staff get
     * the union of their roles.
     *
     * @return array<int, string>
     */
    public function storePermissions(): array
    {
        if (!$this->isStoreStaff()) {
            return StorePermissions::allKeys();
        }

        return $this->storeRoles
            ->pluck('permissions')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    public function canInStore(string $permission): bool
    {
        return $permission === '' || in_array($permission, $this->storePermissions(), true);
    }

    public function hasStore(): bool
    {
        return $this->store()->exists();
    }

    // public function canAccessPanel(Panel $panel): bool
    // {
    //     // بما أن عندك Panel واحد اسمه admin:
    //     if ($panel->getId() === 'admin') {
    //         return $this->hasAnyRole(['admin', 'tenant']);
    //     }

    //     return false;
    // }

    // public function getFilamentName(): string
    // {
    //     $fullName = trim(($this->first_name ?? '') . ' ' . ($this->medium_name ?? '') . ' ' . ($this->last_name ?? ''));
    //     return $fullName !== '' ? $fullName : ($this->username ?? $this->email ?? 'User');
    // }
}
