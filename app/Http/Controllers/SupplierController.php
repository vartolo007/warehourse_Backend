<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
public function index()
    {
        $suppliers = Supplier::all();

        return response()->json([
            'status' => 'success',
            'data' => $suppliers
        ], 200);
    }

    // إضافة مورد جديد
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'         => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'required|string|max:20|unique:suppliers,phone',
            'email'        => 'nullable|email|unique:suppliers,email',
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'status'       => 'required|in:active,inactive',
        ]);

        // توثيق الموظف الذي قام بالإضافة (في حال استخدام Authentication)
        $validatedData['created_by'] = Auth::id();

        $supplier = Supplier::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة المورد بنجاح',
            'data' => $supplier
        ], 201); // تعديل رمز الحالة إلى 201 Created بدلاً من 21
    }

    // عرض تفاصيل مورد واحد
    public function show($id)
        {
            $supplier = Supplier::findOrFail($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المورد غير موجود'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $supplier
            ], 200);
        }

    // تعديل بيانات المورد
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        if (!$supplier) {
            return response()->json(['message' => 'المورد غير موجود'], 404);
        }

        $validatedData = $request->validate([
            'name'         => 'string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'string|max:20|unique:suppliers,phone,' . $id,
            'email'        => 'nullable|email|unique:suppliers,email,' . $id,
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'status'       => 'in:active,inactive',
        ]);

        // توثيق الموظف الذي قام بالتعديل
        $validatedData['updated_by'] = Auth::id();

        $supplier->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات المورد بنجاح',
            'data' => $supplier
        ], 200);
    }

public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if (!$supplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'المورد غير موجود'
            ], 404);
        }

        // تم حذف شرط فحص المنتجات اللي كان يسبب المشكلة
        $supplier->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف المورد بنجاح'
        ], 200);
    }
}
