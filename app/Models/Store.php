<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'seller_id', 'name', 'description', 'address',
        'latitude', 'longitude', 'radius_meter',
        'phone', 'logo', 'payment_qr',
        'operational_status', 'registration_status',
        'rejection_reason', 'rejection_category',
        'open_time', 'close_time', 'category', 'service_type',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius_meter' => 'integer',
            'rating' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($q)
    {
        return $q->where('registration_status', 'active');
    }

    public function scopePending($q)
    {
        return $q->where('registration_status', 'pending');
    }

    public function scopeOpen($q)
    {
        return $q->where('operational_status', 'open');
    }
}
