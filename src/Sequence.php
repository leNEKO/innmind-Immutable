<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @template T
 * @psalm-immutable
 */
final class Sequence implements \Countable
{
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $implementation;

    /**
     * @param Sequence\Implementation<T> $implementation
     */
    private function __construct(Sequence\Implementation $implementation)
    {
        $this->implementation = $implementation;
    }

    /**
     * Add the given element at the end of the sequence
     *
     * Example:
     * <code>
     * Sequence::of('int')(1)(3)
     * </code>
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        $self = clone $this;
        $self->implementation = ($this->implementation)($element);

        return $self;
    }

    /**
     * @template V
     * @no-named-arguments
     * @psalm-pure
     *
     * @param V $values
     *
     * @return self<V>
     */
    public static function of(...$values): self
    {
        return new self(new Sequence\Primitive($values));
    }

    /**
     * It will load the values inside the generator only upon the first use
     * of the sequence
     *
     * Use this mode when the amount of data may not fit in memory
     *
     * @template V
     * @psalm-pure
     *
     * @param \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function defer(\Generator $generator): self
    {
        return new self(new Sequence\Defer($generator));
    }

    /**
     * It will call the given function every time a new operation is done on the
     * sequence. This means the returned structure may not be truly immutable
     * as between the calls the underlying source may change.
     *
     * Use this mode when calling to an external source (meaning IO bound) such
     * as parsing a file or calling an API
     *
     * @template V
     * @psalm-pure
     *
     * @param callable(): \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function lazy(callable $generator): self
    {
        return new self(new Sequence\Lazy($generator));
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @param mixed $values
     *
     * @return self<mixed>
     */
    public static function mixed(...$values): self
    {
        return new self(new Sequence\Primitive($values));
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        /** @var self<int> */
        $self = new self(new Sequence\Primitive($values));

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        /** @var self<float> */
        $self = new self(new Sequence\Primitive($values));

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        /** @var self<string> */
        $self = new self(new Sequence\Primitive($values));

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        /** @var self<object> */
        $self = new self(new Sequence\Primitive($values));

        return $self;
    }

    public function size(): int
    {
        return $this->implementation->size();
    }

    public function count(): int
    {
        return $this->implementation->size();
    }

    /**
     * Return the element at the given index
     *
     * @return Maybe<T>
     */
    public function get(int $index): Maybe
    {
        return $this->implementation->get($index);
    }

    /**
     * Return the diff between this sequence and another
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function diff(self $sequence): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->diff(
            $sequence->implementation,
        );

        return $self;
    }

    /**
     * Remove all duplicates from the sequence
     *
     * @return self<T>
     */
    public function distinct(): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->distinct();

        return $self;
    }

    /**
     * Remove the n first elements
     *
     * @return self<T>
     */
    public function drop(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->drop($size);

        return $self;
    }

    /**
     * Remove the n last elements
     *
     * @return self<T>
     */
    public function dropEnd(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->dropEnd($size);

        return $self;
    }

    /**
     * Check if the two sequences are equal
     *
     * @param self<T> $sequence
     */
    public function equals(self $sequence): bool
    {
        return $this->implementation->equals(
            $sequence->implementation,
        );
    }

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->filter($predicate);

        return $self;
    }

    /**
     * Apply the given function to all elements of the sequence
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->implementation->foreach($function);
    }

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     *
     * @param callable(T): D $discriminator
     *
     * @return Map<D, self<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        return $this->implementation->groupBy($discriminator);
    }

    /**
     * Return the first element
     *
     * @return Maybe<T>
     */
    public function first(): Maybe
    {
        return $this->implementation->first();
    }

    /**
     * Return the last element
     *
     * @return Maybe<T>
     */
    public function last(): Maybe
    {
        return $this->implementation->last();
    }

    /**
     * Check if the sequence contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->implementation->contains($element);
    }

    /**
     * Return the index for the given element
     *
     * @param T $element
     *
     * @return Maybe<int>
     */
    public function indexOf($element): Maybe
    {
        return $this->implementation->indexOf($element);
    }

    /**
     * Return the list of indices
     *
     * @return self<int>
     */
    public function indices(): self
    {
        return new self($this->implementation->indices());
    }

    /**
     * Return a new sequence by applying the given function to all elements
     *
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return self<S>
     */
    public function map(callable $function): self
    {
        return new self($this->implementation->map($function));
    }

    /**
     * Append each sequence created by each value of the initial sequence
     *
     * @template S
     *
     * @param callable(T): self<S> $map
     *
     * @return self<S>
     */
    public function flatMap(callable $map): self
    {
        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedArgument
         */
        return $this->reduce(
            self::of(),
            static fn(self $carry, $value) => $carry->append($map($value)),
        );
    }

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function pad(int $size, $element): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->pad($size, $element);

        return $self;
    }

    /**
     * Return a sequence of 2 sequences partitioned according to the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, self<T>>
     */
    public function partition(callable $predicate): Map
    {
        return $this->implementation->partition($predicate);
    }

    /**
     * Slice the sequence
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->slice($from, $until);

        return $self;
    }

    /**
     * Return a sequence with the n first elements
     *
     * @return self<T>
     */
    public function take(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->take($size);

        return $self;
    }

    /**
     * Return a sequence with the n last elements
     *
     * @return self<T>
     */
    public function takeEnd(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->takeEnd($size);

        return $self;
    }

    /**
     * Append the given sequence to the current one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function append(self $sequence): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->append(
            $sequence->implementation,
        );

        return $self;
    }

    /**
     * Return a sequence with all elements from the current one that exist
     * in the given one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function intersect(self $sequence): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->intersect(
            $sequence->implementation,
        );

        return $self;
    }

    /**
     * Add the given element at the end of the sequence
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self
    {
        return ($this)($element);
    }

    /**
     * Sort the sequence in a different order
     *
     * @param callable(T, T): int $function
     *
     * @return self<T>
     */
    public function sort(callable $function): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->sort($function);

        return $self;
    }

    /**
     * Reduce the sequence to a single value
     *
     * @template R
     *
     * @param R $carry
     * @param callable(R, T): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->implementation->reduce($carry, $reducer);
    }

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self
    {
        $self = clone $this;
        $self->implementation = new Sequence\Primitive;

        return $self;
    }

    /**
     * Return the same sequence but in reverse order
     *
     * @return self<T>
     */
    public function reverse(): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->reverse();

        return $self;
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }

    /**
     * @return list<T>
     */
    public function toList(): array
    {
        /**
         * @psalm-suppress MixedAssignment
         * @var list<T>
         */
        return $this->reduce(
            [],
            static function(array $carry, $value): array {
                $carry[] = $value;

                return $carry;
            },
        );
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Maybe<T>
     */
    public function find(callable $predicate): Maybe
    {
        return $this->implementation->find($predicate);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function matches(callable $predicate): bool
    {
        /** @psalm-suppress MixedArgument */
        return $this->reduce(
            true,
            static fn(bool $matches, $value): bool => $matches && $predicate($value),
        );
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        return $this->find($predicate)->match(
            static fn() => true,
            static fn() => false,
        );
    }
}
