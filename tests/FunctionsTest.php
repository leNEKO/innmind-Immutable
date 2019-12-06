<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use function Innmind\Immutable\{
    assertSet,
    assertSequence,
    assertMap,
    unwrap,
    join,
};
use Innmind\Immutable\{
    Set,
    Sequence,
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testAssertSet()
    {
        $this->assertNull(assertSet('string', Set::of('string'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Set<string>, Set<int> given');

        assertSet('string', Set::of('int'), 42);
    }

    public function testAssertSequence()
    {
        $this->assertNull(assertSequence('string', Sequence::of('string'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Sequence<string>, Sequence<int> given');

        assertSequence('string', Sequence::of('int'), 42);
    }

    public function testAssertMap()
    {
        $this->assertNull(assertMap('string', 'int', Map::of('string', 'int'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Map<string, int>, Map<string, string> given');

        assertMap('string', 'int', Map::of('string', 'string'), 42);
    }

    public function testUnwrapSet()
    {
        $this->assertSame(
            [1, 2, 3],
            unwrap(Set::ints(1, 2, 3)),
        );
    }

    public function testUnwrapSequence()
    {
        $this->assertSame(
            [1, 2, 3],
            unwrap(Sequence::ints(1, 2, 3)),
        );
    }

    public function testThrowWhenUnwrappingNotOfExpectedType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set|Sequence, stdClass given');

        unwrap(new \stdClass);
    }

    public function testThrowWhenJoiningNotOfExpectedType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set|Sequence, stdClass given');

        join('|', new \stdClass);
    }

    public function testThrowWhenJoiningOnNonStringSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set<string>, Set<int> given');

        join('|', Set::of('int'));
    }

    public function testThrowWhenJoiningOnNonStringSequence()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Sequence<string>, Sequence<int> given');

        join('|', Sequence::of('int'));
    }

    public function testJoinSet()
    {
        $str = join('|', Set::of('string', '1', '2', '3'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testJoinSequence()
    {
        $str = join('|', Sequence::of('string', '1', '2', '3'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }
}
