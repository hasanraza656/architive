<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * One table for both account types. Admins sign in with a password,
 * customers with a one-time code sent to their e-mail (see App\Services\Auth\OtpService).
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CUSTOMER = 'customer';

    protected $fillable = [
        'role', 'first_name', 'last_name', 'email', 'phone', 'phone_country',
        'password', 'is_active', 'last_login_at', 'last_seen_at', 'email_verified_at', 'profile_completed_at', 'avatar', 'job_title', 'bio',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'profile_completed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /* ------------------------------------------------------------ Relations */

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /* ------------------------------------------------------------ Scopes */

    public function scopeCustomers($query)
    {
        return $query->where('role', self::ROLE_CUSTOMER);
    }

    /* ------------------------------------------------------------ Helpers */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        $i = mb_substr((string) $this->first_name, 0, 1) . mb_substr((string) $this->last_name, 0, 1);

        return mb_strtoupper($i ?: mb_substr((string) $this->email, 0, 1));
    }

    /** Public URL of the profile photo (null = show initials). */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset($this->avatar) : null;
    }

    /** Where this person lands after signing in. */
    public function homeRoute(): string
    {
        return $this->isAdmin() ? route('admin.dashboard') : route('customer.dashboard');
    }
}
