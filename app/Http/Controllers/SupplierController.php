<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Check if user has UMKM access (admin or petugas)
     */
    private function checkUmkmAccess()
    {
        if (!in_array(Auth::user()->role, ['admin', 'petugas'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Only admin or petugas can perform this action.'
            ], 403);
        }

        if (!Auth::user()->umkm_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You need to be associated with a UMKM to access suppliers.'
            ], 400);
        }

        return null;
    }

    /**
     * Display a listing of suppliers.
     */
    public function index()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $suppliers = Supplier::where('umkm_id', Auth::user()->umkm_id)
                               ->orderBy('created_at', 'desc')
                               ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Suppliers retrieved successfully',
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve suppliers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'nama_supplier' => 'required|string|max:100',
            'alamat_supplier' => 'required|string',
            'kontak_supplier' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $supplier = Supplier::create([
                'nama_supplier' => $request->nama_supplier,
                'alamat_supplier' => $request->alamat_supplier,
                'kontak_supplier' => $request->kontak_supplier,
                'umkm_id' => Auth::user()->umkm_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier created successfully',
                'data' => $supplier
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified supplier.
     */
    public function show($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $supplier = Supplier::where('supplier_id', $id)
                              ->where('umkm_id', Auth::user()->umkm_id)
                              ->first();

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found or not authorized to view'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier retrieved successfully',
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, $id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        // 1. Mencari data
        $supplier = Supplier::where('supplier_id', $id)
                          ->where('umkm_id', Auth::user()->umkm_id)
                          ->first();

        if (!$supplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supplier not found or not authorized to update'
            ], 404);
        }

        // 2. Validator
        $validator = Validator::make($request->all(), [
            'nama_supplier' => 'required|string|max:100',
            'alamat_supplier' => 'required|string',
            'kontak_supplier' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Siapkan data yang ingin di update
        $data = [
            'nama_supplier' => $request->nama_supplier,
            'alamat_supplier' => $request->alamat_supplier,
            'kontak_supplier' => $request->kontak_supplier
        ];

        // 4. Update data baru ke database
        $supplier->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier updated successfully',
            'data' => $supplier
        ], 200);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $supplier = Supplier::where('supplier_id', $id)
                              ->where('umkm_id', Auth::user()->umkm_id)
                              ->first();

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found or not authorized to delete'
                ], 404);
            }

            $supplier->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
