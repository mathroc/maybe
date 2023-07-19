<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Option;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Option;
use TH\Maybe\Tests\Provider;

final class OfTest extends TestCase
{
    use Provider\Values;

    /**
     * @dataProvider fromValueMatrix
     * @param Option<mixed> $expected
     */
    public function testOf(Option $expected, mixed $value, mixed $noneValue, bool $strict = true): void
    {
        Assert::assertEquals($expected, Option\of(static fn () => $value, $noneValue, strict: $strict));
    }

    /**
     * @dataProvider fromValueMatrix
     * @param Option<mixed> $expected
     */
    public function testTryOf(Option $expected, mixed $value, mixed $noneValue, bool $strict = true): void
    {
        Assert::assertEquals($expected, Option\tryOf(static fn () => $value, $noneValue, strict: $strict));
    }

    public function testOfDefaultToNull(): void
    {
        Assert::assertEquals(Option\none(), Option\of(static fn () => null));
        Assert::assertEquals(Option\some(1), Option\of(static fn () => 1));
    }

    public function testTryOfDefaultToNull(): void
    {
        Assert::assertEquals(Option\none(), Option\tryOf(static fn () => null));
        Assert::assertEquals(Option\some(1), Option\tryOf(static fn () => 1));
    }

    public function testOfDefaultToStrict(): void
    {
        $o = (object)[];

        Assert::assertEquals(Option\none(), Option\of(static fn () => $o, (object)[], strict: false));
        Assert::assertEquals($o, Option\of(static fn () => $o, (object)[])->unwrap());
    }

    public function testTryOfDefaultToStrict(): void
    {
        $o = (object)[];

        Assert::assertEquals(Option\none(), Option\tryOf(static fn () => $o, (object)[], strict: false));
        Assert::assertEquals($o, Option\tryOf(static fn () => $o, (object)[])->unwrap());
    }

    public function testTryOfExeptions(): void
    {
        // @phpstan-ignore-next-line
        Assert::assertEquals(Option\tryOf(static fn () => new \DateTimeImmutable("none")), Option\none());

        try {
            // @phpstan-ignore-next-line
            Assert::assertEquals(Option\tryOf(static fn () => 1 / 0), Option\none());
            Assert::fail("An exception should have been thrown");
        } catch (\DivisionByZeroError) {
        }
    }
}
