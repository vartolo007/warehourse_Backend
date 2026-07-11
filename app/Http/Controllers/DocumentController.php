<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // رفع مستند (صورة فاتورة أو PDF) وربطه بفاتورة بيع أو شراء
    public function store(StoreDocumentRequest $request)
    {
        $data = $request->validated();

        // التأكد من وجود الفاتورة المستهدفة
        $documentable = $data['documentable_type'] === 'sales_order'
            ? SalesOrder::findOrFail($data['documentable_id'])
            : PurchaseOrder::findOrFail($data['documentable_id']);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = $documentable->documents()->create([
            'file_path'  => $path,
            'file_name'  => $file->getClientOriginalName(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data'    => $document,
        ], 201);
    }

    // تحميل المستند
    public function download($id)
    {
        $document = Document::findOrFail($id);

        if (! Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found on disk'], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    // حذف المستند (صلاحية الأدمن فقط) مع حذف الملف من التخزين
    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ], 200);
    }
}
