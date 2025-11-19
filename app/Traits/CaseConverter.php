<?php

namespace App\Traits;

trait CaseConverter
{
    // camelCase → snake_case
    public function toSnakeCase(array $array): array
    {
        $converted = [];

        foreach ($array as $key => $value) {
            $newKey = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key));

            if (is_array($value)) {
                $converted[$newKey] = $this->toSnakeCase($value);
            } else {
                $converted[$newKey] = $value;
            }
        }

        return $converted;
    }

    // snake_case → camelCase
    public function toCamelCase(array $array): array
    {
        $converted = [];

        foreach ($array as $key => $value) {
            $newKey = lcfirst(str_replace('_', '', ucwords($key, '_')));

            if (is_array($value)) {
                $converted[$newKey] = $this->toCamelCase($value);
            } else {
                $converted[$newKey] = $value;
            }
        }

        return $converted;
    }
}
