<?php

namespace DaigoxCom\ArrayManager;

class ArrayCollection
{
    protected array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function get($key, $default = null)
    {
        return ArrayManager::get($this->items, $key, $default);
    }

    public function set($key, $value): self
    {
        ArrayManager::set($this->items, $key, $value);
        return $this;
    }

    public function has($keys): bool
    {
        return ArrayManager::has($this->items, $keys);
    }

    public function first(callable $callback = null, $default = null)
    {
        return ArrayManager::first($this->items, $callback, $default);
    }

    public function last(callable $callback = null, $default = null)
    {
        return ArrayManager::last($this->items, $callback, $default);
    }

    public function where(callable $callback): self
    {
        return new static(ArrayManager::where($this->items, $callback));
    }

    public function whereEquals($key, $value): self
    {
        return new static(ArrayManager::whereEquals($this->items, $key, $value));
    }

    public function whereIn($key, array $values): self
    {
        return new static(ArrayManager::whereIn($this->items, $key, $values));
    }

    public function whereNotIn($key, array $values): self
    {
        return new static(ArrayManager::whereNotIn($this->items, $key, $values));
    }

    public function whereBetween($key, $min, $max): self
    {
        return new static(ArrayManager::whereBetween($this->items, $key, $min, $max));
    }

    public function whereNotNull($key = null): self
    {
        return new static(ArrayManager::whereNotNull($this->items, $key));
    }

    public function map(callable $callback): self
    {
        return new static(ArrayManager::map($this->items, $callback));
    }

    public function mapWithKeys(callable $callback): self
    {
        return new static(ArrayManager::mapWithKeys($this->items, $callback));
    }

    public function pluck($value, $key = null): self
    {
        return new static(ArrayManager::pluck($this->items, $value, $key));
    }

    public function only($keys): self
    {
        return new static(ArrayManager::only($this->items, $keys));
    }

    public function except($keys): self
    {
        return new static(ArrayManager::except($this->items, $keys));
    }

    public function merge(...$arrays): self
    {
        return new static(ArrayManager::merge($this->items, ...$arrays));
    }

    public function flatten($depth = INF): self
    {
        return new static(ArrayManager::flatten($this->items, $depth));
    }

    public function unique($key = null): self
    {
        return new static(ArrayManager::unique($this->items, $key));
    }

    public function sort(callable $callback = null): self
    {
        return new static(ArrayManager::sort($this->items, $callback));
    }

    public function sortBy($key, int $options = SORT_REGULAR, bool $descending = false): self
    {
        return new static(ArrayManager::sortBy($this->items, $key, $options, $descending));
    }

    public function sortByMany(array $comparisons): self
    {
        return new static(ArrayManager::sortByMany($this->items, $comparisons));
    }

    public function groupBy($groupBy): self
    {
        return new static(ArrayManager::groupBy($this->items, $groupBy));
    }

    public function keyBy($keyBy): self
    {
        return new static(ArrayManager::keyBy($this->items, $keyBy));
    }

    public function chunk(int $size, bool $preserveKeys = false): self
    {
        return new static(ArrayManager::chunk($this->items, $size, $preserveKeys));
    }

    public function take(int $limit): self
    {
        return new static(ArrayManager::take($this->items, $limit));
    }

    public function slice(int $offset, ?int $length = null, bool $preserveKeys = false): self
    {
        return new static(ArrayManager::slice($this->items, $offset, $length, $preserveKeys));
    }

    public function shuffle($seed = null): self
    {
        return new static(ArrayManager::shuffle($this->items, $seed));
    }

    public function reverse(bool $preserveKeys = false): self
    {
        return new static(ArrayManager::reverse($this->items, $preserveKeys));
    }

    public function flip(): self
    {
        return new static(ArrayManager::flip($this->items));
    }

    public function values(): self
    {
        return new static(ArrayManager::values($this->items));
    }

    public function keys($searchValue = null, bool $strict = false): self
    {
        return new static(ArrayManager::keys($this->items, $searchValue, $strict));
    }

    public function filter(callable $callback): self
    {
        return new static(ArrayManager::where($this->items, $callback));
    }

    public function reject(callable $callback): self
    {
        return new static(ArrayManager::where($this->items, function ($value, $key) use ($callback) {
            return !$callback($value, $key);
        }));
    }

    public function partition(callable $callback): array
    {
        return ArrayManager::partition($this->items, $callback);
    }

    public function sum($callback = null)
    {
        return ArrayManager::sum($this->items, $callback);
    }

    public function avg($callback = null)
    {
        return ArrayManager::avg($this->items, $callback);
    }

    public function min($callback = null)
    {
        return ArrayManager::min($this->items, $callback);
    }

    public function max($callback = null)
    {
        return ArrayManager::max($this->items, $callback);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function countBy($callback): self
    {
        return new static(ArrayManager::countBy($this->items, $callback));
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function contains($value, bool $strict = false): bool
    {
        return ArrayManager::contains($this->items, $value, $strict);
    }

    public function search($value, bool $strict = false)
    {
        return ArrayManager::search($this->items, $value, $strict);
    }

    public function reduce(callable $callback, $initial = null)
    {
        return ArrayManager::reduce($this->items, $callback, $initial);
    }

    public function pipe(array $callbacks)
    {
        return ArrayManager::pipe($this->items, $callbacks);
    }

    public function tap(callable $callback): self
    {
        $callback($this->items);
        return $this;
    }

    public function transform(array $transformations): self
    {
        return new static(ArrayManager::transform($this->items, $transformations));
    }

    public function dot(): self
    {
        return new static(ArrayManager::dot($this->items));
    }

    public function undot(): self
    {
        return new static(ArrayManager::undot($this->items));
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(int $options = 0): string
    {
        return ArrayManager::toJson($this->items, $options);
    }

    public function toString(int $indentLevel = 0): string
    {
        return ArrayManager::toString($this->items, $indentLevel);
    }

    public function dd(): void
    {
        var_dump($this->items);
        die();
    }

    public function dump(): self
    {
        var_dump($this->items);
        return $this;
    }

    // Magic methods for array access
    public function offsetExists($offset): bool
    {
        return ArrayManager::has($this->items, $offset);
    }

    public function offsetGet($offset)
    {
        return ArrayManager::get($this->items, $offset);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            ArrayManager::set($this->items, $offset, $value);
        }
    }

    public function offsetUnset($offset): void
    {
        ArrayManager::forget($this->items, $offset);
    }

    // Make it countable
    public function __toString(): string
    {
        return $this->toJson();
    }

    // Make it iterable
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
