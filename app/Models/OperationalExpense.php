<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'description',
        'amount',
        'spent_at',
        'vendor',
    ];

    protected $casts = [
        'amount' => 'integer',
        'spent_at' => 'date',
    ];
}
