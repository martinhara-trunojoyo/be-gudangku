<?php

namespace App\Http\Controllers;

use App\Models\NotifikasiStok;
use App\Models\Barang;
use App\Models\User;
use App\Mail\LowStockNotificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NotifikasiStokController extends Controller
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
                'message' => 'You need to be associated with a UMKM to access notifications.'
            ], 400);
        }

        return null;
    }

    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $notifications = NotifikasiStok::with('barang')
                                         ->whereHas('barang', function($query) {
                                             $query->where('umkm_id', Auth::user()->umkm_id);
                                         })
                                         ->orderBy('created_at', 'desc')
                                         ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display unread notifications.
     */
    public function unread()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $notifications = NotifikasiStok::with('barang')
                                         ->where('status', 'unread')
                                         ->whereHas('barang', function($query) {
                                             $query->where('umkm_id', Auth::user()->umkm_id);
                                         })
                                         ->orderBy('created_at', 'desc')
                                         ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Unread notifications retrieved successfully',
                'data' => $notifications,
                'count' => $notifications->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve unread notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $notification = NotifikasiStok::with('barang')
                                        ->where('notifikasi_id', $id)
                                        ->whereHas('barang', function($query) {
                                            $query->where('umkm_id', Auth::user()->umkm_id);
                                        })
                                        ->first();

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found or not authorized to access'
                ], 404);
            }

            $notification->status = 'read';
            $notification->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $updated = NotifikasiStok::whereHas('barang', function($query) {
                                        $query->where('umkm_id', Auth::user()->umkm_id);
                                    })
                                    ->where('status', 'unread')
                                    ->update(['status' => 'read']);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read',
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification.
     */
    public function destroy($id)
    {
        $check = $this->checkUmkmAccess();
        if ($check) return $check;

        try {
            $notification = NotifikasiStok::where('notifikasi_id', $id)
                                        ->whereHas('barang', function($query) {
                                            $query->where('umkm_id', Auth::user()->umkm_id);
                                        })
                                        ->first();

            if (!$notification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found or not authorized to delete'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check and create stock notifications for low stock items.
     */
    public static function checkLowStock($barangId)
    {
        try {
            $barang = Barang::with(['kategori', 'umkm'])->find($barangId);
            
            if (!$barang) {
                return;
            }

            // Check if stock is at or below minimum threshold
            if ($barang->stok <= $barang->batas_minimum) {
                // Check if there's already an unread notification for this product
                $existingNotification = NotifikasiStok::where('barang_id', $barangId)
                                                    ->where('status', 'unread')
                                                    ->first();

                if (!$existingNotification) {
                    // Create notification message
                    $pesan = "Stok barang '{$barang->nama_barang}' sudah mencapai batas minimum. ";
                    $pesan .= "Stok saat ini: {$barang->stok} {$barang->satuan}, ";
                    $pesan .= "Batas minimum: {$barang->batas_minimum} {$barang->satuan}";

                    NotifikasiStok::create([
                        'pesan' => $pesan,
                        'status' => 'unread',
                        'tanggal' => now(),
                        'barang_id' => $barangId
                    ]);

                    // Send email notification to all admins of this UMKM
                    self::sendLowStockEmailNotification($barang);
                }
            } else {
                // If stock is above minimum, mark existing notifications as read
                NotifikasiStok::where('barang_id', $barangId)
                            ->where('status', 'unread')
                            ->update(['status' => 'read']);
            }
        } catch (\Exception $e) {
            // Log error but don't stop the main process
            Log::error('Failed to check low stock: ' . $e->getMessage());
        }
    }

    /**
     * Send low stock email notification to all admins of the UMKM
     */
    private static function sendLowStockEmailNotification($barang)
    {
        try {
            // Get all admins for this UMKM
            $admins = User::where('umkm_id', $barang->umkm_id)
                         ->where('role', 'admin')
                         ->get();

            foreach ($admins as $admin) {
                try {
                    Mail::to($admin->email)->send(new LowStockNotificationMail($barang, $barang->umkm));
                    
                    Log::info("Low stock notification sent to {$admin->email} for product {$barang->nama_barang}");
                } catch (\Exception $e) {
                    Log::error("Failed to send low stock notification email to {$admin->email}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send low stock email notifications: ' . $e->getMessage());
        }
    }
}
