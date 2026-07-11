<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * لوحة القيادة (Dashboard) لمدير المستودع
     * إحصائيات اليوم + العدادات العامة + بيانات جاهزة للرسوم البيانية (Charts)
     */
    public function dashboard()
    {
        $today = now()->toDateString();

        // حركة المخزون اليوم (كم بضاعة دخلت؟ كم خرجت؟)
        $stockInToday = StockMovement::where('type', 'in')
            ->whereDate('created_at', $today)
            ->sum('quantity');

        $stockOutToday = StockMovement::where('type', 'out')
            ->whereDate('created_at', $today)
            ->sum('quantity');

        // مبيعات ومشتريات اليوم (الفواتير المؤكدة فقط)
        $salesToday = SalesOrder::where('status', 'confirmed')
            ->whereDate('sale_date', $today)
            ->sum('grand_total');

        $purchasesToday = PurchaseOrder::where('status', 'confirmed')
            ->whereDate('purchase_date', $today)
            ->sum('grand_total');

        // فواتير بانتظار التأكيد (تحتاج انتباه المدير)
        $pendingSalesOrders    = SalesOrder::where('status', 'pending')->count();
        $pendingPurchaseOrders = PurchaseOrder::where('status', 'pending')->count();

        // منتجات وصلت للحد الأدنى (تنبيه إعادة الطلب)
        $lowStockCount = Product::whereColumn('quantity', '<=', 'minimum_quantity')->count();

        // بيانات Chart: حركة المخزون آخر 7 أيام (وارد مقابل صادر)
        $movementsChart = StockMovement::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['type', 'quantity', 'created_at'])
            ->groupBy(fn ($m) => $m->created_at->toDateString())
            ->map(fn ($day, $date) => [
                'date' => $date,
                'in'   => $day->where('type', 'in')->sum('quantity'),
                'out'  => $day->where('type', 'out')->sum('quantity'),
            ])->values();

        // بيانات Chart: المبيعات مقابل المشتريات آخر 6 أشهر
        $salesByMonth = SalesOrder::where('status', 'confirmed')
            ->where('sale_date', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['sale_date', 'grand_total'])
            ->groupBy(fn ($o) => \Carbon\Carbon::parse($o->sale_date)->format('Y-m'));

        $purchasesByMonth = PurchaseOrder::where('status', 'confirmed')
            ->where('purchase_date', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['purchase_date', 'grand_total'])
            ->groupBy(fn ($o) => \Carbon\Carbon::parse($o->purchase_date)->format('Y-m'));

        $ordersChart = collect(range(5, 0))->map(function ($i) use ($salesByMonth, $purchasesByMonth) {
            $month = now()->subMonths($i)->format('Y-m');

            return [
                'month'     => $month,
                'sales'     => (float) ($salesByMonth->get($month)?->sum('grand_total') ?? 0),
                'purchases' => (float) ($purchasesByMonth->get($month)?->sum('grand_total') ?? 0),
            ];
        });

        return response()->json([
            'message' => 'Dashboard retrieved successfully',
            'data'    => [
                'today' => [
                    'stock_in'   => (int) $stockInToday,
                    'stock_out'  => (int) $stockOutToday,
                    'sales'      => (float) $salesToday,
                    'purchases'  => (float) $purchasesToday,
                ],
                'pending' => [
                    'sales_orders'    => $pendingSalesOrders,
                    'purchase_orders' => $pendingPurchaseOrders,
                ],
                'low_stock_products' => $lowStockCount,
                'totals' => [
                    'products'   => Product::count(),
                    'categories' => Category::count(),
                    'customers'  => Customer::count(),
                    'suppliers'  => Supplier::count(),
                ],
                'charts' => [
                    'stock_movements_last_7_days'   => $movementsChart,
                    'sales_vs_purchases_last_6_months' => $ordersChart,
                ],
            ],
        ], 200);
    }

    /**
     * تقرير جرد المخزون: كل المنتجات بكمياتها الحالية مع فلاتر
     */
    public function inventory(Request $request)
    {
        $products = Product::with('category:id,name')
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'sku', 'quantity', 'minimum_quantity', 'status'])
            ->map(function ($product) {
                $product->is_low_stock = $product->isLowStock();
                return $product;
            });

        return response()->json([
            'message' => 'Inventory report retrieved successfully',
            'data'    => [
                'total_products' => $products->count(),
                'total_quantity' => $products->sum('quantity'),
                'low_stock_count' => $products->where('is_low_stock', true)->count(),
                'products'       => $products,
            ],
        ], 200);
    }

    /**
     * تقرير المنتجات تحت الحد الأدنى (تحتاج إعادة طلب)
     */
    public function lowStock()
    {
        $products = Product::with('category:id,name')
            ->whereColumn('quantity', '<=', 'minimum_quantity')
            ->orderBy('quantity')
            ->get(['id', 'category_id', 'name', 'sku', 'quantity', 'minimum_quantity']);

        return response()->json([
            'message' => 'Low stock report retrieved successfully',
            'data'    => $products,
        ], 200);
    }

    /**
     * تقرير الأصناف الراكدة: منتجات لم يخرج منها شيء منذ X يوم (افتراضياً 30)
     */
    public function stagnantProducts(Request $request)
    {
        $days  = (int) ($request->days ?? 30);
        $since = now()->subDays($days);

        $products = Product::with('category:id,name')
            ->where('quantity', '>', 0) // الراكد هو الموجود بالمستودع ولا يتحرك
            ->whereDoesntHave('movements', fn ($q) => $q->where('type', 'out')->where('created_at', '>=', $since))
            ->orderByDesc('quantity')
            ->get(['id', 'category_id', 'name', 'sku', 'quantity']);

        return response()->json([
            'message' => 'Stagnant products report retrieved successfully',
            'data'    => [
                'days'     => $days,
                'count'    => $products->count(),
                'products' => $products,
            ],
        ], 200);
    }

    /**
     * ملخص حركة المخزون لكل منتج خلال فترة (إجمالي الوارد والصادر)
     */
    public function stockMovementsSummary(Request $request)
    {
        $summary = StockMovement::selectRaw("
                product_id,
                SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as total_out,
                SUM(CASE WHEN type = 'adjustment' THEN quantity ELSE 0 END) as total_adjustment,
                COUNT(*) as movements_count
            ")
            ->when($request->from_date, fn ($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->groupBy('product_id')
            ->with('product:id,name,sku,quantity')
            ->get();

        return response()->json([
            'message' => 'Stock movements summary retrieved successfully',
            'data'    => $summary,
        ], 200);
    }

    /**
     * تقرير أداء الموردين: عدد فواتير الشراء المؤكدة وإجمالي التعامل وآخر توريد
     */
    public function suppliersPerformance()
    {
        $suppliers = Supplier::withCount([
                'purchaseOrders as confirmed_orders_count' => fn ($q) => $q->where('status', 'confirmed'),
            ])
            ->withSum([
                'purchaseOrders as total_purchases' => fn ($q) => $q->where('status', 'confirmed'),
            ], 'grand_total')
            ->withMax([
                'purchaseOrders as last_purchase_date' => fn ($q) => $q->where('status', 'confirmed'),
            ], 'purchase_date')
            ->orderByDesc('total_purchases')
            ->get(['id', 'name', 'company_name', 'phone', 'status']);

        return response()->json([
            'message' => 'Suppliers performance report retrieved successfully',
            'data'    => $suppliers,
        ], 200);
    }

    /**
     * تقرير المنتجات الأكثر مبيعاً خلال فترة (من الفواتير المؤكدة)
     */
    public function topSellingProducts(Request $request)
    {
        $limit = (int) ($request->limit ?? 10);

        $items = SalesOrderItem::selectRaw('product_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
            ->whereHas('salesOrder', function ($q) use ($request) {
                $q->where('status', 'confirmed')
                    ->when($request->from_date, fn ($qq) => $qq->whereDate('sale_date', '>=', $request->from_date))
                    ->when($request->to_date, fn ($qq) => $qq->whereDate('sale_date', '<=', $request->to_date));
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->with('product:id,name,sku,quantity')
            ->get();

        return response()->json([
            'message' => 'Top selling products report retrieved successfully',
            'data'    => $items,
        ], 200);
    }
}
