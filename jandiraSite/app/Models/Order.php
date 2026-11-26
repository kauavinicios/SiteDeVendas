<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'channel',
        'payment_method',
        'payment_status',
        'payment_reference',
        'total',
        'total_cost',
        'items_count',
        'notes',
        'stock_deducted_at',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'items_count' => 'integer',
            'stock_deducted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateCode(): string
    {
        $prefix = 'BJ-'.now()->format('Ymd').'-';

        do {
            $code = $prefix.Str::upper(Str::random(5));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function estimatedProfit(): ?float
    {
        if ($this->total_cost === null) {
            return null;
        }

        return round((float) $this->total - (float) $this->total_cost, 2);
    }
}
