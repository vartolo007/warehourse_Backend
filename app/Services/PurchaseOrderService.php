<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    protected $stockMovementService;
    protected $financialTransactionService;

    public function __construct(
        StockMovementService $stockMovementService,
        FinancialTransactionService $financialTransactionService
    ) {
        $this->stockMovementService = $stockMovementService;
        $this->financialTransactionService = $financialTransactionService;
    }

    /**
     * إنشاء فاتورة شراء بحالة pending مع أسطرها وحساب الإجماليات
     * (لا تدخل الكمية للمخزون إلا عند التأكيد/الاستلام)
     */
    public function create(array $data, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $total = collect($data['items'])
                ->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

            $discount = $data['discount'] ?? 0;
            $tax      = $data['tax'] ?? 0;

            $order = PurchaseOrder::create([
                'supplier_id'    => $data['supplier_id'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'purchase_date'  => $data['purchase_date'],
                'total'          => $total,
                'discount'       => $discount,
                'tax'            => $tax,
                'grand_total'    => $total - $discount + $tax,
                'status'         => 'pending',
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order->load('items.product:id,name,sku');
        });
    }

    /**
     * تأكيد الاستلام: إدخال الكميات للمخزون وتسجيل الدين للمورد
     */
    public function confirm(PurchaseOrder $order, int $userId): PurchaseOrder
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending orders can be confirmed. Current status: ' . $order->status,
            ]);
        }

        return DB::transaction(function () use ($order, $userId) {
            // إدخال الكميات للمخزون (حركة in لكل سطر)
            foreach ($order->items as $item) {
                $this->stockMovementService->moveIn([
                    'product_id'     => $item->product_id,
                    'quantity'       => $item->quantity,
                    'reference_type' => 'PurchaseOrder',
                    'reference_id'   => $order->id,
                    'notes'          => 'Purchase invoice ' . $order->invoice_number,
                ], $userId);
            }

            // تسجيل قيمة الفاتورة كدين علينا للمورد
            $this->financialTransactionService->record(
                $order->supplier,
                'purchase',
                (float) $order->grand_total,
                $order->invoice_number,
                'Purchase invoice confirmed',
                $userId
            );

            $order->update([
                'status'     => 'confirmed',
                'updated_by' => $userId,
            ]);

            return $order->load('items.product:id,name,sku,quantity');
        });
    }

    /**
     * إلغاء فاتورة معلقة (لا يمكن إلغاء فاتورة مؤكدة لأن المخزون تحرك)
     */
    public function cancel(PurchaseOrder $order, int $userId): PurchaseOrder
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending orders can be cancelled. Current status: ' . $order->status,
            ]);
        }

        $order->update([
            'status'     => 'cancelled',
            'updated_by' => $userId,
        ]);

        return $order;
    }

    // توليد رقم فاتورة شراء فريد مثل: PO-20260711-0001
    protected function generateInvoiceNumber(): string
    {
        $next = (PurchaseOrder::max('id') ?? 0) + 1;

        return 'PO-' . now()->format('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
