<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'table_number',
        'cashier_name',
        'order_type',
        'payment_method',
        'payment_reference',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'change_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'tax' => 'integer',
        'total' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
