<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetails extends Model
{
    //

    protected $fillable = [
        'user_id',
        'payer_email',
        'payer_id',
        'payer_name',
        'plan_type',
        'plan_amount',
        'transaction_id',
        'transaction_status',
        'shipping_address',
        'payment_date',
        'currency',
        'raw_response',
        'gateway'
    ];

}
