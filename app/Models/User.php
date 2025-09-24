<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , HasApiTokens;

 protected $fillable = [
        'name','email','password','role','phone','address','avatar'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];



    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
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
    public function scopeOrganizers($query)
    {
        return $query->where('role', 'organizer');
    }

    public function scopeAttendees($query)
    {
        return $query->where('role', 'attendee');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // طرق مساعدة
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }

    public function isAttendee(): bool
    {
        return $this->role === 'attendee';
    }
}
