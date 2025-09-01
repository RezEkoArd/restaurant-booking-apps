<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $fillable = ['order_id', 'menu_id', 'quantity', 'price', 'subtotal'];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal: 2',
        'subtotal' => 'decimal:2',
    ];

    // Relationship
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
