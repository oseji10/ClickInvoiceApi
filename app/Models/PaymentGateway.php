<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaymentGateway extends Model
{
    protected $table = 'payment_gateways';

    protected $primaryKey = 'gatewayId';

    protected $fillable = [
        'paymentGatewayName',
        'url',
    ];

}
