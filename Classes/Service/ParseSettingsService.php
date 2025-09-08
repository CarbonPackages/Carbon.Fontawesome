<?php

namespace Carbon\Fontawesome\Service;

use Neos\Flow\Annotations as Flow;
use Carbon\Eel\Service\StringConversionService;

/**
 * @phpstan-type IconSettings array<string,mixed>
 */
#[Flow\Scope('singleton')]
class ParseSettingsService {
    /**
     * Converts a string in the format "{key1:value1,key2:value2,…}"
     * into an associative array. Numeric values are converted to int or float.
     *
     * @param string|array<string,mixed>|null $input Input string or array, e.g. "{animation:spin,rotate:90}"
     * @return IconSettings|null Associative array, e.g. ['animation'=>'spin','rotate'=>90]
     */
    public function parse(mixed $input = null): ?array
    {
        if (is_array($input)) {
            if (empty($input)) {
                return null;
            }
            // If input is already an array, just convert keys and values
            return $this->keysToCamelCaseAndValuesToKebabCase($input);
        }

        if ($input === null || $input === '') {
            return null;
        }

        // 1. Remove the opening and closing braces
        $inner = $this->trim($input);
        if (substr($inner, 0, 1) === '{') {
            $inner = substr($inner, 1);
        }
        if (substr($inner, -1) === '}') {
            $inner = substr($inner, 0, -1);
        }

        // 2. If the string is empty after removing braces, return null
        if ($inner === '') {
            return null;
        }

        $result = [];
        // 3. Split by commas to get "key:value" pairs
        $pairs = explode(',', $inner);
        foreach ($pairs as $pair) {
            // 4. Split each pair by the first colon into key and value
            $parts = $this->trim(explode(':', $pair, 2));
            $key = $parts[0] ?: null;
            $value = $parts[1] ?? null;
            if (!$key) {
                // If there's no key, skip this pair
                continue;
            }
            if ($value === null) {
                // If there's no colon, treat the whole part as a key as true
                $result[$key] = true;
                continue;
            }

            // 5. Normalize lowercase for boolean/null check
            $lower = strtolower($value);
            if ($lower === 'true') {
                $result[$key] = true;
                continue;
            }
            if ($lower === 'false') {
                $result[$key] = false;
                continue;
            }
            if ($lower === 'null') {
                $result[$key] = null;
                continue;
            }

            // 6. If not boolean, check for numeric and convert accordingly
            $numeric = $this->normalizeNumericValue($value);
            if ($numeric !== null) {
                $result[$key] = $numeric;
                continue;
            }

            // 7. Otherwise trim the string
            $result[$key] = $this->trim($value);
        }

        return $this->keysToCamelCaseAndValuesToKebabCase($result);
    }

    /**
     * Trims a value or each element in an array.
     * If the value is a string, it trims whitespace.
     * If it's an array, it trims each element.
     * Otherwise, it returns the value unchanged.
     *
     * @param mixed $value
     * @return mixed
     */
    public function trim(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value, " \n\r\t\v\0'\"");
        }
        if (is_array($value)) {
            return array_map(fn($item) => $this->trim($item), $value);
        }
        return $value;
    }

    /**
     * Converts an associative array to a new array where:
     * - Keys are converted to camelCase
     * - Values are converted to kebab-case if they are strings
     *
     * @param array<string, mixed> $array The input associative array.
     * @return array<string, mixed> The transformed array with camelCase keys and kebab-case values.
     */
    /**
     * @param array<string,mixed> $array
     * @return IconSettings
     */
    private function keysToCamelCaseAndValuesToKebabCase(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            // Convert key to camelCase
            $camelCaseKey = StringConversionService::toCamelCase($key);
            if (
                $camelCaseKey === 'label' ||
                $camelCaseKey === 'tooltip' ||
                $camelCaseKey === 'alt' ||
                $camelCaseKey === 'title'
            ) {
                $result[$camelCaseKey] = $this->trim($value);
            } elseif (is_string($value)) {
                // Convert value to kebab-case if it's a string
                $result[
                    $camelCaseKey
                ] = StringConversionService::convertCamelCase($value);
            } else {
                $result[$camelCaseKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Normalizes a value to an int or float if it's numeric.
     * Returns null if the value is not numeric.
     *
     * @param mixed $value The value to normalize.
     * @return int|float|null The normalized numeric value, or null if not numeric.
     */
    private function normalizeNumericValue(mixed $value): int|float|null
    {
        if (is_float($value) || is_int($value)) {
            // Already a numeric type
            return $value;
        }

        if (!is_numeric($value)) {
            return null;
        }

        if (ctype_digit($value)) {
            // Pure integer
            return (int) $value;
        }

        // Float (contains decimal point or exponent)
        return (float) $value;
    }
}
