<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'address',
        'notes',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
