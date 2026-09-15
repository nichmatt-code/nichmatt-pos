<?php

namespace App\Models\Concerns;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToStore
{
    /**
     * Boot the trait and automatically scope queries to the authenticated user's store.
     */
    protected static function bootBelongsToStore(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            if (Auth::check() && Auth::user()->store_id) {
                $builder->where($builder->getModel()->getTable().'.store_id', Auth::user()->store_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->store_id && Auth::check() && Auth::user()->store_id) {
                $model->store_id = Auth::user()->store_id;
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
