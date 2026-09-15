<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Database\Factories\EmployeeInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeInvitation extends Model
{
    /** @use HasFactory<EmployeeInvitationFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'invited_by',
        'email',
        'role',
        'permissions',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isPast();
    }
}
