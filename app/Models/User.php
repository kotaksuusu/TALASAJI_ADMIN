<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone_number', 'avatar_url'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function setPasswordAttribute($value): void
    {
        if (!str_starts_with($value, '$2y$') && !str_starts_with($value, '$2a$')) {
            $value = Hash::make($value);
        }
        $this->attributes['password'] = $value;
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
