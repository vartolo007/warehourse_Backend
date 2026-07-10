<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // هل وصل المنتج للحد الأدنى للتنبيه؟
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
