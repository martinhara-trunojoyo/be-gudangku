<?php

namespace App\Http\Controllers;

use App\Models\BarangKeluar;
use App\Models\Barang;
use App\Services\StockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BarangKeluarController extends Controller
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
     * Display a listing of barang keluar.
     */
    public function index()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $barangKeluar = BarangKeluar::with(['barang', 'user'])
                                      ->whereHas('barang', function($query) {
                                          $query->where('umkm_id', Auth::user()->umkm_id);
                                      })
                                      ->orderBy('created_at', 'desc')
                                      ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Outgoing stock retrieved successfully',
                'data' => $barangKeluar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve outgoing stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created barang keluar.
     */
    public function store(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'jumlah_keluar' => 'required|integer|min:1',
            'tanggal_keluar' => 'required|date',
            'tujuan' => 'required|string',
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

            // Check if there's enough stock
            if ($barang->stok < $request->jumlah_keluar) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock. Available: ' . $barang->stok . ', Requested: ' . $request->jumlah_keluar
                ], 409);
            }

            // Store old stock for notification
            $oldStock = $barang->stok;

            // Create barang keluar record
            $barangKeluar = BarangKeluar::create([
                'jumlah_keluar' => $request->jumlah_keluar,
                'tanggal_keluar' => $request->tanggal_keluar,
                'tujuan' => $request->tujuan,
                'barang_id' => $request->barang_id,
                'user_id' => Auth::id()
            ]);

            // Update stock - decrease stock
            $barang->stok -= $request->jumlah_keluar;
            $barang->save();

            // Check for low stock notifications
            NotifikasiStokController::checkLowStock($barang->barang_id);

            // Send email notification
            $reason = "Barang keluar ke: {$request->tujuan}";
            StockNotificationService::sendStockChangeNotification(
                $barang->barang_id,
                'decrease',
                $request->jumlah_keluar,
                $reason,
                $oldStock
            );

            DB::commit();

            // Load relationships
            $barangKeluar->load(['barang', 'user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Outgoing stock recorded successfully',
                'data' => $barangKeluar
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to record outgoing stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified barang keluar.
     */
    public function show($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $barangKeluar = BarangKeluar::with(['barang', 'user'])
                                      ->where('keluar_id', $id)
                                      ->whereHas('barang', function($query) {
                                          $query->where('umkm_id', Auth::user()->umkm_id);
                                      })
                                      ->first();

            if (!$barangKeluar) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outgoing stock record not found or not authorized to view'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Outgoing stock record retrieved successfully',
                'data' => $barangKeluar
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve outgoing stock record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified barang keluar.
     */
    public function destroy($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            DB::beginTransaction();

            $barangKeluar = BarangKeluar::where('keluar_id', $id)
                                      ->whereHas('barang', function($query) {
                                          $query->where('umkm_id', Auth::user()->umkm_id);
                                      })
                                      ->first();

            if (!$barangKeluar) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outgoing stock record not found or not authorized to delete'
                ], 404);
            }

            // Get the barang first
            $barang = Barang::find($barangKeluar->barang_id);

            // Store old stock for notification
            $oldStock = $barang->stok;
            $barang->stok += $barangKeluar->jumlah_keluar;
            $barang->save();

            // Check for low stock notifications
            NotifikasiStokController::checkLowStock($barang->barang_id);

            // Send email notification
            $reason = "Pembatalan barang keluar (ID: {$barangKeluar->keluar_id})";
            StockNotificationService::sendStockChangeNotification(
                $barang->barang_id,
                'increase',
                $barangKeluar->jumlah_keluar,
                $reason,
                $oldStock
            );

            // Delete the record
            $barangKeluar->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Outgoing stock record deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete outgoing stock record',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
