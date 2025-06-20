<?php

namespace App\Http\Controllers;

use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LaporanController extends Controller
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
                'message' => 'You need to be associated with a UMKM to access reports.'
            ], 400);
        }

        return null;
    }

    /**
     * Generate stock in report (Laporan Barang Masuk)
     */
    public function stockInReport(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = BarangMasuk::with(['barang.kategori', 'supplier', 'user'])
                               ->whereHas('barang', function($q) {
                                   $q->where('umkm_id', Auth::user()->umkm_id);
                               });

            // Apply date filter
            if ($request->start_date) {
                $query->whereDate('tanggal_masuk', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('tanggal_masuk', '<=', $request->end_date);
            }

            // Apply search filter
            if ($request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('barang', function($barangQuery) use ($searchTerm) {
                        $barangQuery->where('nama_barang', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('supplier', function($supplierQuery) use ($searchTerm) {
                        $supplierQuery->where('nama_supplier', 'like', "%{$searchTerm}%");
                    });
                });
            }

            // Order by date descending
            $query->orderBy('tanggal_masuk', 'desc');

            // Pagination
            $perPage = $request->per_page ?? 15;
            $data = $query->paginate($perPage);

            // Calculate totals
            $totalQuantity = $query->sum('jumlah_masuk');
            $totalTransactions = $query->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock in report generated successfully',
                'data' => $data,
                'summary' => [
                    'total_quantity' => $totalQuantity,
                    'total_transactions' => $totalTransactions,
                    'date_range' => [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate stock in report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate stock out report (Laporan Barang Keluar)
     */
    public function stockOutReport(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = BarangKeluar::with(['barang.kategori', 'user'])
                                ->whereHas('barang', function($q) {
                                    $q->where('umkm_id', Auth::user()->umkm_id);
                                });

            // Apply date filter
            if ($request->start_date) {
                $query->whereDate('tanggal_keluar', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('tanggal_keluar', '<=', $request->end_date);
            }

            // Apply search filter
            if ($request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('barang', function($barangQuery) use ($searchTerm) {
                        $barangQuery->where('nama_barang', 'like', "%{$searchTerm}%");
                    })
                    ->orWhere('tujuan', 'like', "%{$searchTerm}%");
                });
            }

            // Order by date descending
            $query->orderBy('tanggal_keluar', 'desc');

            // Pagination
            $perPage = $request->per_page ?? 15;
            $data = $query->paginate($perPage);

            // Calculate totals
            $totalQuantity = $query->sum('jumlah_keluar');
            $totalTransactions = $query->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock out report generated successfully',
                'data' => $data,
                'summary' => [
                    'total_quantity' => $totalQuantity,
                    'total_transactions' => $totalTransactions,
                    'date_range' => [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate stock out report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate stock summary report
     */
    public function stockSummary(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Set default dates if not provided
            $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();

            // Stock in summary
            $stockInSummary = BarangMasuk::whereHas('barang', function($q) {
                                        $q->where('umkm_id', Auth::user()->umkm_id);
                                    })
                                    ->whereBetween('tanggal_masuk', [$startDate, $endDate])
                                    ->selectRaw('COUNT(*) as total_transactions, SUM(jumlah_masuk) as total_quantity')
                                    ->first();

            // Stock out summary
            $stockOutSummary = BarangKeluar::whereHas('barang', function($q) {
                                         $q->where('umkm_id', Auth::user()->umkm_id);
                                     })
                                     ->whereBetween('tanggal_keluar', [$startDate, $endDate])
                                     ->selectRaw('COUNT(*) as total_transactions, SUM(jumlah_keluar) as total_quantity')
                                     ->first();

            // Low stock items (current stock, not filtered by date)
            $lowStockItems = \App\Models\Barang::where('umkm_id', Auth::user()->umkm_id)
                                             ->whereColumn('stok', '<=', 'batas_minimum')
                                             ->with('kategori')
                                             ->get();

            // Get top products by movement (in and out) within date range
            $topProductsIn = BarangMasuk::with('barang')
                                      ->whereHas('barang', function($q) {
                                          $q->where('umkm_id', Auth::user()->umkm_id);
                                      })
                                      ->whereBetween('tanggal_masuk', [$startDate, $endDate])
                                      ->selectRaw('barang_id, SUM(jumlah_masuk) as total_in')
                                      ->groupBy('barang_id')
                                      ->orderBy('total_in', 'desc')
                                      ->limit(5)
                                      ->get();

            $topProductsOut = BarangKeluar::with('barang')
                                        ->whereHas('barang', function($q) {
                                            $q->where('umkm_id', Auth::user()->umkm_id);
                                        })
                                        ->whereBetween('tanggal_keluar', [$startDate, $endDate])
                                        ->selectRaw('barang_id, SUM(jumlah_keluar) as total_out')
                                        ->groupBy('barang_id')
                                        ->orderBy('total_out', 'desc')
                                        ->limit(5)
                                        ->get();

            // Calculate stock value (if you have price field in barang table)
            $currentStockValue = \App\Models\Barang::where('umkm_id', Auth::user()->umkm_id)
                                                 ->selectRaw('COUNT(*) as total_products, SUM(stok) as total_stock')
                                                 ->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock summary generated successfully',
                'data' => [
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'days_count' => $startDate->diffInDays($endDate) + 1
                    ],
                    'stock_in' => [
                        'total_transactions' => $stockInSummary->total_transactions ?? 0,
                        'total_quantity' => $stockInSummary->total_quantity ?? 0
                    ],
                    'stock_out' => [
                        'total_transactions' => $stockOutSummary->total_transactions ?? 0,
                        'total_quantity' => $stockOutSummary->total_quantity ?? 0
                    ],
                    'net_stock_change' => [
                        'quantity' => ($stockInSummary->total_quantity ?? 0) - ($stockOutSummary->total_quantity ?? 0)
                    ],
                    'current_inventory' => [
                        'total_products' => $currentStockValue->total_products ?? 0,
                        'total_stock' => $currentStockValue->total_stock ?? 0
                    ],
                    'low_stock_items' => $lowStockItems,
                    'low_stock_count' => $lowStockItems->count(),
                    'top_products_in' => $topProductsIn,
                    'top_products_out' => $topProductsOut
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate stock summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export stock in report to CSV
     */
    public function exportStockIn(Request $request)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $query = BarangMasuk::with(['barang.kategori', 'supplier', 'user'])
                               ->whereHas('barang', function($q) {
                                   $q->where('umkm_id', Auth::user()->umkm_id);
                               });

            // Apply filters (same as stockInReport)
            if ($request->start_date) {
                $query->whereDate('tanggal_masuk', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('tanggal_keluar', '<=', $request->end_date);
            }

            if ($request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('barang', function($barangQuery) use ($searchTerm) {
                        $barangQuery->where('nama_barang', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('supplier', function($supplierQuery) use ($searchTerm) {
                        $supplierQuery->where('nama_supplier', 'like', "%{$searchTerm}%");
                    });
                });
            }

            $data = $query->orderBy('tanggal_masuk', 'desc')->get();

            // Format data for export
            $exportData = $data->map(function($item, $index) {
                return [
                    'no' => $index + 1,
                    'nama_barang' => $item->barang->nama_barang,
                    'kategori' => $item->barang->kategori->nama_kategori,
                    'jumlah_masuk' => $item->jumlah_masuk,
                    'satuan' => $item->barang->satuan,
                    'tanggal_masuk' => Carbon::parse($item->tanggal_masuk)->format('d/m/Y'),
                    'supplier' => $item->supplier->nama_supplier,
                    'petugas' => $item->user->name
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Stock in data prepared for export',
                'data' => $exportData,
                'filename' => 'laporan_barang_masuk_' . now()->format('Y_m_d_H_i_s') . '.csv'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export stock in report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
