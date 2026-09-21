<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One support chat thread between a store user and the AI assistant /
 * developer team. Deliberately NOT store-scoped (no BelongsToStore): the
 * user side always filters by user_id, and the developer inbox needs to
 * see every tenant's conversations.
 */
class SupportConversation extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'store_id',
        'status',
        'developer_handling',
        'unread_by_developer',
        'unread_by_user',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'developer_handling' => 'boolean',
            'unread_by_developer' => 'boolean',
            'unread_by_user' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
