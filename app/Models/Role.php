<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A named set of permissions for team members (users.role = 'staff'). */
class Role extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['permissions' => 'array'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function permissionList(): array
    {
        return Permissions::clean($this->permissions ?? []);
    }

    public function allows(string $key): bool
    {
        return in_array($key, $this->permissionList(), true);
    }
}
