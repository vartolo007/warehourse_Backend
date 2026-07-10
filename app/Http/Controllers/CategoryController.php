<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    // عرض قائمة الأصناف مع عدد المنتجات في كل صنف
    public function index()
    {
        $categories = Category::withCount('products')->latest()->paginate(15);

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data'    => $categories,
        ], 200);
    }

    // إضافة صنف جديد
    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data'    => $category,
        ], 201);
    }

    // عرض تفاصيل صنف واحد مع منتجاته
    public function show($id)
    {
        $category = Category::with('products')->findOrFail($id);

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data'    => $category,
        ], 200);
    }

    // تعديل بيانات صنف
    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);

        $category->update($request->validated() + [
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'data'    => $category,
        ], 200);
    }

    // حذف صنف (للأدمن فقط - محمي من الراوت)
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }
}
