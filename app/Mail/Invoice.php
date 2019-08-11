<?php

namespace App\Mail;

use App\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Invoice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The Transaction instance.
     *
     * @var Transaction
     */
    public $order;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Transaction $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(
                    'Invoice from ' . $this->order->sender->getFullName()
                )
                ->view('emails.invoice');
    }
}
