<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestStaff;
use App\Services\StaffService;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    protected $staffService;

    public function __construct(StaffService $staffService)
    {
        $this->staffService = $staffService;
    }
    public function storeStaff(RequestStaff $request)
{
    $validatedData = $request->validated();
    $user = $this->staffService->createStaff($validatedData, $request->role);
    return response()->json([
        'message' => 'Staff member created successfully',
        'data'    => $user
    ], 201);
}
}
