<?php

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    public const TRIAL_DAYS = 30;

    public const RECEIPT_FORMAT_THERMAL = 'thermal';

    public const RECEIPT_FORMAT_PDF = 'pdf';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'logo_path',
        'receipt_format',
        'show_product_images',
        'allow_price_edit',
        'tax_percent',
        'service_charge_percent',
        'is_active',
        'order_token',
        'trial_ends_at',
        'subscription_status',
        'subscription_ends_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $store) {
            $store->order_token ??= Str::random(16);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_product_images' => 'boolean',
            'allow_price_edit' => 'boolean',
            'tax_percent' => 'integer',
            'service_charge_percent' => 'integer',
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
     * The date access actually runs out: the subscription end date while
     * actively subscribed, otherwise the trial end date (even if past).
     */
    public function accessEndsAt(): ?Carbon
    {
        return $this->subscriptionActive() ? $this->subscription_ends_at : $this->trial_ends_at;
    }

    /**
     * Days left before access runs out, for either a trial or a paid
     * subscription - whichever currently applies. 0 once it has expired.
     */
    public function accessDaysLeft(): int
    {
        $endsAt = $this->accessEndsAt();

        if (! $endsAt || $endsAt->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($endsAt, false) + 1;
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

    /**
     * Whether the "trial ending soon" banner should still show: only while
     * genuinely on trial and not already covered by a paid subscription
     * (trial_ends_at is never cleared when a store subscribes early).
     */
    public function shouldShowTrialNotice(): bool
    {
        return $this->onTrial() && ! $this->subscriptionActive();
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

    public function employeeInvitations(): HasMany
    {
        return $this->hasMany(EmployeeInvitation::class);
    }

    public function selfOrders(): HasMany
    {
        return $this->hasMany(SelfOrder::class);
    }

    public function selfOrderUrl(): string
    {
        return route('self-order.menu', $this->order_token);
    }

    public function usesPdfReceipt(): bool
    {
        return $this->receipt_format === self::RECEIPT_FORMAT_PDF;
    }

    public function taxAmountFor(int $amount): int
    {
        return (int) round($amount * $this->tax_percent / 100);
    }

    public function serviceChargeAmountFor(int $amount): int
    {
        return (int) round($amount * $this->service_charge_percent / 100);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
