<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    // عرض فواتير الشراء مع فلاتر (المورد، الحالة، التاريخ)
    public function index(Request $request)
    {
        $orders = PurchaseOrder::with('supplier:id,name,phone')
            ->when($request->supplier_id, fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn ($q) => $q->whereDate('purchase_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('purchase_date', '<=', $request->to_date))
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Purchase orders retrieved successfully',
            'data'    => $orders,
        ], 200);
    }

    // إنشاء فاتورة شراء جديدة (بحالة pending)
    public function store(StorePurchaseOrderRequest $request)
    {
        $order = $this->purchaseOrderService->create(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data'    => $order,
        ], 201);
    }

    // تفاصيل فاتورة شراء مع أسطرها ومستنداتها
    public function show($id)
    {
        $order = PurchaseOrder::with([
            'supplier:id,name,phone,email',
            'items.product:id,name,sku',
            'documents',
            'creator:id,first_name,last_name',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Purchase order retrieved successfully',
            'data'    => $order,
        ], 200);
    }

    // تأكيد الاستلام: إدخال الكميات للمخزون وتسجيل الدين للمورد
    public function confirm(Request $request, $id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);

        $order = $this->purchaseOrderService->confirm($order, $request->user()->id);

        return response()->json([
            'message' => 'Purchase order confirmed successfully',
            'data'    => $order,
        ], 200);
    }

    // إلغاء فاتورة معلقة
    public function cancel(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        $order = $this->purchaseOrderService->cancel($order, $request->user()->id);

        return response()->json([
            'message' => 'Purchase order cancelled successfully',
            'data'    => $order,
        ], 200);
    }

    // حذف نهائي (صلاحية الأدمن فقط)
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        $order->delete();

        return response()->json([
            'message' => 'Purchase order deleted successfully',
        ], 200);
    }
}
