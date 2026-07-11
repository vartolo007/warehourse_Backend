<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialTransactionRequest;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Supplier;
use App\Services\FinancialTransactionService;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller
{
    protected $financialTransactionService;

    public function __construct(FinancialTransactionService $financialTransactionService)
    {
        $this->financialTransactionService = $financialTransactionService;
    }

    // سجل كل الحركات المالية مع فلاتر (النوع، التاريخ)
    public function index(Request $request)
    {
        $transactions = FinancialTransaction::with(['financialable:id,name', 'creator:id,first_name,last_name'])
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->from_date, fn ($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Financial transactions retrieved successfully',
            'data'    => $transactions,
        ], 200);
    }

    // تسجيل سند قبض (من عميل) أو سند صرف (لمورد)
    public function store(StoreFinancialTransactionRequest $request)
    {
        $data = $request->validated();

        // سند القبض على عميل وسند الصرف لمورد فقط
        if ($data['party_type'] === 'customer' && $data['type'] !== 'receipt') {
            return response()->json(['message' => 'Only receipt vouchers are allowed for customers'], 422);
        }
        if ($data['party_type'] === 'supplier' && $data['type'] !== 'payment') {
            return response()->json(['message' => 'Only payment vouchers are allowed for suppliers'], 422);
        }

        $party = $data['party_type'] === 'customer'
            ? Customer::findOrFail($data['party_id'])
            : Supplier::findOrFail($data['party_id']);

        $transaction = $this->financialTransactionService->record(
            $party,
            $data['type'],
            (float) $data['amount'],
            $data['reference_number'] ?? null,
            $data['notes'] ?? null,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Financial transaction recorded successfully',
            'data'    => $transaction,
        ], 201);
    }

    // كشف حساب عميل: كل حركاته المالية + الرصيد الحالي (كم لنا عنده؟)
    public function customerStatement($id)
    {
        $customer = Customer::findOrFail($id);

        return $this->statement($customer);
    }

    // كشف حساب مورد: كل حركاته المالية + الرصيد الحالي (كم علينا له؟)
    public function supplierStatement($id)
    {
        $supplier = Supplier::findOrFail($id);

        return $this->statement($supplier);
    }

    // بناء كشف الحساب لأي طرف مالي
    protected function statement($party)
    {
        $transactions = $party->financialTransactions()
            ->with('creator:id,first_name,last_name')
            ->oldest('id')
            ->get();

        $currentBalance = $transactions->last()->balance_after ?? 0;

        return response()->json([
            'message' => 'Statement retrieved successfully',
            'data'    => [
                'party'           => $party->only(['id', 'name', 'phone', 'email']),
                'current_balance' => $currentBalance,
                'transactions'    => $transactions,
            ],
        ], 200);
    }
}
