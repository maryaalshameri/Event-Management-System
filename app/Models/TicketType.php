<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TicketType extends Model
{

     use HasFactory, Notifiable;

    protected $fillable = [
        'event_id',
        'type',
        'price',
        'quantity',
        'sold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // العلاقات
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // طرق مساعدة
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->sold;
    }

    public function isSoldOut(): bool
    {
        return $this->sold >= $this->quantity;
    }

    public function increaseSold(int $quantity): void
    {
        $this->sold += $quantity;
        $this->save();
    }

    public function decreaseSold(int $quantity): void
    {
        $this->sold -= $quantity;
        $this->save();
    }

}
