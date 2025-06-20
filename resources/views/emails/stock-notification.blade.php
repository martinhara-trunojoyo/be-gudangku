<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifikasi Perubahan Stok</title>
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
            background: {{ $type === 'increase' ? '#28a745' : '#dc3545' }};
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .content {
            padding: 20px 0;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid {{ $type === 'increase' ? '#28a745' : '#dc3545' }};
            padding: 15px;
            margin: 15px 0;
        }
        .stock-change {
            font-size: 18px;
            font-weight: bold;
            color: {{ $type === 'increase' ? '#28a745' : '#dc3545' }};
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $type === 'increase' ? '📈 Stok Bertambah' : '📉 Stok Berkurang' }}</h1>
            <p>{{ $umkm->nama_umkm }}</p>
        </div>

        <div class="content">
            <h2>Notifikasi Perubahan Stok</h2>
            
            <div class="info-box">
                <p><strong>Produk:</strong> {{ $barang->nama_barang }}</p>
                <p><strong>Kategori:</strong> {{ $barang->kategori->nama_kategori }}</p>
                <p><strong>Satuan:</strong> {{ $barang->satuan }}</p>
            </div>

            <table>
                <tr>
                    <td class="label">Stok Sebelumnya:</td>
                    <td>{{ $oldStock }} {{ $barang->satuan }}</td>
                </tr>
                <tr>
                    <td class="label">Perubahan:</td>
                    <td class="stock-change">
                        {{ $type === 'increase' ? '+' : '-' }}{{ $quantity }} {{ $barang->satuan }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Stok Sekarang:</td>
                    <td><strong>{{ $newStock }} {{ $barang->satuan }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Alasan:</td>
                    <td>{{ $reason }}</td>
                </tr>
                <tr>
                    <td class="label">Waktu:</td>
                    <td>{{ now()->format('d/m/Y H:i:s') }}</td>
                </tr>
            </table>

            @if($newStock <= $barang->batas_minimum)
                <div class="info-box" style="border-left-color: #ffc107; background: #fff3cd;">
                    <p style="color: #856404; margin: 0;">
                        ⚠️ <strong>Peringatan:</strong> Stok sudah mencapai atau di bawah batas minimum ({{ $barang->batas_minimum }} {{ $barang->satuan }})
                    </p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>Email ini dikirim secara otomatis oleh sistem GudangKu</p>
            <p>Untuk informasi lebih lanjut, silakan login ke aplikasi</p>
        </div>
    </div>
</body>
</html>
