<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peringatan Stok Rendah</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .warning-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px 0;
        }
        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .danger-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .stock-info {
            font-size: 18px;
            font-weight: bold;
            color: #dc3545;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .label {
            font-weight: bold;
            width: 30%;
        }
        .action-button {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="warning-icon">⚠️</div>
            <h1>Peringatan Stok Rendah</h1>
            <p>{{ $umkm->nama_umkm }}</p>
        </div>

        <div class="content">
            <div class="danger-box">
                <h2 style="color: #721c24; margin-top: 0;">🚨 Stok Produk Hampir Habis!</h2>
                <p style="color: #721c24; margin-bottom: 0;">
                    Produk <strong>{{ $barang->nama_barang }}</strong> sudah mencapai atau di bawah batas minimum yang telah ditetapkan.
                </p>
            </div>

            <div class="info-box">
                <h3>Informasi Produk</h3>
                <table>
                    <tr>
                        <td class="label">Nama Produk:</td>
                        <td><strong>{{ $barang->nama_barang }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Kategori:</td>
                        <td>{{ $barang->kategori->nama_kategori }}</td>
                    </tr>
                    <tr>
                        <td class="label">Satuan:</td>
                        <td>{{ $barang->satuan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Stok Saat Ini:</td>
                        <td class="stock-info">{{ $barang->stok }} {{ $barang->satuan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Batas Minimum:</td>
                        <td>{{ $barang->batas_minimum }} {{ $barang->satuan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td>
                            @if($barang->stok <= 0)
                                <span style="color: #dc3545; font-weight: bold;">🔴 STOK HABIS</span>
                            @elseif($barang->stok < $barang->batas_minimum)
                                <span style="color: #dc3545; font-weight: bold;">🟠 DI BAWAH MINIMUM</span>
                            @else
                                <span style="color: #ffc107; font-weight: bold;">⚠️ MENCAPAI MINIMUM</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Waktu Notifikasi:</td>
                        <td>{{ now()->format('d/m/Y H:i:s') }}</td>
                    </tr>
                </table>
            </div>

            @if($barang->stok <= 0)
                <div class="danger-box">
                    <h3 style="color: #721c24;">🚨 TINDAKAN SEGERA DIPERLUKAN</h3>
                    <p style="color: #721c24;">
                        Stok produk sudah <strong>HABIS</strong>. Segera lakukan pemesanan atau pengisian stok untuk menghindari kehilangan penjualan.
                    </p>
                </div>
            @else
                <div class="alert-box">
                    <h3 style="color: #856404;">💡 Rekomendasi Tindakan</h3>
                    <ul style="color: #856404;">
                        <li>Segera pesan stok dari supplier</li>
                        <li>Hubungi supplier untuk konfirmasi ketersediaan</li>
                        <li>Pertimbangkan untuk menaikkan batas minimum jika diperlukan</li>
                        <li>Monitor penjualan untuk memperkirakan kebutuhan stok</li>
                    </ul>
                </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <p>Untuk informasi lebih detail dan melakukan tindakan, silakan login ke sistem:</p>
                <a href="#" class="action-button">Login ke GudangKu</a>
            </div>
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem GudangKu</p>
            <p>Jangan membalas email ini. Untuk bantuan, hubungi administrator sistem.</p>
            <p><small>© {{ date('Y') }} GudangKu - Sistem Manajemen Inventori UMKM</small></p>
        </div>
    </div>
</body>
</html>
