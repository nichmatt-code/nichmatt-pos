<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public const SENDER_USER = 'user';

    public const SENDER_AI = 'ai';

    public const SENDER_DEVELOPER = 'developer';

    public const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'support_conversation_id',
        'sender_type',
        'sender_id',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class, 'support_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
