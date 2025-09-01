<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $fillable =
    ['table_id', 'user_id', 'status', 'total_price', 'opened_at', 'closed_at'];

    protected $casts = [
        'total_price' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relation Table
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    // Relasi ke User (pelayan yang buka order)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class);
    }
}
