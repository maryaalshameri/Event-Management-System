<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
        use HasFactory, Notifiable;
         protected $fillable = [
        'user_id',
        'event_id',
        'rating',
        'comment',
    ];

    // العلاقات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // نطاقات الاستعلام (Scopes)
    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRated($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    public function scopeWithComments($query)
    {
        return $query->whereNotNull('comment');
    }

    // طرق مساعدة
    public function hasComment(): bool
    {
        return $this->comment !== null;
    }

    public function getRatingStarsAttribute(): string
    {
        return str_repeat('⭐', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}
