<?php

namespace DaigoxCom\ArrayManager;

use ArgumentCountError;
use ArrayAccess;
use Closure;
use InvalidArgumentException;

class ArrayManager
{
    /**
     * Check if a value is accessible as an array
     */
    public static function isAccessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Check if array is associative
     */
    public static function isAssociative(array $array): bool
    {
        return !array_is_list($array);
    }

    /**
     * Check if array is sequential (indexed)
     */
    public static function isSequential(array $array): bool
    {
        return array_is_list($array);
    }

    /**
     * Check if array is multidimensional
     */
    public static function isMultidimensional(array $array): bool
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get value from array using dot notation
     */
    public static function get($array, $key, $default = null)
    {
        if (!static::isAccessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (static::isAccessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Set value in array using dot notation
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }
            unset($keys[$i]);
            
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Add value to array if key doesn't exist
     */
    public static function add(&$array, $key, $value): bool
    {
        if (static::has($array, $key)) {
            return false;
        }

        static::set($array, $key, $value);
        return true;
    }

    /**
     * Push value to array (supports dot notation)
     */
    public static function push(&$array, $key, $value): void
    {
        if (is_null($key)) {
            $array[] = $value;
            return;
        }

        $current = static::get($array, $key, []);
        $current[] = $value;
        static::set($array, $key, $current);
    }

    /**
     * Prepend value to array
     */
    public static function prepend(&$array, $value, $key = null): void
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
    }

    /**
     * Remove keys from array using dot notation
     */
    public static function forget(&$array, $keys): void
    {
        $original = &$array;
        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && static::isAccessible($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Pull value from array and remove it
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);
        return $value;
    }

    /**
     * Check if key exists in array
     */
    public static function exists($array, $key): bool
    {
        if (!static::isAccessible($array)) {
            return false;
        }

        if (is_float($key)) {
            $key = (string)$key;
        }

        return array_key_exists($key, $array);
    }

    /**
     * Check if array has key(s) using dot notation
     */
    public static function has($array, $keys): bool
    {
        $keys = (array)$keys;

        if (!$array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::isAccessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if array has any of the given keys
     */
    public static function hasAny($array, $keys): bool
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array)$keys;

        if (!$array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the first element
     */
    public static function first(array $array, callable $callback = null, $default = null)
    {
        if (empty($array)) {
            return value($default);
        }

        if (is_null($callback)) {
            return reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Get the last element
     */
    public static function last(array $array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Take first/last n elements
     */
    public static function take(array $array, int $limit): array
    {
        if ($limit < 0) {
            return array_slice($array, $limit, abs($limit));
        }

        return array_slice($array, 0, $limit);
    }

    /**
     * Flatten array to single dimension
     */
    public static function flatten($array, $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : static::flatten($item, $depth - 1);
                
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Divide array into keys and values
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Only include specified keys
     */
    public static function only(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Exclude specified keys
     */
    public static function except(array $array, $keys): array
    {
        static::forget($array, $keys);
        return $array;
    }

    /**
     * Filter array using callback
     */
    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Filter where value equals
     */
    public static function whereEquals(array $array, $key, $value): array
    {
        return static::where($array, function ($item) use ($key, $value) {
            return static::get($item, $key) === $value;
        });
    }

    /**
     * Filter where value is in array
     */
    public static function whereIn(array $array, $key, array $values): array
    {
        return static::where($array, function ($item) use ($key, $values) {
            return in_array(static::get($item, $key), $values);
        });
    }

    /**
     * Filter where value is not in array
     */
    public static function whereNotIn(array $array, $key, array $values): array
    {
        return static::where($array, function ($item) use ($key, $values) {
            return !in_array(static::get($item, $key), $values);
        });
    }

    /**
     * Filter where value is between
     */
    public static function whereBetween(array $array, $key, $min, $max): array
    {
        return static::where($array, function ($item) use ($key, $min, $max) {
            $value = static::get($item, $key);
            return $value >= $min && $value <= $max;
        });
    }

    /**
     * Filter where value is not null
     */
    public static function whereNotNull(array $array, $key = null): array
    {
        if (is_null($key)) {
            return array_filter($array, fn($value) => !is_null($value));
        }

        return static::where($array, function ($item) use ($key) {
            return !is_null(static::get($item, $key));
        });
    }

    /**
     * Pluck values from array
     */
    public static function pluck(array $array, $value, $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = static::get($item, $value);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $results[static::get($item, $key)] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Map array maintaining keys
     */
    public static function map(array $array, callable $callback): array
    {
        $keys = array_keys($array);

        try {
            $items = array_map($callback, $array, $keys);
        } catch (ArgumentCountError) {
            $items = array_map($callback, $array);
        }

        return array_combine($keys, $items);
    }

    /**
     * Map with keys
     */
    public static function mapWithKeys(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return $result;
    }

    /**
     * Merge arrays
     */
    public static function merge(...$arrays): array
    {
        return array_merge([], ...array_filter($arrays, 'is_array'));
    }

    /**
     * Merge arrays recursively
     */
    public static function mergeRecursive(...$arrays): array
    {
        return array_merge_recursive([], ...array_filter($arrays, 'is_array'));
    }

    /**
     * Combine arrays (keys from first, values from second)
     */
    public static function combine($keys, $values): array
    {
        if (count($keys) !== count($values)) {
            throw new InvalidArgumentException('Arrays must have the same length');
        }

        return array_combine($keys, $values);
    }

    /**
     * Cross join arrays
     */
    public static function crossJoin(...$arrays): array
    {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Collapse array of arrays into single array
     */
    public static function collapse(array $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

    /**
     * Zip arrays together
     */
    public static function zip(...$arrays): array
    {
        return array_map(null, ...$arrays);
    }

    /**
     * Pad array to specified length
     */
    public static function pad(array $array, int $size, $value): array
    {
        return array_pad($array, $size, $value);
    }

    /**
     * Group array by key
     */
    public static function groupBy(array $array, $groupBy): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $groupKey = is_callable($groupBy) 
                ? $groupBy($value, $key) 
                : static::get($value, $groupBy);

            $result[$groupKey][] = $value;
        }

        return $result;
    }

    /**
     * Key array by given key
     */
    public static function keyBy(array $array, $keyBy): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $resolvedKey = is_callable($keyBy) 
                ? $keyBy($value, $key) 
                : static::get($value, $keyBy);

            $result[$resolvedKey] = $value;
        }

        return $result;
    }

    /**
     * Sort array by callback
     */
    public static function sort(array $array, callable $callback = null): array
    {
        $callback ? uasort($array, $callback) : asort($array);
        return $array;
    }

    /**
     * Sort array by key
     */
    public static function sortBy(array $array, $key, int $options = SORT_REGULAR, bool $descending = false): array
    {

        $results = array_map(function ($value) use ($key) {
            return is_callable($key) ? $key($value) : static::get($value, $key);
        }, $array);

        $descending ? arsort($results, $options) : asort($results, $options);

        foreach (array_keys($results) as $k) {
            $results[$k] = $array[$k];
        }

        return $results;
    }

    /**
     * Sort array by multiple keys
     */
    public static function sortByMany(array $array, array $comparisons): array
    {
        usort($array, function ($a, $b) use ($comparisons) {
            foreach ($comparisons as $comparison) {
                $key = $comparison[0];
                $direction = $comparison[1] ?? 'asc';

                $aValue = is_callable($key) ? $key($a) : static::get($a, $key);
                $bValue = is_callable($key) ? $key($b) : static::get($b, $key);

                $result = $aValue <=> $bValue;

                if ($result !== 0) {
                    return $direction === 'desc' ? -$result : $result;
                }
            }

            return 0;
        });

        return $array;
    }

    /**
     * Sort array recursively
     */
    public static function sortRecursive($array, $options = SORT_REGULAR, $descending = false): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value, $options, $descending);
            }
        }

        if (!array_is_list($array)) {
            $descending ? krsort($array, $options) : ksort($array, $options);
        } else {
            $descending ? rsort($array, $options) : sort($array, $options);
        }

        return $array;
    }

    /**
     * Shuffle array
     */
    public static function shuffle(array $array, $seed = null): array
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * Get random element(s)
     */
    public static function random(array $array, int $number = 1, bool $preserveKeys = false)
    {
        $requested = is_null($number) ? 1 : $number;
        $count = count($array);

        if ($requested > $count) {
            throw new InvalidArgumentException("You requested {$requested} items, but there are only {$count} items available.");
        }

        if (is_null($number)) {
            return $array[array_rand($array)];
        }

        if ($requested === 0) {
            return [];
        }

        $keys = array_rand($array, $requested);
        $results = [];

        foreach ((array)$keys as $key) {
            if ($preserveKeys) {
                $results[$key] = $array[$key];
            } else {
                $results[] = $array[$key];
            }
        }

        return $results;
    }

    /**
     * Get unique values
     */
    public static function unique(array $array, $key = null): array
    {
        if (is_null($key)) {
            return array_unique($array);
        }

        $exists = [];
        $results = [];

        foreach ($array as $k => $item) {
            $value = static::get($item, $key);
            
            if (!in_array($value, $exists)) {
                $exists[] = $value;
                $results[$k] = $item;
            }
        }

        return $results;
    }

    /**
     * Get duplicate values
     */
    public static function duplicates(array $array, $key = null): array
    {
        $counts = [];
        $duplicates = [];

        foreach ($array as $item) {
            $value = is_null($key) ? $item : static::get($item, $key);
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        foreach ($array as $k => $item) {
            $value = is_null($key) ? $item : static::get($item, $key);
            
            if ($counts[$value] > 1) {
                $duplicates[$k] = $item;
            }
        }

        return $duplicates;
    }

    /**
     * Chunk array
     */
    public static function chunk(array $array, int $size, bool $preserveKeys = false): array
    {
        return array_chunk($array, $size, $preserveKeys);
    }

    /**
     * Split array into n groups
     */
    public static function split(array $array, int $numberOfGroups): array
    {
        if ($numberOfGroups <= 0) {
            return [];
        }

        $groups = [];
        $groupSize = ceil(count($array) / $numberOfGroups);

        for ($i = 0; $i < $numberOfGroups; $i++) {
            $groups[] = array_slice($array, $i * $groupSize, $groupSize);
        }

        return $groups;
    }

    /**
     * Partition array by callback
     */
    public static function partition(array $array, callable $callback): array
    {
        $passed = [];
        $failed = [];

        foreach ($array as $key => $item) {
            if ($callback($item, $key)) {
                $passed[$key] = $item;
            } else {
                $failed[$key] = $item;
            }
        }

        return [$passed, $failed];
    }

    /**
     * Get array depth
     */
    public static function depth(array $array): int
    {
        $maxDepth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = static::depth($value) + 1;
                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }

        return $maxDepth;
    }

    /**
     * Convert to query string
     */
    public static function query(array $array): string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Wrap value in array
     */
    public static function wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Get sum of array values
     */
    public static function sum(array $array, $callback = null)
    {
        if (is_null($callback)) {
            return array_sum($array);
        }

        $callback = static::valueRetriever($callback);

        return array_reduce($array, function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Get average of array values
     */
    public static function avg(array $array, $callback = null)
    {
        $count = count($array);
        return $count > 0 ? static::sum($array, $callback) / $count : null;
    }

    /**
     * Get min value
     */
    public static function min(array $array, $callback = null)
    {
        if (is_null($callback)) {
            return min($array);
        }

        $callback = static::valueRetriever($callback);
        $values = array_map($callback, $array);

        return min($values);
    }

    /**
     * Get max value
     */
    public static function max(array $array, $callback = null)
    {
        if (is_null($callback)) {
            return max($array);
        }

        $callback = static::valueRetriever($callback);
        $values = array_map($callback, $array);

        return max($values);
    }

    /**
     * Count values
     */
    public static function countValues(array $array): array
    {
        return array_count_values($array);
    }

    /**
     * Pipe array through callbacks
     */
    public static function pipe(array $array, array $callbacks)
    {
        return array_reduce($callbacks, function ($carry, $callback) {
            return $callback($carry);
        }, $array);
    }

    /**
     * Reduce array
     */
    public static function reduce(array $array, callable $callback, $initial = null)
    {
        return array_reduce($array, $callback, $initial);
    }

    /**
     * Every element passes test
     */
    public static function every(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Some elements pass test
     */
    public static function some(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Transform keys to lower case
     */
    public static function keysToLower(array $array): array
    {
        return array_change_key_case($array);
    }

    /**
     * Transform keys to upper case
     */
    public static function keysToUpper(array $array): array
    {
        return array_change_key_case($array, CASE_UPPER);
    }

    /**
     * Undot array (convert dot notation keys to nested array)
     */
    public static function undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Dot array (flatten with dot notation keys)
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Replace array values
     */
    public static function replace(array $array, array $replacement): array
    {
        return array_replace($array, $replacement);
    }

    /**
     * Replace array values recursively
     */
    public static function replaceRecursive(array $array, array $replacement): array
    {
        return array_replace_recursive($array, $replacement);
    }

    /**
     * Get value retriever callback
     */
    protected static function valueRetriever($value): callable
    {
        if (is_callable($value)) {
            return $value;
        }

        return fn($item) => static::get($item, $value);
    }

    /**
     * Stringify array (useful for debugging)
     */
    public static function toString(array $array, int $indentLevel = 0): string
    {
        $isAssoc = static::isAssociative($array);
        $indent = str_repeat('  ', $indentLevel);
        $result = $isAssoc ? "[\n" : "[";

        $items = [];
        foreach ($array as $key => $value) {
            $item = $isAssoc ? $indent . '  ' : '';
            
            if ($isAssoc) {
                $item .= var_export($key, true) . ' => ';
            }

            if (is_array($value)) {
                $item .= static::toString($value, $indentLevel + 1);
            } else {
                $item .= var_export($value, true);
            }

            $items[] = $item;
        }

        $result .= implode($isAssoc ? ",\n" : ", ", $items);
        $result .= $isAssoc ? "\n" . $indent . "]" : "]";

        return $result;
    }

    /**
     * Create array from range
     */
    public static function range($from, $to, $step = 1): array
    {
        return range($from, $to, $step);
    }

    /**
     * Repeat value n times
     */
    public static function repeat($value, int $times): array
    {
        return array_fill(0, $times, $value);
    }

    /**
     * Search value in array
     */
    public static function search(array $array, $value, bool $strict = false): false|int|string
    {
        return array_search($value, $array, $strict);
    }

    /**
     * Check if value exists in array
     */
    public static function contains(array $array, $value, bool $strict = false): bool
    {
        return in_array($value, $array, $strict);
    }

    /**
     * Get array without specified values
     */
    public static function without(array $array, ...$values): array
    {
        return array_diff($array, $values);
    }

    /**
     * Find differences between arrays
     */
    public static function diff(array $array, array ...$arrays): array
    {
        return array_diff($array, ...$arrays);
    }

    /**
     * Find intersection between arrays
     */
    public static function intersect(array $array, array ...$arrays): array
    {
        return array_intersect($array, ...$arrays);
    }

    /**
     * Flip array keys and values
     */
    public static function flip(array $array): array
    {
        return array_flip($array);
    }

    /**
     * Reverse array
     */
    public static function reverse(array $array, bool $preserveKeys = false): array
    {
        return array_reverse($array, $preserveKeys);
    }

    /**
     * Slice array
     */
    public static function slice(array $array, int $offset, ?int $length = null, bool $preserveKeys = false): array
    {
        return array_slice($array, $offset, $length, $preserveKeys);
    }

    /**
     * Splice array
     */
    public static function splice(array &$array, int $offset, ?int $length = null, $replacement = []): array
    {
        return array_splice($array, $offset, $length, $replacement);
    }

    /**
     * Remove empty values
     */
    public static function clean(array $array): array
    {
        return array_filter($array);
    }

    /**
     * Reset array keys
     */
    public static function values(array $array): array
    {
        return array_values($array);
    }

    /**
     * Get array keys
     */
    public static function keys(array $array, $searchValue = null, bool $strict = false): array
    {
        if (is_null($searchValue)) {
            return array_keys($array);
        }

        return array_keys($array, $searchValue, $strict);
    }

    /**
     * Apply callback to each element
     */
    public static function each(array $array, callable $callback): void
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
    }

    /**
     * Convert array to JSON
     */
    public static function toJson(array $array, int $options = 0): string
    {
        return json_encode($array, $options);
    }

    /**
     * Create array from JSON
     */
    public static function fromJson(string $json, bool $associative = true): array
    {
        return json_decode($json, $associative);
    }

    /**
     * Validate array structure
     */
    public static function validate(array $array, array $rules): bool
    {
        foreach ($rules as $key => $rule) {
            if (!static::has($array, $key)) {
                return false;
            }

            $value = static::get($array, $key);

            if (is_callable($rule) && !$rule($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Transform array using dot notation paths
     */
    public static function transform(array $array, array $transformations): array
    {
        $result = [];

        foreach ($transformations as $newKey => $oldKey) {
            if (is_callable($oldKey)) {
                $result[$newKey] = $oldKey($array);
            } else {
                $value = static::get($array, $oldKey);
                static::set($result, $newKey, $value);
            }
        }

        return $result;
    }

    /**
     * Recursive array diff
     */
    public static function diffRecursive(array $array1, array $array2): array
    {
        $diff = [];

        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $diff[$key] = $value;
            } elseif (is_array($value) && is_array($array2[$key])) {
                $recursiveDiff = static::diffRecursive($value, $array2[$key]);
                if (!empty($recursiveDiff)) {
                    $diff[$key] = $recursiveDiff;
                }
            } elseif ($value !== $array2[$key]) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Recursive array merge with callback
     */
    public static function mergeRecursiveWithCallback(array $array1, array $array2, callable $callback): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::mergeRecursiveWithCallback($merged[$key], $value, $callback);
            } else {
                $merged[$key] = isset($merged[$key]) ? $callback($merged[$key], $value, $key) : $value;
            }
        }

        return $merged;
    }

    /**
     * Array walk recursive with keys
     */
    public static function walkRecursive(array &$array, callable $callback, $userdata = null): bool
    {
        $walker = function (&$value, $key, $prefix = '') use (&$walker, $callback, $userdata) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                foreach ($value as $k => &$v) {
                    $walker($v, $k, $fullKey);
                }
            } else {
                $callback($value, $fullKey, $userdata);
            }
        };

        foreach ($array as $key => &$value) {
            $walker($value, $key);
        }

        return true;
    }

    /**
     * Normalize array (remove null values and reset keys)
     */
    public static function normalize(array $array): array
    {
        return array_values(array_filter($array, fn($value) => !is_null($value)));
    }

    /**
     * Get nested value or throw exception
     */
    public static function getOrFail(array $array, string $key)
    {
        if (!static::has($array, $key)) {
            throw new InvalidArgumentException("Key [{$key}] not found in array");
        }

        return static::get($array, $key);
    }

    /**
     * Set multiple values
     */
    public static function setMany(array &$array, array $values): void
    {
        foreach ($values as $key => $value) {
            static::set($array, $key, $value);
        }
    }

    /**
     * Cache result of expensive operation
     */
    public static function remember(array &$array, string $key, callable $callback)
    {
        if (!static::has($array, $key)) {
            static::set($array, $key, $callback());
        }

        return static::get($array, $key);
    }

    /**
     * Convert multidimensional array to single dimension with composite keys
     */
    public static function flattenWithKeys(array $array, string $prepend = '', string $separator = '.'): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $newKey = $prepend ? $prepend . $separator . $key : $key;
            
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::flattenWithKeys($value, $newKey, $separator));
            } else {
                $results[$newKey] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all possible paths in array
     */
    public static function paths(array $array): array
    {
        $paths = [];
        $dotted = static::dot($array);
        
        foreach (array_keys($dotted) as $key) {
            $paths[] = $key;
            $parts = explode('.', $key);
            
            for ($i = 1; $i < count($parts); $i++) {
                $paths[] = implode('.', array_slice($parts, 0, $i));
            }
        }

        return array_unique($paths);
    }

    /**
     * Create tree structure from flat array
     */
    public static function tree(array $array, string $parentKey = 'parent_id', string $childrenKey = 'children'): array
    {
        $grouped = static::groupBy($array, $parentKey);
        $roots = $grouped[null] ?? [];

        foreach ($roots as &$root) {
            $root[$childrenKey] = static::buildTree($root['id'] ?? null, $grouped, $childrenKey);
        }

        return $roots;
    }

    /**
     * Helper for building tree
     */
    protected static function buildTree($parentId, array $grouped, string $childrenKey): array
    {
        $children = [];

        if (isset($grouped[$parentId])) {
            foreach ($grouped[$parentId] as $item) {
                $item[$childrenKey] = static::buildTree($item['id'] ?? null, $grouped, $childrenKey);
                $children[] = $item;
            }
        }

        return $children;
    }

    /**
     * Array column with multiple columns
     */
    public static function columns(array $array, array $columns): array
    {
        return static::map($array, function ($item) use ($columns) {
            $result = [];
            foreach ($columns as $column) {
                $result[$column] = static::get($item, $column);
            }
            return $result;
        });
    }

    /**
     * Rename keys in array
     */
    public static function renameKeys(array $array, array $map): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $map[$key] ?? $key;
            $result[$newKey] = $value;
        }

        return $result;
    }

    /**
     * Recursive key rename
     */
    public static function renameKeysRecursive(array $array, array $map): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $map[$key] ?? $key;
            
            if (is_array($value)) {
                $result[$newKey] = static::renameKeysRecursive($value, $map);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Get cartesian product of arrays
     */
    public static function cartesian(array $array): array
    {
        if (empty($array)) {
            return [[]];
        }

        $result = [[]];

        foreach ($array as $key => $values) {
            $append = [];

            foreach ($result as $product) {
                foreach ($values as $value) {
                    $product[$key] = $value;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }

    /**
     * Sliding window over array
     */
    public static function sliding(array $array, int $size, int $step = 1): array
    {
        $result = [];
        $count = count($array);

        for ($i = 0; $i <= $count - $size; $i += $step) {
            $result[] = array_slice($array, $i, $size);
        }

        return $result;
    }

    /**
     * Array diff by callback
     */
    public static function diffUsing(array $array, array $values, callable $callback): array
    {
        return array_udiff($array, $values, $callback);
    }

    /**
     * Array intersect by callback
     */
    public static function intersectUsing(array $array, array $values, callable $callback): array
    {
        return array_uintersect($array, $values, $callback);
    }

    /**
     * Ensure array has all required keys
     */
    public static function ensure(array $array, array $keys, $defaultValue = null): array
    {
        foreach ($keys as $key) {
            if (!static::has($array, $key)) {
                static::set($array, $key, value($defaultValue));
            }
        }

        return $array;
    }

    /**
     * Tap into array (useful for debugging)
     */
    public static function tap(array $array, callable $callback): array
    {
        $callback($array);
        return $array;
    }

    /**
     * Count elements by callback
     */
    public static function countBy(array $array, $callback): array
    {
        $callback = static::valueRetriever($callback);
        $result = [];

        foreach ($array as $key => $value) {
            $groupKey = $callback($value, $key);
            $result[$groupKey] = ($result[$groupKey] ?? 0) + 1;
        }

        return $result;
    }

    /**
     * Check if arrays are equal
     */
    public static function equals(array $array1, array $array2, bool $strict = true): bool
    {
        if (count($array1) !== count($array2)) {
            return false;
        }

        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                return false;
            }

            $value2 = $array2[$key];

            if (is_array($value) && is_array($value2)) {
                if (!static::equals($value, $value2, $strict)) {
                    return false;
                }
            } elseif ($strict ? $value !== $value2 : $value != $value2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create collection-like wrapper for chaining
     */
    public static function collect(array $array): ArrayCollection
    {
        return new ArrayCollection($array);
    }
}

/**
 * Array Collection for method chaining
 */

// Helper function
if (!function_exists('value')) {
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}