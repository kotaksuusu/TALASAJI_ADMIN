<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'description', 'display_order', 'icon', 'is_active'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }
}
