<?php

namespace App\Mail;

use App\Models\Borrow;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BorrowReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Borrow $borrow;

    public function __construct(Borrow $borrow)
    {
        $this->borrow = $borrow;
    }

    public function build()
    {
        return $this
            ->subject('แจ้งเตือนกำหนดคืน Ebook')
            ->view('emails.borrow-reminder');
    }
}