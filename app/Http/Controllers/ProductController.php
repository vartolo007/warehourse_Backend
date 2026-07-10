<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    protected $stockMovementService;

    public function __construct(StockMovementService $stockMovementService)
    {
        $this->stockMovementService = $stockMovementService;
    }

    // عرض قائمة المنتجات مع الصنف والكمية الحالية
    public function index(Request $request)
    {
        $products = Product::with('category')
            // بحث بالاسم أو الـ SKU أو الباركود
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%')
                      ->orWhere('barcode', 'like', '%' . $request->search . '%');
                });
            })
            // فلترة حسب الصنف أو الحالة
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            // فلترة المنتجات التي وصلت للحد الأدنى (?low_stock=1)
            ->when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('quantity', '<=', 'minimum_quantity'))
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Products retrieved successfully',
            'data'    => $products,
        ], 200);
    }

    // إضافة منتج جديد للمستودع
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();
        $openingQuantity = $validatedData['quantity'] ?? 0;
        // الكمية تبدأ من صفر وتضاف كحركة مخزون افتتاحية ليكون كل شيء موثقاً
        $validatedData['quantity'] = 0;

        $product = DB::transaction(function () use ($validatedData, $openingQuantity, $request) {
            $product = Product::create($validatedData + [
                'created_by' => $request->user()->id,
            ]);

            // تسجيل الكمية الافتتاحية كحركة "وارد" في سجل حركات المخزون
            if ($openingQuantity > 0) {
                $this->stockMovementService->moveIn([
                    'product_id' => $product->id,
                    'quantity'   => $openingQuantity,
                    'notes'      => 'Opening stock',
                ], $request->user()->id);
            }

            return $product;
        });

        return response()->json([
            'message' => 'Product created successfully',
            'data'    => $product->fresh('category'),
        ], 201);
    }

    // عرض تفاصيل منتج واحد مع آخر حركاته
    public function show($id)
    {
        $product = Product::with([
            'category',
            'movements' => fn ($q) => $q->with('creator:id,first_name,last_name')->latest()->limit(10),
        ])->findOrFail($id);

        return response()->json([
            'message'      => 'Product retrieved successfully',
            'data'         => $product,
            'is_low_stock' => $product->isLowStock(), // تنبيه إذا قارب على النفاذ
        ], 200);
    }

    // تعديل بيانات المنتج (بدون الكمية - الكمية تعدل فقط عبر حركات المخزون)
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update($request->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Product updated successfully',
            'data'    => $product->fresh('category'),
        ], 200);
    }

    // حذف منتج (للأدمن فقط - محمي من الراوت)
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }
}
