<?php declare(strict_types=1);

namespace DeRidderDenHertog\Core\Type;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use RuntimeException;

/**
 * @internal
 * @template T
 * @phpstan-consistent-constructor
 */
abstract readonly class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /** @param T[] $items */
    protected function __construct(protected array $items = []) {}

    public static function empty(): static
    {
        return new static();
    }

    /** @param T ...$items */
    public static function including(...$items): static
    {
        return new static($items);
    }

    /** @param T[] $items */
    public static function of(array $items): static
    {
        return new static($items);
    }

    /** @return T[] */
    public function all(): array
    {
        return [...$this->items];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Cannot write to immutable collection using array access.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Cannot write to immutable collection using array access.');
    }
}
