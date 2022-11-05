<?php

namespace App\Jobs;

use App\Mail\resetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class resetPasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries = 5;
    protected $email, $linkResetPassword;

    public function __construct($linkResetPassword, $email)
    {
        $this->email = $email;
        $this->linkResetPassword = $linkResetPassword;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = $this->email;
        $linkResetPassword = $this->linkResetPassword;

        Mail::send(new resetPassword($linkResetPassword, $email));
    }
}
