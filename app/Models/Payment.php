<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'charge_id',
        'status',
        'amount',
        'currency',
        'card_type',
        'card_last4',
        'auth_code',
        'token',
        'environment',
        'captured_at',
    ];
}
