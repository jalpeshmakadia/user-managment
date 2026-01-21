<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class WelcomeUserMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $plainPassword
    ) {
    }

    public function build(): self
    {
        return $this->subject('Welcome to ' . config('app.name'))
            ->view('emails.welcome-user')
            ->with([
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
            ]);
    }
}
