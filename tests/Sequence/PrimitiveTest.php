<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence\Primitive,
    Sequence\Implementation,
    Map,
    Sequence,
    Str,
    Set,
    Exception\OutOfBoundException,
    Exception\CannotGroupEmptyStructure,
    Exception\ElementNotFound,
};
use function Innmind\Immutable\unwrap;
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
        $sequence = new Primitive('int', 1, 2, 3, 4);
        $calls = 0;
        $sum = 0;

        $this->assertNull($sequence->foreach(function($i) use (&$calls, &$sum) {
            ++$calls;
            $sum += $i;
        }));
        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testThrowWhenTryingToGroupEmptySequence()
    {
        $this->expectException(CannotGroupEmptyStructure::class);

        (new Primitive('int'))->groupBy(fn($i) => $i);
    }

    public function testGroupBy()
    {
        $sequence = new Primitive('int', 1, 2, 3, 4);
        $groups = $sequence->groupBy(fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], $sequence->toArray());
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertTrue($groups->isOfType('int', Sequence::class));
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->get(0)->isOfType('int'));
        $this->assertTrue($groups->get(1)->isOfType('int'));
        $this->assertSame([2, 4], unwrap($groups->get(0)));
        $this->assertSame([1, 3], unwrap($groups->get(1)));
    }

    public function testThrowWhenTryingToAccessFirstElementOnEmptySequence()
    {
        $this->expectException(OutOfBoundException::class);

        (new Primitive('int'))->first();
    }

    public function testThrowWhenTryingToAccessLastElementOnEmptySequence()
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
        $sequence = new Primitive('int', 1, 2, 3);

        $this->assertTrue($sequence->contains(2));
        $this->assertFalse($sequence->contains(4));
    }

    public function testIndexOf()
    {
        $sequence = new Primitive('int', 1, 2, 4);

        $this->assertSame(1, $sequence->indexOf(2));
        $this->assertSame(2, $sequence->indexOf(4));
    }

    public function testThrowWhenTryingToAccessIndexOfUnknownValue()
    {
        $this->expectException(ElementNotFound::class);

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

    public function testIndicesOnEmptySequence()
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
        $sequence = new Primitive('int', 1, 2, 3, 4);
        $partition = $sequence->partition(fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $sequence->toArray());
        $this->assertInstanceOf(Map::class, $partition);
        $this->assertTrue($partition->isOfType('bool', Sequence::class));
        $this->assertCount(2, $partition);
        $this->assertTrue($partition->get(true)->isOfType('int'));
        $this->assertTrue($partition->get(false)->isOfType('int'));
        $this->assertSame([2, 4], unwrap($partition->get(true)));
        $this->assertSame([1, 3], unwrap($partition->get(false)));
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
        $sequence = new Primitive('int', 2, 3, 4, 5);
        $parts = $sequence->splitAt(2);

        $this->assertSame([2, 3, 4, 5], $sequence->toArray());
        $this->assertInstanceOf(Sequence::class, $parts);
        $this->assertTrue($parts->isOfType(Sequence::class));
        $this->assertCount(2, $parts);
        $this->assertTrue($parts->first()->isOfType('int'));
        $this->assertTrue($parts->last()->isOfType('int'));
        $this->assertSame([2, 3], unwrap($parts->first()));
        $this->assertSame([4, 5], unwrap($parts->last()));
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
        $sequence = new Primitive('int', 1, 2);
        $str = $sequence->join('|');

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
        $sequence = new Primitive('int', 1, 2, 3, 4);

        $this->assertSame(10, $sequence->reduce(0, fn($sum, $i) => $sum + $i));
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

    public function testToSetOf()
    {
        $sequence = new Primitive('int', 1, 2, 3);
        $set = $sequence->toSetOf('string|int', function($i) {
            yield (string) $i;
            yield $i;
        });

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(
            ['1', 1, '2', 2, '3', 3],
            unwrap($set),
        );
    }
}
