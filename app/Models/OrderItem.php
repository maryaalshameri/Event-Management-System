<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class OrderItem extends Model
{
        use HasFactory, Notifiable;
         protected $fillable = [
        'order_id',
        'ticket_type_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // العلاقات
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // طرق مساعدة
    public function getTotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    public function isFullyUsed(): bool
    {
        return $this->tickets()->whereNotNull('used_at')->count() === $this->quantity;
    }

    public function getUsedTicketsCountAttribute(): int
    {
        return $this->tickets()->whereNotNull('used_at')->count();
    }

}
