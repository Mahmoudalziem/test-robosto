<?php

namespace Webkul\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgetPasswordOTPMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public function __construct($user)
    {
        $this->user=$user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build( )
    {

        $data['name']="mahmoud";
        $data['link']="mahmoud";
        $address = 'mail@example.com';
        $subject = 'This is a demo!';
        $data['user']= $this->user;


        return $this->view('user::emails.forgetpasswordOTPMail',$data)
            ->from($address, $data['name'])
            ->cc($this->user->email, $data['name'])
            ->bcc($this->user->email, $data['name'])
            ->replyTo($this->user->email, $data['name'])
            ->subject($subject)
            ->with([ 'test_message' => $this->user->email ]);

    }
}
