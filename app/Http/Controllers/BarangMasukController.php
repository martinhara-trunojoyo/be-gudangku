<?php

namespace App\Http\Controllers;

use App\Models\BarangMasuk;
use App\Models\Barang;
use App\Models\Supplier;
use App\Services\StockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BarangMasukController extends Controller
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
                'message' => 'You need to be associated with a UMKM to access stock transactions.'
            ], 400);
        }

        return null;
    }

    /**
     * Display a listing of barang masuk.
     */
    public function index()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $barangMasuk = BarangMasuk::with(['barang', 'supplier', 'user'])
                                    ->whereHas('barang', function($query) {
                                        $query->where('umkm_id', Auth::user()->umkm_id);
                                    })
                                    ->orderBy('created_at', 'desc')
                                    ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Incoming stock retrieved successfully',
                'data' => $barangMasuk
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve incoming stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created barang masuk.
     */
    public function store(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'jumlah_masuk' => 'required|integer|min:1',
            'tanggal_masuk' => 'required|date',
            'supplier_id' => 'required|exists:supplier,supplier_id',
            'barang_id' => 'required|exists:barang,barang_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Verify that the barang belongs to the same UMKM
            $barang = Barang::where('barang_id', $request->barang_id)
                           ->where('umkm_id', Auth::user()->umkm_id)
                           ->first();

            if (!$barang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found or does not belong to your UMKM'
                ], 404);
            }

            // Verify that the supplier belongs to the same UMKM
            $supplier = Supplier::where('supplier_id', $request->supplier_id)
                              ->where('umkm_id', Auth::user()->umkm_id)
                              ->first();

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found or does not belong to your UMKM'
                ], 404);
            }

            // Store old stock for notification
            $oldStock = $barang->stok;

            // Create barang masuk record
            $barangMasuk = BarangMasuk::create([
                'jumlah_masuk' => $request->jumlah_masuk,
                'tanggal_masuk' => $request->tanggal_masuk,
                'supplier_id' => $request->supplier_id,
                'barang_id' => $request->barang_id,
                'user_id' => Auth::id()
            ]);

            // Update stock - increase stock
            $barang->stok += $request->jumlah_masuk;
            $barang->save();

            // Check for low stock notifications
            NotifikasiStokController::checkLowStock($barang->barang_id);

            // Send email notification
            $reason = "Barang masuk dari supplier: {$supplier->nama_supplier}";
            StockNotificationService::sendStockChangeNotification(
                $barang->barang_id,
                'increase',
                $request->jumlah_masuk,
                $reason,
                $oldStock
            );

            DB::commit();

            // Load relationships
            $barangMasuk->load(['barang', 'supplier', 'user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Incoming stock recorded successfully',
                'data' => $barangMasuk
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record incoming stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified barang masuk.
     */
    public function show($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $barangMasuk = BarangMasuk::with(['barang', 'supplier', 'user'])
                                    ->where('masuk_id', $id)
                                    ->whereHas('barang', function($query) {
                                        $query->where('umkm_id', Auth::user()->umkm_id);
                                    })
                                    ->first();

            if (!$barangMasuk) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Incoming stock record not found or not authorized to view'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Incoming stock record retrieved successfully',
                'data' => $barangMasuk
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve incoming stock record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified barang masuk.
     */
    public function destroy($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            DB::beginTransaction();

            $barangMasuk = BarangMasuk::where('masuk_id', $id)
                                    ->whereHas('barang', function($query) {
                                        $query->where('umkm_id', Auth::user()->umkm_id);
                                    })
                                    ->first();

            if (!$barangMasuk) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Incoming stock record not found or not authorized to delete'
                ], 404);
            }

            // Get the barang
            $barang = Barang::find($barangMasuk->barang_id);

            // Check if we can reduce the stock
            if ($barang->stok < $barangMasuk->jumlah_masuk) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete this record. Current stock is less than the incoming amount.'
                ], 409);
            }

            // Store old stock for notification
            $oldStock = $barang->stok;

            // Reduce stock
            $barang->stok -= $barangMasuk->jumlah_masuk;
            $barang->save();

            // Check for low stock notifications
            NotifikasiStokController::checkLowStock($barang->barang_id);

            // Send email notification
            $reason = "Pembatalan barang masuk (ID: {$barangMasuk->masuk_id})";
            StockNotificationService::sendStockChangeNotification(
                $barang->barang_id,
                'decrease',
                $barangMasuk->jumlah_masuk,
                $reason,
                $oldStock
            );

            // Delete the record
            $barangMasuk->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Incoming stock record deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete incoming stock record',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
