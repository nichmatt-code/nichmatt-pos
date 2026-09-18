<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'password_set_by_user', 'store_id', 'role', 'permissions', 'is_active', 'is_developer', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The one account that always has developer (cross-tenant) access.
     * Every other developer must be granted access by an existing one.
     */
    public const BOOTSTRAP_DEVELOPER_EMAIL = 'nicholas.official17@gmail.com';

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
            'permissions' => 'array',
            'is_active' => 'boolean',
            'is_developer' => 'boolean',
            'password_set_by_user' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if ($user->email === self::BOOTSTRAP_DEVELOPER_EMAIL) {
                $user->is_developer = true;
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isKasir(): bool
    {
        return $this->role === 'kasir';
    }

    /**
     * Whether the user may access the given area. Owners always can;
     * everyone else needs the matching permission checked by the owner.
     */
    public function hasPermission(Permission $permission): bool
    {
        return $this->isOwner() || in_array($permission->value, $this->permissions ?? [], true);
    }

    /**
     * Whether this account has platform-wide (cross-tenant) admin access,
     * separate from and unaffected by their role/permissions within their
     * own store.
     */
    public function isDeveloper(): bool
    {
        return (bool) $this->is_developer;
    }

    /**
     * Remember that this browser session has proven ownership of this
     * account (via password or Google), so it can be switched back to
     * later without authenticating again.
     */
    public function rememberAsLinkedAccount(): void
    {
        $ids = Session::get('linked_accounts', []);

        if (! in_array($this->id, $ids, true)) {
            $ids[] = $this->id;
        }

        Session::put('linked_accounts', array_slice($ids, -5));
    }

    /**
     * @return Collection<int, User>
     */
    public static function linkedAccounts(): Collection
    {
        return static::query()
            ->whereIn('id', Session::get('linked_accounts', []))
            ->where('is_active', true)
            ->get();
    }
}
