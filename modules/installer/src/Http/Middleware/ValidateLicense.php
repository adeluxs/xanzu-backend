<?php

namespace Remotelywork\Installer\Http\Middleware;

use Closure;
use Remotelywork\Installer\Repository\App;

class ValidateLicense
{
    public function handle($request, Closure $next)
    {

        $isInstalled = file_exists(storage_path('installed'));

        if ($isInstalled && ! App::validateLicense()) {
            return redirect('blocked');
        }

        return $next($request);
    }
}
