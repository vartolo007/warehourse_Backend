<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesOrderRequest;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    protected $salesOrderService;

    public function __construct(SalesOrderService $salesOrderService)
    {
        $this->salesOrderService = $salesOrderService;
    }

    // عرض فواتير البيع مع فلاتر (العميل، الحالة، التاريخ)
    public function index(Request $request)
    {
        $orders = SalesOrder::with('customer:id,name,phone')
            ->when($request->customer_id, fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn ($q) => $q->whereDate('sale_date', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('sale_date', '<=', $request->to_date))
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Sales orders retrieved successfully',
            'data'    => $orders,
        ], 200);
    }

    // إنشاء فاتورة بيع جديدة (بحالة pending)
    public function store(StoreSalesOrderRequest $request)
    {
        $order = $this->salesOrderService->create(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Sales order created successfully',
            'data'    => $order,
        ], 201);
    }

    // تفاصيل فاتورة بيع مع أسطرها ومستنداتها
    public function show($id)
    {
        $order = SalesOrder::with([
            'customer:id,name,phone,email',
            'items.product:id,name,sku',
            'documents',
            'creator:id,first_name,last_name',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Sales order retrieved successfully',
            'data'    => $order,
        ], 200);
    }

    // تأكيد الفاتورة: صرف الكميات من المخزون وتسجيل الدين على العميل
    public function confirm(Request $request, $id)
    {
        $order = SalesOrder::with('items')->findOrFail($id);

        $order = $this->salesOrderService->confirm($order, $request->user()->id);

        return response()->json([
            'message' => 'Sales order confirmed successfully',
            'data'    => $order,
        ], 200);
    }

    // إلغاء فاتورة معلقة
    public function cancel(Request $request, $id)
    {
        $order = SalesOrder::findOrFail($id);

        $order = $this->salesOrderService->cancel($order, $request->user()->id);

        return response()->json([
            'message' => 'Sales order cancelled successfully',
            'data'    => $order,
        ], 200);
    }

    // حذف نهائي (صلاحية الأدمن فقط)
    public function destroy($id)
    {
        $order = SalesOrder::findOrFail($id);

        $order->delete();

        return response()->json([
            'message' => 'Sales order deleted successfully',
        ], 200);
    }
}
