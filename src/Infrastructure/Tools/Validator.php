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
            if ($type === 'number' && !is_numeric($input[$name])) { return false; }

            // minLength for strings
            if ($type === 'string' && isset($rules['minLength']) && is_int($rules['minLength'])) {
                if (mb_strlen((string) $input[$name]) < $rules['minLength']) { return false; }
            }

            // enum constraint
            if (isset($rules['enum']) && is_array($rules['enum'])) {
                if (!in_array($input[$name], $rules['enum'], true)) { return false; }
            }

            // minimum for numbers
            if ($type === 'number' && isset($rules['minimum'])) {
                if (!is_numeric($rules['minimum'])) { return false; }
                if ((float) $input[$name] < (float) $rules['minimum']) { return false; }
            }
        }
        return true;
    }
}


