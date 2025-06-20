<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\User;
use App\Mail\StockNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StockNotificationService
{
    /**
     * Send stock change notification to all admins of the UMKM
     */
    public static function sendStockChangeNotification($barangId, $type, $quantity, $reason, $oldStock = null)
    {
        try {
            $barang = Barang::with(['kategori', 'umkm'])->find($barangId);
            
            if (!$barang) {
                return;
            }

            $newStock = $barang->stok;
            $oldStock = $oldStock ?? ($type === 'increase' ? $newStock - $quantity : $newStock + $quantity);

            // Get all admins for this UMKM
            $admins = User::where('umkm_id', $barang->umkm_id)
                         ->where('role', 'admin')
                         ->get();

            foreach ($admins as $admin) {
                try {
                    Mail::to($admin->email)->send(new StockNotificationMail(
                        $barang,
                        $type,
                        $quantity,
                        $reason,
                        $barang->umkm,
                        $oldStock,
                        $newStock
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send stock notification email to ' . $admin->email . ': ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Stock notification service error: ' . $e->getMessage());
        }
    }
}
