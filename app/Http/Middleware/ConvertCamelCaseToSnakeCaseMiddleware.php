<?php

namespace App\Http\Middleware;

use App\Traits\CaseConverter;
use Closure;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpFoundation\Response;

class ConvertCamelCaseToSnakeCaseMiddleware
{
    use CaseConverter;
    public function handle($request, Closure $next)
    {
        $raw = $request->getContent();

        if (!empty($raw)) {
            $json = json_decode($raw, true);

            if (is_array($json)) {
                $snake = $this->toSnakeCase($json);

                // replace the parsed body
                $request->replace($snake);
            }
        }

        return $next($request);
    }
}
