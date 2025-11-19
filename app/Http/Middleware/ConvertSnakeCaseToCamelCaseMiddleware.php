<?php

namespace App\Http\Middleware;

use App\Traits\CaseConverter;
use Closure;

class ConvertSnakeCaseToCamelCaseMiddleware
{
    use CaseConverter;

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // pastikan ini dijalankan SETELAH response dibuat
        if (method_exists($response, 'getData')) {
            $data = $response->getData(true);

            $converted = $this->toCamelCase($data);

            $response->setData($converted);
        }

        return $response;
    }
}
