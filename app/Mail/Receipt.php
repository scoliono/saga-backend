<?php

namespace App\Mail;

use App\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Receipt extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Transaction $order, bool $is_customer)
    {
        $this->order = $order;
        $this->customer = $is_customer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(
            $this->customer ? 'Receipt from SAGA'
                : 'Update to your Payment on SAGA'
        )->markdown('emails.receipt');
    }
}
