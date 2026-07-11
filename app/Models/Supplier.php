<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // الحركات المالية للمورد (فواتير شراء وسندات صرف)
    public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'financialable');
    }
}
