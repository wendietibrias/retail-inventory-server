<?php

namespace App\Http\Middleware;

use App\Traits\CaseConverter;
use Closure;
use Illuminate\Http\Request;
use Log;
use Str;
use Symfony\Component\HttpFoundation\Response;
use function in_array;

class ConvertCamelCaseToSnakeCaseMiddleware
{
    use CaseConverter;
    public function handle($request, Closure $next)
    {
        // 1. Tangani Query Parameters (GET)
        if (!empty($request->query())) {
            $snakeCaseQuery = $this->convertKeysToSnakeCase($request->query());
            // Ganti query parameters dengan data yang sudah di-snake case
            $request->query->replace($snakeCaseQuery);
        }
        if ($request->isJson() || in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $snakeCaseInput = $this->convertKeysToSnakeCase($request->all());
            $request->replace($snakeCaseInput);
        }

        return $next($request);
    }

    protected function convertKeysToSnakeCase(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            // Konversi key saat ini
            $snakeKey = Str::snake($key);

            // Jika nilai adalah array dan bukan array asosiatif (misalnya list item), proses rekursif
            if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $value = $this->convertKeysToSnakeCase($value);
            }

            // Jika nilai adalah array non-asosiatif (list), proses setiap item
            if (is_array($value) && array_keys($value) === range(0, count($value) - 1)) {
                $value = array_map(function ($item) {
                    return is_array($item) ? $this->convertKeysToSnakeCase($item) : $item;
                }, $value);
            }

            $result[$snakeKey] = $value;
        }

        return $result;
    }
}
