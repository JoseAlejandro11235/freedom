<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Livewire's temporary file upload endpoint is protected by a temporary
     * signed URL. In some reverse-proxy / Docker port-mapping setups, the
     * browser request can legitimately fail CSRF validation even though the
     * signature is valid. Exempting this endpoint keeps uploads reliable.
     *
     * @var array<int, string>
     */
    protected $except = [
        'livewire-*/upload-file',
        'livewire-*/preview-file/*',
    ];
}

