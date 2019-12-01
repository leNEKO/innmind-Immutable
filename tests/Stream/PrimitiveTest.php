<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Stream;

use Innmind\Immutable\{
    Stream\Primitive,
    Stream\Implementation,
    Map,
    Stream,
    Str,
    Exception\OutOfBoundException,
    Exception\GroupEmptySequenceException,
    Exception\ElementNotFoundException,
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Implementation::class,
            new Primitive('mixed'),
        );
    }

    public function testType()
    {
        $this->assertSame('int', (new Primitive('int'))->type());
    }

    public function testSize()
    {
        $this->assertSame(2, (new Primitive('int', 1, 1))->size());
        $this->assertSame(2, (new Primitive('int', 1, 1))->count());
    }

    public function testToArray()
    {
        $this->assertSame(
            [1, 2, 3],
            (new Primitive('int', 1, 2, 3))->toArray(),
        );
    }

    public function testGet()
    {
        $this->assertSame(42, (new Primitive('int', 1, 42, 3))->get(1));
    }

    public function testThrowWhenIndexNotFound()
    {
        $this->expectException(OutOfBoundException::class);

        (new Primitive('int'))->get(0);
    }

    public function testDiff()
    {
        $a = new Primitive('int', 1, 2);
        $b = new Primitive('int', 2, 3);
        $c = $a->diff($b);

        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([2, 3], $b->toArray());
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1], $c->toArray());
    }

    public function testDistinct()
    {
        $a = new Primitive('int', 1, 2, 1);
        $b = $a->distinct();

        $this->assertSame([1, 2, 1], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], $b->toArray());
    }

    public function testDrop()
    {
        $a = new Primitive('int', 1, 2, 3, 4);
        $b = $a->drop(2);

        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([3, 4], $b->toArray());
    }

    public function testDropEnd()
    {
        $a = new Primitive('int', 1, 2, 3, 4);
        $b = $a->dropEnd(2);

        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], $b->toArray());
    }

    public function testEquals()
    {
        $this->assertTrue((new Primitive('int', 1, 2))->equals(new Primitive('int', 1, 2)));
        $this->assertFalse((new Primitive('int', 1, 2))->equals(new Primitive('int', 2)));
    }

    public function testFilter()
    {
        $a = new Primitive('int', 1, 2, 3, 4);
        $b = $a->filter(fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4], $b->toArray());
    }

    public function testForeach()
    {
        $stream = new Primitive('int', 1, 2, 3, 4);
        $calls = 0;
        $sum = 0;

        $this->assertNull($stream->foreach(function($i) use (&$calls, &$sum) {
            ++$calls;
            $sum += $i;
        }));
        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testThrowWhenTryingToGroupEmptyStream()
    {
        $this->expectException(GroupEmptySequenceException::class);

        (new Primitive('int'))->groupBy(fn($i) => $i);
    }

    public function testGroupBy()
    {
        $stream = new Primitive('int', 1, 2, 3, 4);
        $groups = $stream->groupBy(fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], $stream->toArray());
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertTrue($groups->isOfType('int', Stream::class));
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->get(0)->isOfType('int'));
        $this->assertTrue($groups->get(1)->isOfType('int'));
        $this->assertSame([2, 4], $groups->get(0)->toArray());
        $this->assertSame([1, 3], $groups->get(1)->toArray());
    }

    public function testThrowWhenTryingToAccessFirstElementOnEmptyStream()
    {
        $this->expectException(OutOfBoundException::class);

        (new Primitive('int'))->first();
    }

    public function testThrowWhenTryingToAccessLastElementOnEmptyStream()
    {
        $this->expectException(OutOfBoundException::class);

        (new Primitive('int'))->last();
    }

    public function testFirst()
    {
        $this->assertSame(2, (new Primitive('int', 2, 3, 4))->first());
    }

    public function testLast()
    {
        $this->assertSame(4, (new Primitive('int', 2, 3, 4))->last());
    }

    public function testContains()
    {
        $stream = new Primitive('int', 1, 2, 3);

        $this->assertTrue($stream->contains(2));
        $this->assertFalse($stream->contains(4));
    }

    public function testIndexOf()
    {
        $stream = new Primitive('int', 1, 2, 4);

        $this->assertSame(1, $stream->indexOf(2));
        $this->assertSame(2, $stream->indexOf(4));
    }

    public function testThrowWhenTryingToAccessIndexOfUnknownValue()
    {
        $this->expectException(ElementNotFoundException::class);

        (new Primitive('int'))->indexOf(1);
    }

    public function testIndices()
    {
        $a = new Primitive('string', '1', '2');
        $b = $a->indices();

        $this->assertSame(['1', '2'], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame('int', $b->type());
        $this->assertSame([0, 1], $b->toArray());
    }

    public function testIndicesOnEmptyStream()
    {
        $a = new Primitive('string');
        $b = $a->indices();

        $this->assertSame([], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame('int', $b->type());
        $this->assertSame([], $b->toArray());
    }

    public function testMap()
    {
        $a = new Primitive('int', 1, 2, 3);
        $b = $a->map(fn($i) => $i * 2);

        $this->assertSame([1, 2, 3], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4, 6], $b->toArray());
    }

    public function testThrowWhenTryingToModifyTheTypeWhenMapping()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        (new Primitive('int', 1))->map(fn($i) => (string) $i);
    }

    public function testPad()
    {
        $a = new Primitive('int', 1, 2);
        $b = $a->pad(4, 0);
        $c = $a->pad(1, 0);

        $this->assertSame([1, 2], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 2, 0, 0], $b->toArray());
        $this->assertSame([1, 2], $c->toArray());
    }

    public function testPartition()
    {
        $stream = new Primitive('int', 1, 2, 3, 4);
        $partition = $stream->partition(fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $stream->toArray());
        $this->assertInstanceOf(Map::class, $partition);
        $this->assertTrue($partition->isOfType('bool', Stream::class));
        $this->assertCount(2, $partition);
        $this->assertTrue($partition->get(true)->isOfType('int'));
        $this->assertTrue($partition->get(false)->isOfType('int'));
        $this->assertSame([2, 4], $partition->get(true)->toArray());
        $this->assertSame([1, 3], $partition->get(false)->toArray());
    }

    public function testSlice()
    {
        $a = new Primitive('int', 2, 3, 4, 5);
        $b = $a->slice(1, 3);

        $this->assertSame([2, 3, 4, 5], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([3, 4], $b->toArray());
    }

    public function testSplitAt()
    {
        $stream = new Primitive('int', 2, 3, 4, 5);
        $parts = $stream->splitAt(2);

        $this->assertSame([2, 3, 4, 5], $stream->toArray());
        $this->assertInstanceOf(Stream::class, $parts);
        $this->assertTrue($parts->isOfType(Stream::class));
        $this->assertCount(2, $parts);
        $this->assertTrue($parts->first()->isOfType('int'));
        $this->assertTrue($parts->last()->isOfType('int'));
        $this->assertSame([2, 3], $parts->first()->toArray());
        $this->assertSame([4, 5], $parts->last()->toArray());
    }

    public function testTake()
    {
        $a = new Primitive('int', 2, 3, 4);
        $b = $a->take(2);

        $this->assertSame([2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 3], $b->toArray());
    }

    public function testTakeEnd()
    {
        $a = new Primitive('int', 2, 3, 4);
        $b = $a->takeEnd(2);

        $this->assertSame([2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([3, 4], $b->toArray());
    }

    public function testAppend()
    {
        $a = new Primitive('int', 1, 2);
        $b = new Primitive('int', 3, 4);
        $c = $a->append($b);

        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([3, 4], $b->toArray());
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 2, 3, 4], $c->toArray());
    }

    public function testIntersect()
    {
        $a = new Primitive('int', 1, 2);
        $b = new Primitive('int', 2, 3);
        $c = $a->intersect($b);

        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([2, 3], $b->toArray());
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([2], $c->toArray());
    }

    public function testJoin()
    {
        $stream = new Primitive('int', 1, 2);
        $str = $stream->join('|');

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2', (string) $str);
    }

    public function testAdd()
    {
        $a = new Primitive('int', 1);
        $b = $a->add(2);

        $this->assertSame([1], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], $b->toArray());
    }

    public function testSort()
    {
        $a = new Primitive('int', 1, 4, 3, 2);
        $b = $a->sort(fn($a, $b) => $a > $b);

        $this->assertSame([1, 4, 3, 2], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2, 3, 4], $b->toArray());
    }

    public function testReduce()
    {
        $stream = new Primitive('int', 1, 2, 3, 4);

        $this->assertSame(10, $stream->reduce(0, fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = new Primitive('int', 1, 2);
        $b = $a->clear();

        $this->assertSame([1, 2], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([], $b->toArray());
    }

    public function testReverse()
    {
        $a = new Primitive('int', 1, 2, 3, 4);
        $b = $a->reverse();

        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([4, 3, 2, 1], $b->toArray());
    }

    public function testEmpty()
    {
        $this->assertTrue((new Primitive('int'))->empty());
        $this->assertFalse((new Primitive('int', 1))->empty());
    }
}
