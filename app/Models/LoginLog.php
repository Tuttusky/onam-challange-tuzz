<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $fillable = [
        'admin_id',
        'email',
        'ip',
        'user_agent',
        'success',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
