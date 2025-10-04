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
        'name', // غيرت من type إلى name
        'price',
        'quantity',
        'available', // تأكد من وجوده
        'description', // تأكد من وجوده
        'sold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

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
        $this->available = $this->quantity - $this->sold; // تحديث available أيضاً
        $this->save();
    }

    public function decreaseSold(int $quantity): void
    {
        $this->sold -= $quantity;
        $this->available = $this->quantity - $this->sold; // تحديث available أيضاً
        $this->save();
    }
}