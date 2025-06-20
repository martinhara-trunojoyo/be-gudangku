<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $barang;
    public $type;
    public $quantity;
    public $reason;
    public $umkm;
    public $oldStock;
    public $newStock;

    /**
     * Create a new message instance.
     */
    public function __construct($barang, $type, $quantity, $reason, $umkm, $oldStock, $newStock)
    {
        $this->barang = $barang;
        $this->type = $type; // 'increase' or 'decrease'
        $this->quantity = $quantity;
        $this->reason = $reason;
        $this->umkm = $umkm;
        $this->oldStock = $oldStock;
        $this->newStock = $newStock;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->type === 'increase' 
            ? 'Notifikasi Penambahan Stok - ' . $this->barang->nama_barang
            : 'Notifikasi Pengurangan Stok - ' . $this->barang->nama_barang;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.stock-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
