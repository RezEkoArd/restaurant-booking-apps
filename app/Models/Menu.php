<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    //
    protected $fillable = [
        'name',
        'price',
        'category'
    ];

    /**
     * Scope a query to only include menus of a given category.
     */

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }


    /**
     * RelationShip Table
     */

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class);
    }
}
