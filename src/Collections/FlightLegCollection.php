<?php

declare(strict_types=1);

namespace Ezzaze\SsimParser\Collections;

use Ezzaze\SsimParser\DTOs\FlightLeg;

/**
 * @implements \IteratorAggregate<int, FlightLeg>
 */
final class FlightLegCollection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var list<FlightLeg> */
    private array $items;

    /**
     * @param list<FlightLeg> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Create a collection from an array of legacy associative arrays.
     *
     * @param list<array<string, string>> $arrays
     */
    public static function fromArrays(array $arrays): self
    {
        return new self(array_map(
            static fn (array $data): FlightLeg => FlightLeg::fromArray($data),
            $arrays,
        ));
    }

    /**
     * Filter the collection using a callback.
     *
     * @param callable(FlightLeg): bool $callback
     */
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Sort the collection by a property using a callback.
     *
     * @param callable(FlightLeg, FlightLeg): int $callback
     */
    public function sortBy(callable $callback): self
    {
        $items = $this->items;
        usort($items, $callback);

        return new self($items);
    }

    /**
     * Get the first flight in the collection, or null if empty.
     */
    public function first(): ?FlightLeg
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get the last flight in the collection, or null if empty.
     */
    public function last(): ?FlightLeg
    {
        if ($this->items === []) {
            return null;
        }

        return $this->items[count($this->items) - 1];
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Get a flight by index.
     */
    public function get(int $index): ?FlightLeg
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Get all flights as an array of FlightLeg DTOs.
     *
     * @return list<FlightLeg>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Convert all flights to legacy associative arrays.
     *
     * @return list<array<string, string|null>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (FlightLeg $flight): array => $flight->toArray(),
            $this->items,
        );
    }

    /**
     * Convert the collection to a JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return \ArrayIterator<int, FlightLeg>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return list<array<string, string|null>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
