<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FriendAvatar extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function friendMedia(): HasMany
    {
        return $this->hasMany(FriendMedia::class);
    }
}
