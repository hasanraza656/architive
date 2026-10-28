<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    public const ROLE_STAFF = 'staff';

    protected $fillable = [
        'role', 'first_name', 'last_name', 'email', 'phone', 'phone_country',
        'password', 'is_active', 'last_login_at', 'last_seen_at', 'email_verified_at', 'profile_completed_at', 'avatar', 'job_title', 'bio', 'role_id', 'invited_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'profile_completed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'invited_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /* ------------------------------------------------------------ Relations */

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /** The permission set of a team member (users.role = staff). */
    public function teamRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * The same mailbox written differently: Gmail ignores dots and "+tags" (jo.hn+shop@gmail.com = john@gmail.com).
     * Other providers are left alone apart from upper / lower case.
     */
    public static function canonicalEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return $email;
        }
        [$local, $domain] = explode('@', $email, 2);
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            return str_replace('.', '', explode('+', $local, 2)[0]) . '@gmail.com';
        }

        return $local . '@' . $domain;
    }

    /** The account that owns this e-mail address (exact match first, then the same Gmail mailbox spelled differently). */
    public static function findByEmail(string $email): ?self
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        if ($exact = static::where('email', $email)->first()) {
            return $exact;
        }
        $canonical = static::canonicalEmail($email);
        if (! str_ends_with($canonical, '@gmail.com')) {
            return null;
        }

        return static::where(fn ($q) => $q->where('email', 'like', '%@gmail.com')->orWhere('email', 'like', '%@googlemail.com'))
            ->get()->first(fn (self $u) => static::canonicalEmail($u->email) === $canonical);
    }

    /* ------------------------------------------------------------ Scopes */

    public function scopeCustomers($query)
    {
        return $query->where('role', self::ROLE_CUSTOMER);
    }

    /** Super admins and team members (everyone who is not a customer). */
    public function scopeTeam($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_STAFF]);
    }

    /* ------------------------------------------------------------ Helpers */

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Team member with limited, role-based access. */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    /** Anyone on our side of the conversation: super admin or team member. */
    public function isTeam(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_STAFF;
    }

    /** Super admins can do everything; team members only what their role allows; customers nothing here. */
    public function hasPermission(string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (! $this->isStaff() || ! $this->is_active) {
            return false;
        }
        $this->permissionCache ??= $this->teamRole?->permissionList() ?? [];

        return in_array($key, $this->permissionCache, true);
    }

    /** True if they have at least one of the given permissions. */
    public function hasAnyPermission(string ...$keys): bool
    {
        foreach ($keys as $k) {
            if ($this->hasPermission($k)) {
                return true;
            }
        }

        return false;
    }

    private ?array $permissionCache = null;

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

    /** Tasks assigned to this person that are not done yet (sidebar badge). */
    public function openTaskCount(): int
    {
        try {
            return Task::open()->whereIn('tasks.id', TaskAssignee::where('user_id', $this->id)->select('task_id'))->count();
        } catch (\Throwable) {
            return 0;       // the tasks tables do not exist yet (the database update has not been run): the page must still open
        }
    }

    /** Public URL of the profile photo (null = show initials). */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? asset($this->avatar) : null;
    }

    /** Where this person lands after signing in. */
    public function homeRoute(): string
    {
        if ($this->isCustomer()) {
            return route('customer.dashboard');
        }
        if ($this->isAdmin() || $this->hasPermission('dashboard.view')) {
            return route('admin.dashboard');
        }

        return route('admin.tasks.mine');      // team members without a dashboard land on their own work
    }
}
