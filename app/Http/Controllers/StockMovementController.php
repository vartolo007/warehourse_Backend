<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    protected $stockMovementService;

    public function __construct(StockMovementService $stockMovementService)
    {
        $this->stockMovementService = $stockMovementService;
    }

    // سجل حركات المخزون (وارد، صادر، جرد/تالف) مع فلاتر
    public function index(Request $request)
    {
        $movements = StockMovement::with(['product:id,name,sku', 'creator:id,first_name,last_name'])
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->from_date, fn ($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->latest()
            ->paginate(15);

        return response()->json([
            'message' => 'Stock movements retrieved successfully',
            'data'    => $movements,
        ], 200);
    }

    // إدخال بضاعة للمستودع (وارد)
    public function storeIn(StockInRequest $request)
    {
        $movement = $this->stockMovementService->moveIn(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Stock added successfully',
            'data'    => $movement->load('product:id,name,sku,quantity'),
        ], 201);
    }

    // إخراج بضاعة من المستودع (صادر أو جرد/تالف)
    public function storeOut(StockOutRequest $request)
    {
        $movement = $this->stockMovementService->moveOut(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'message' => 'Stock removed successfully',
            'data'    => $movement->load('product:id,name,sku,quantity'),
        ], 201);
    }
}
