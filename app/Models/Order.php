<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough; //

class Order extends Model
{
    use HasFactory, Notifiable;
     protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];


     public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Ticket::class, 
            OrderItem::class,
            'order_id', // Foreign key on OrderItem table
            'order_item_id', // Foreign key on Ticket table  
            'id', // Local key on Order table
            'id' // Local key on OrderItem table
        );
    }

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // public function tickets(): HasManyThrough
    // {
    //     return $this->hasManyThrough(Ticket::class, OrderItem::class);
    // }

    // نطاقات الاستعلام (Scopes)
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePendingPayment($query)
    {
        return $query->where('payment_status', 'pending');
    }

    // طرق مساعدة
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'completed';
    }

    public function markAsPaid(): void
    {
        $this->payment_status = 'completed';
        $this->status = 'confirmed';
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->payment_status = 'refunded';
        $this->save();
    }

}
