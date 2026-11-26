<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'subject',
        'title',
        'body',
        'cta_label',
        'cta_url',
        'status',
        'recipients_count',
        'sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recipients_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Clientes elegíveis para campanhas de novidades (ativos, verificados e com opt-in LGPD).
     *
     * @return Builder<User>
     */
    public static function eligibleRecipientsQuery(): Builder
    {
        return User::query()
            ->where('role', UserRole::Customer->value)
            ->where('active', true)
            ->where('marketing_opt_in', true)
            ->whereNotNull('email_verified_at');
    }
}
