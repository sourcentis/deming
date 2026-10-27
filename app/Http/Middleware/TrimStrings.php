<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     */
    protected $except = [
        'password',
        'password_confirmation',
        // SMTP secrets of the settings page
        'smtp_password',
        'dkim_passphrase',
    ];
}
