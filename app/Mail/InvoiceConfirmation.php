<?php

namespace App\Mail;

use App\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoiceConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Transaction $order)
    {
        $this->order = $order;
        $this->url = Str::replaceFirst(
            url('/api'),
            config('app.frontend_url'),
            URL::signedRoute('payments.confirm', ['id' => $order->id])
        );
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(
                    'You received an invoice from ' . $this->order->merchant->getFullName() . ' on SAGA'
                )
                ->view('emails.invoiceconfirmation');
    }
}
