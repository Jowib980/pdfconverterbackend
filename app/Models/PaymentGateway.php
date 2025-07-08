<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    //

    protected $fillable = [
        'name',
        'is_enabled',
        'client_id',
        'client_secret'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

}
