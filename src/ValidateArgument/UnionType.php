<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument,
    Type,
};

final class UnionType implements ValidateArgument
{
    /** @var list<ValidateArgument> */
    private array $types;

    public function __construct(
        ValidateArgument $first,
        ValidateArgument $second,
        ValidateArgument ...$rest
    ) {
        $this->types = [$first, $second, ...$rest];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        foreach ($this->types as $validate) {
            try {
                $validate($value, $position);

                return;
            } catch (\TypeError $e) {
                // try next type
            }
        }

        throw new \TypeError;
    }
}