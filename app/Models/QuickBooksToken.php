<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickBooksToken extends Model
{
    protected $table = 'quickbooks_tokens';
    protected $fillable = [
        'realm_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
    ];
}
