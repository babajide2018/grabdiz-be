<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $previousStatus;
    public $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $previousStatus, string $newStatus)
    {
        $this->order = $order->load('items');
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjectMessages = [
            'pending' => 'Your Order is Being Processed - ' . $this->order->order_number,
            'processing' => 'Your Order is Being Prepared - ' . $this->order->order_number,
            'shipped' => 'Your Order has been Shipped - ' . $this->order->order_number,
            'delivered' => 'Your Order has been Delivered - ' . $this->order->order_number,
            'cancelled' => 'Your Order has been Cancelled - ' . $this->order->order_number,
        ];

        $subject = $subjectMessages[$this->newStatus] ?? 'Order Status Update - ' . $this->order->order_number;

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
            view: 'emails.order-status-update',
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

