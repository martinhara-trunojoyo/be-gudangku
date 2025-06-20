<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kategori;
use App\Services\StockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
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
                'message' => 'You need to be associated with a UMKM to access products.'
            ], 400);
        }

        return null;
    }

    /**
     * Display a listing of products.
     */
    public function index()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $products = Barang::with('kategori')
                            ->where('umkm_id', Auth::user()->umkm_id)
                            ->orderBy('created_at', 'desc')
                            ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Products retrieved successfully',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:100',
            'kategori_id' => 'required|exists:kategori,kategori_id',
            'satuan' => 'required|string|max:45',
            'stok' => 'required|integer|min:0',
            'batas_minimum' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify that the kategori belongs to the same UMKM
        $kategori = Kategori::where('kategori_id', $request->kategori_id)
                          ->where('umkm_id', Auth::user()->umkm_id)
                          ->first();

        if (!$kategori) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found or does not belong to your UMKM'
            ], 404);
        }

        // Check if product name already exists for this UMKM
        $existingProduct = Barang::where('umkm_id', Auth::user()->umkm_id)
                                ->where('nama_barang', $request->nama_barang)
                                ->first();

        if ($existingProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product name already exists in your UMKM'
            ], 409);
        }

        try {
            $product = Barang::create([
                'nama_barang' => $request->nama_barang,
                'kategori_id' => $request->kategori_id,
                'satuan' => $request->satuan,
                'stok' => $request->stok,
                'umkm_id' => Auth::user()->umkm_id,
                'batas_minimum' => $request->batas_minimum ?? 0
            ]);

            // Load the relationship
            $product->load('kategori');

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $product = Barang::with('kategori')
                           ->where('barang_id', $id)
                           ->where('umkm_id', Auth::user()->umkm_id)
                           ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found or not authorized to view'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        // 1. Mencari data
        $product = Barang::where('barang_id', $id)
                       ->where('umkm_id', Auth::user()->umkm_id)
                       ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found or not authorized to update'
            ], 404);
        }

        // 2. Validator
        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:100',
            'kategori_id' => 'required|exists:kategori,kategori_id',
            'satuan' => 'required|string|max:45',
            'stok' => 'required|integer|min:0',
            'batas_minimum' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify that the kategori belongs to the same UMKM
        $kategori = Kategori::where('kategori_id', $request->kategori_id)
                          ->where('umkm_id', Auth::user()->umkm_id)
                          ->first();

        if (!$kategori) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found or does not belong to your UMKM'
            ], 404);
        }

        // Check if new product name already exists (except current product)
        $existingProduct = Barang::where('umkm_id', Auth::user()->umkm_id)
                                ->where('nama_barang', $request->nama_barang)
                                ->where('barang_id', '!=', $id)
                                ->first();

        if ($existingProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product name already exists in your UMKM'
            ], 409);
        }

        // Store old stock for notification
        $oldStock = $product->stok;

        // 3. Siapkan data yang ingin di update
        $data = [
            'nama_barang' => $request->nama_barang,
            'kategori_id' => $request->kategori_id,
            'satuan' => $request->satuan,
            'stok' => $request->stok,
            'batas_minimum' => $request->batas_minimum ?? 0
        ];

        // 4. Update data baru ke database
        $product->update($data);
        $product->load('kategori');

        // Check for low stock notifications
        NotifikasiStokController::checkLowStock($product->barang_id);

        // Send email notification if stock was changed
        if ($request->stok != $oldStock) {
            $stockDifference = $request->stok - $oldStock;
            $type = $stockDifference > 0 ? 'increase' : 'decrease';
            $quantity = abs($stockDifference);
            $reason = "Penyesuaian stok manual oleh " . Auth::user()->name;

            StockNotificationService::sendStockChangeNotification(
                $product->barang_id,
                $type,
                $quantity,
                $reason,
                $oldStock
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ], 200);
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $product = Barang::where('barang_id', $id)
                           ->where('umkm_id', Auth::user()->umkm_id)
                           ->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found or not authorized to delete'
                ], 404);
            }

            // Check if product has associated transactions
            $hasTransactions = $product->barangMasuk()->exists() || $product->barangKeluar()->exists();
            if ($hasTransactions) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete product. It has associated transactions.'
                ], 409);
            }

            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
