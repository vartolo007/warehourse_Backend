<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    // عرض قائمة العملاء
    public function index()
    {
        $customers = Customer::all();

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ], 200);
    }

    // إضافة عميل جديد
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'         => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone'        => 'required|string|max:20|unique:customers,phone', // تم التعديل لجدول customers
            'email'        => 'nullable|email|unique:customers,email',       // تم التعديل لجدول customers
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'status'       => 'required|in:active,inactive',
        ]);

        // توثيق الموظف الذي قام بالإضافة
        $validatedData['created_by'] = Auth::id();

        $customer = Customer::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة العميل بنجاح',
            'data' => $customer
        ], 201);
    }

    // عرض تفاصيل عميل واحد
    public function show($id)
    {
        // استخدام find بدلاً من findOrFail لكي يعمل الشرط أدناه ويرجع JSON
        $customer = Customer::findOrFail($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'العميل غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $customer
        ], 200);
    }

    // تعديل بيانات العميل
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'العميل غير موجود'
            ], 404);
        }

        $validatedData = $request->validate([
            'name'         => 'string|max:255',
            'phone'        => 'string|max:20|unique:customers,phone,' . $id,
            'email'        => 'nullable|email|unique:customers,email,' . $id,
            'address'      => 'nullable|string',
            'city'         => 'nullable|string|max:255',
            'country'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'status'       => 'in:active,inactive',
        ]);

        // توثيق الموظف الذي قام بالتعديل
        $validatedData['updated_by'] = Auth::id();

        $customer->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث بيانات العميل بنجاح',
            'data' => $customer
        ], 200);
    }

    // حذف العميل
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'العميل غير موجود'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف العميل بنجاح'
        ], 200);
    }
}
