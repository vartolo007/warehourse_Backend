<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesOrderService
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
     * إنشاء فاتورة بيع بحالة pending مع أسطرها وحساب الإجماليات
     * (لا تتحرك الكمية من المخزون إلا عند التأكيد)
     */
    public function create(array $data, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $total = collect($data['items'])
                ->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

            $discount = $data['discount'] ?? 0;
            $tax      = $data['tax'] ?? 0;

            $order = SalesOrder::create([
                'customer_id'    => $data['customer_id'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'sale_date'      => $data['sale_date'],
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
     * تأكيد الفاتورة: صرف الكميات من المخزون وتسجيل الدين على العميل
     */
    public function confirm(SalesOrder $order, int $userId): SalesOrder
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending orders can be confirmed. Current status: ' . $order->status,
            ]);
        }

        return DB::transaction(function () use ($order, $userId) {
            // التحقق من توفر كل الكميات قبل تنفيذ أي صرف
            foreach ($order->items as $item) {
                $product = Product::findOrFail($item->product_id);
                if ($product->quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for product ({$product->name}). Available: {$product->quantity}, required: {$item->quantity}",
                    ]);
                }
            }

            // صرف الكميات من المخزون (حركة out لكل سطر)
            foreach ($order->items as $item) {
                $this->stockMovementService->moveOut([
                    'product_id'     => $item->product_id,
                    'type'           => 'out',
                    'quantity'       => $item->quantity,
                    'reference_type' => 'SalesOrder',
                    'reference_id'   => $order->id,
                    'notes'          => 'Sales invoice ' . $order->invoice_number,
                ], $userId);
            }

            // تسجيل قيمة الفاتورة كدين على العميل
            $this->financialTransactionService->record(
                $order->customer,
                'sale',
                (float) $order->grand_total,
                $order->invoice_number,
                'Sales invoice confirmed',
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
    public function cancel(SalesOrder $order, int $userId): SalesOrder
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

    // توليد رقم فاتورة بيع فريد مثل: SO-20260711-0001
    protected function generateInvoiceNumber(): string
    {
        $next = (SalesOrder::max('id') ?? 0) + 1;

        return 'SO-' . now()->format('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
