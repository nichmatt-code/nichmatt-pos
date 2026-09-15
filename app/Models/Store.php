<?php

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    public const TRIAL_DAYS = 30;

    public const SUBSCRIPTION_MONTHLY_PRICE = 100_000;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'is_active',
        'trial_ends_at',
        'subscription_status',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(StoreSubscriptionPayment::class);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function subscriptionActive(): bool
    {
        return $this->subscription_status === 'active'
            && $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isFuture();
    }

    /**
     * Whether the store currently has access to the app: either still
     * within its free trial, or has an active paid subscription.
     */
    public function hasAccess(): bool
    {
        return $this->onTrial() || $this->subscriptionActive();
    }

    public function trialDaysLeft(): int
    {
        if (! $this->onTrial()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false) + 1;
    }

    /**
     * The date a new paid period should start from: extends the current
     * subscription if still active, otherwise starts from now.
     */
    public function nextSubscriptionPeriodStart(): Carbon
    {
        if ($this->subscriptionActive()) {
            return $this->subscription_ends_at;
        }

        return now();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
