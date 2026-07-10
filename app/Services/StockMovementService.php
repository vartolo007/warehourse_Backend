<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * إدخال بضاعة للمستودع (وارد)
     * يزيد كمية المنتج ويسجل حركة من نوع in
     */
    public function moveIn(array $data, int $userId): StockMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            // lockForUpdate يقفل السطر أثناء العملية حتى لا تتضارب حركتان بنفس اللحظة
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            $newQuantity = $product->quantity + $data['quantity'];

            $product->update([
                'quantity'   => $newQuantity,
                'updated_by' => $userId,
            ]);

            return StockMovement::create([
                'product_id'     => $product->id,
                'type'           => 'in',
                'quantity'       => $data['quantity'],
                'balance_after'  => $newQuantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $userId,
            ]);
        });
    }

    /**
     * إخراج بضاعة من المستودع (صادر أو جرد/تالف)
     * يتحقق من توفر الكمية ثم ينقصها ويسجل حركة من نوع out أو adjustment
     */
    public function moveOut(array $data, int $userId): StockMovement
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            // التحقق من توفر الكمية قبل الإخراج
            if ($product->quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock. Available quantity: ' . $product->quantity,
                ]);
            }

            $newQuantity = $product->quantity - $data['quantity'];

            $product->update([
                'quantity'   => $newQuantity,
                'updated_by' => $userId,
            ]);

            return StockMovement::create([
                'product_id'     => $product->id,
                'type'           => $data['type'] ?? 'out',
                'quantity'       => $data['quantity'],
                'balance_after'  => $newQuantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'created_by'     => $userId,
            ]);
        });
    }
}
