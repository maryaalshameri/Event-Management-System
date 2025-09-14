<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
use HasFactory, Notifiable;

protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'latitude',
        'longitude',
        'capacity',
        'category',
        'available_seats',
        'price',
        'image',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price' => 'decimal:2',
    ];

    // العلاقات
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // نطاقات الاستعلام (Scopes)
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('end_date', '<', now());
    }

    // طرق مساعدة
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isSoldOut(): bool
    {
        return $this->available_seats <= 0;
    }

    public function decreaseAvailableSeats(int $quantity): void
    {
        $this->available_seats -= $quantity;
        $this->save();
    }

    public function increaseAvailableSeats(int $quantity): void
    {
        $this->available_seats += $quantity;
        $this->save();
    }
}
