<?php
namespace AIAgent\Infrastructure\Tools;

final class Validator
{
    /**
     * Very small subset of JSON-schema-like validation.
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $input
     */
    public static function validate(array $schema, array $input): bool
    {
        $required = $schema['required'] ?? [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $input)) {
                return false;
            }
        }

        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            return true;
        }

        foreach ($schema['properties'] as $name => $rules) {
            if (!array_key_exists($name, $input)) {
                continue;
            }
            $type = $rules['type'] ?? null;
            if ($type === 'string' && !is_string($input[$name])) { return false; }
            if ($type === 'integer' && !is_int($input[$name])) { return false; }
            if ($type === 'object' && !is_array($input[$name])) { return false; }
        }
        return true;
    }
}


