<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialTransactionService
{
    /**
     * تسجيل حركة مالية على طرف (عميل أو مورد) مع حساب الرصيد بعد الحركة
     *
     * منطق الرصيد:
     * - sale (فاتورة بيع) تزيد دين العميل، receipt (سند قبض) ينقصه
     * - purchase (فاتورة شراء) تزيد ديننا للمورد، payment (سند صرف) ينقصه
     */
    public function record(Model $party, string $type, float $amount, ?string $referenceNumber, ?string $notes, int $userId): FinancialTransaction
    {
        return DB::transaction(function () use ($party, $type, $amount, $referenceNumber, $notes, $userId) {
            // آخر رصيد مسجل لهذا الطرف (مع قفل السطر لمنع تضارب حركتين بنفس اللحظة)
            $lastBalance = FinancialTransaction::where('financialable_type', get_class($party))
                ->where('financialable_id', $party->id)
                ->lockForUpdate()
                ->latest('id')
                ->value('balance_after') ?? 0;

            // الفواتير تزيد الرصيد (الدين)، والسندات تنقصه
            $newBalance = in_array($type, ['sale', 'purchase'])
                ? $lastBalance + $amount
                : $lastBalance - $amount;

            return FinancialTransaction::create([
                'financialable_type' => get_class($party),
                'financialable_id'   => $party->id,
                'type'               => $type,
                'amount'             => $amount,
                'balance_after'      => $newBalance,
                'reference_number'   => $referenceNumber,
                'notes'              => $notes,
                'created_by'         => $userId,
            ]);
        });
    }
}
