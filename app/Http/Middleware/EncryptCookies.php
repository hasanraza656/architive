<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        '__oppref',   // OpenAI ads pixel cookies are plain text set by the pixel; the server reads them for server-side events
        '__obref',
    ];
}
