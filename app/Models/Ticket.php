<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
        use HasFactory, Notifiable;

        protected $fillable = [
        'order_item_id',
        'code',
        'pdf_path',
        'qr_path',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    // العلاقات
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // العلاقة من خلال orderItem إلى order
    public function order()
    {
        return $this->hasOneThrough(Order::class, OrderItem::class);
    }

    // العلاقة من خلال orderItem إلى ticketType
    public function ticketType()
    {
        return $this->hasOneThrough(TicketType::class, OrderItem::class);
    }

    // العلاقة من خلال order إلى event
    public function event()
    {
        return $this->hasOneThrough(Event::class, OrderItem::class, 'id', 'id', 'order_item_id', 'order_id')
            ->through('order');
    }

    // نطاقات الاستعلام (Scopes)
    public function scopeUsed($query)
    {
        return $query->whereNotNull('used_at');
    }

    public function scopeNotUsed($query)
    {
        return $query->whereNull('used_at');
    }

    // طرق مساعدة
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function markAsUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }

    public function markAsUnused(): void
    {
        $this->used_at = null;
        $this->save();
    }

}
