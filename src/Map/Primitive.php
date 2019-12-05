<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Type,
    Str,
    Sequence,
    Set,
    Pair,
    ValidateArgument,
    Exception\LogicException,
    Exception\ElementNotFound,
    Exception\CannotGroupEmptyStructure,
};

/**
 * @template T
 * @template S
 */
final class Primitive implements Implementation
{
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;
    /** @var array<T, S> */
    private array $values;
    private ?int $size;

    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);

        if (!in_array($keyType, ['int', 'integer', 'string'], true)) {
            throw new LogicException;
        }

        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->values = [];
        $this->size = null;
    }

    public function keyType(): string
    {
        return $this->keyType;
    }

    public function valueType(): string
    {
        return $this->valueType;
    }

    public function size(): int
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return $this->size ?? $this->size = \count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function put($key, $value): self
    {
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

        $map = clone $this;
        $map->size = null;
        $map->values[$key] = $value;

        return $map;
    }

    /**
     * @param T $key
     *
     * @throws ElementNotFound
     *
     * @return S
     */
    public function get($key)
    {
        if (!$this->contains($key)) {
            throw new ElementNotFound($key);
        }

        return $this->values[$key];
    }

    /**
     * @param T $key
     */
    public function contains($key): bool
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return \array_key_exists($key, $this->values);
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        $map = clone $this;
        $map->size = null;
        $map->values = [];

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     */
    public function equals(Implementation $map): bool
    {
        if ($map->size() !== $this->size()) {
            return false;
        }

        foreach ($this->values as $k => $v) {
            if (!$map->contains($k)) {
                return false;
            }

            if ($map->get($k) !== $v) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    public function filter(callable $predicate): self
    {
        $map = $this->clear();

        foreach ($this->values as $k => $v) {
            if ($predicate($this->normalizeKey($k), $v) === true) {
                $map->values[$k] = $v;
            }
        }

        return $map;
    }

    /**
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): void
    {
        foreach ($this->values as $k => $v) {
            $function($this->normalizeKey($k), $v);
        }
    }

    /**
     * @template D
     * @param callable(T, S): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
     *
     * @return Map<D, Map<T, S>>
     */
    public function groupBy(callable $discriminator): Map
    {
        if ($this->size() === 0) {
            throw new CannotGroupEmptyStructure;
        }

        $groups = null;

        foreach ($this->values as $k => $v) {
            /** @var T */
            $key = $this->normalizeKey($k);
            /** @var S */
            $value = $v;
            $discriminant = $discriminator($key, $value);

            if ($groups === null) {
                /** @var Map<D, Map<T, S>> */
                $groups = Map::of(
                    Type::determine($discriminant),
                    Map::class
                );
            }

            if ($groups->contains($discriminant)) {
                /** @var Map<T, S> */
                $group = $groups->get($discriminant);
                /** @var Map<T, S> */
                $group = $group->put($key, $value);

                $groups = $groups->put($discriminant, $group);
            } else {
                /** @var Map<T, S> */
                $group = $this->clearMap()->put($key, $value);

                $groups = $groups->put($discriminant, $group);
            }
        }

        /** @var Map<D, Map<T, S>> */
        return $groups;
    }

    /**
     * @return Set<T>
     */
    public function keys(): Set
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $keys = \array_keys($this->values);

        return Set::of(
            $this->keyType,
            ...\array_map(
                function($key) {
                    return $this->normalizeKey($key);
                },
                $keys,
            ),
        );
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $values = \array_values($this->values);

        return Sequence::of($this->valueType, ...$values);
    }

    /**
     * @param callable(T, S): (S|Pair<T, S>) $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        $map = $this->clear();

        foreach ($this->values as $k => $v) {
            $return = $function($this->normalizeKey($k), $v);

            if ($return instanceof Pair) {
                ($this->validateKey)($return->key(), 1);

                /** @var T */
                $key = $return->key();
                /** @var S */
                $value = $return->value();
            } else {
                $key = $k;
                $value = $return;
            }

            ($this->validateValue)($value, 2);

            $map->values[$key] = $value;
        }

        return $map;
    }

    public function join(string $separator): Str
    {
        return $this->values()->join($separator);
    }

    /**
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): self
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $map = clone $this;
        $map->size = null;
        /** @psalm-suppress MixedArrayTypeCoercion */
        unset($map->values[$key]);

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     *
     * @return self<T, S>
     */
    public function merge(Implementation $map): self
    {
        /** @var self<T, S> $merged */
        $merged = $map->reduce(
            $this,
            function(self $carry, $key, $value): self {
                return $carry->put($key, $value);
            }
        );

        return $merged;
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return Map<bool, Map<T, S>>
     */
    public function partition(callable $predicate): Map
    {
        $truthy = $this->clearMap();
        $falsy = $this->clearMap();

        foreach ($this->values as $k => $v) {
            $return = $predicate($this->normalizeKey($k), $v);

            if ($return === true) {
                $truthy = $truthy->put($k, $v);
            } else {
                $falsy = $falsy->put($k, $v);
            }
        }

        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
         */
        return Map::of('bool', Map::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T, S): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->values as $k => $v) {
            $carry = $reducer($carry, $this->normalizeKey($k), $v);
        }

        return $carry;
    }

    public function empty(): bool
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        \reset($this->values);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return \is_null(\key($this->values));
    }

    /**
     * @param mixed $value
     *
     * @return T
     */
    private function normalizeKey($value)
    {
        if ($this->keyType === 'string' && !\is_null($value)) {
            /** @var T */
            return (string) $value;
        }

        /** @var T */
        return $value;
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of($this->keyType, $this->valueType);
    }
}
