<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    // الحركات المالية للعميل (فواتير بيع وسندات قبض)
    public function financialTransactions()
    {
        return $this->morphMany(FinancialTransaction::class, 'financialable');
    }
}
