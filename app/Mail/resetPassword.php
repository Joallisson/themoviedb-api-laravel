<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class resetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $email, $linkResetPassword;
    public function __construct($linkResetPassword, $email)
    {
        $this->email = $email;
        $this->linkResetPassword = $linkResetPassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->email;
        $linkResetPassword = $this->linkResetPassword;

        $this->from('joallisson.teste@outlook.com', 'Reviews')
            ->subject('Link para resetar sua senha')
            ->to($email);
        return $this->view('Mails.ResetPassword', ["linkResetPassword" => $linkResetPassword]);
    }
}
