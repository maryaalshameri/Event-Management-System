<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('تذاكر فعاليتك جاهزة - ' . $this->order->event->title)
                    ->view('emails.ticket-generated')
                    ->attach(storage_path('app/public/' . $this->order->orderItems->first()->tickets->first()->pdf_path), [
                        'as' => 'tickets.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}