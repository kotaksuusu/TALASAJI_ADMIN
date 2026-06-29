<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnerMenu extends Model
{
    protected $attributes = [
        'is_recommended' => false,
    ];

    protected $fillable = [
        'user_id', 'owner_category_id', 'name', 'description',
        'price', 'image', 'is_recommended', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_recommended' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownerCategory(): BelongsTo
    {
        return $this->belongsTo(OwnerCategory::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'owner_menu_id');
    }
}
