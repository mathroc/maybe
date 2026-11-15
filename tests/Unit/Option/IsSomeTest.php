<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Option;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Option;

final class IsSomeTest extends TestCase
{
    public function testFromValueWithIsSome(): void
    {
        Assert::assertEquals(Option\some(0), Option\fromValue(0, isSome: \is_int(...)));
        Assert::assertEquals(Option\none(), Option\fromValue(false, isSome: \is_int(...)));
        Assert::assertEquals(Option\some(null), Option\fromValue(null, isSome: static fn () => true));
    }

    public function testOfWithIsSome(): void
    {
        Assert::assertEquals(Option\some(2), Option\of(static fn () => \strpos(haystack: 'fruits', needle: 'u'), isSome: \is_int(...)));
        Assert::assertEquals(Option\none(), Option\of(static fn () => \strpos(haystack: 'fruits', needle: 'z'), isSome: \is_int(...)));
    }

    public function testTryOfWithIsSome(): void
    {
        Assert::assertEquals(Option\none(), Option\tryOf(static fn () => 3, isSome: static fn (int $i) => $i % 2 === 0));
        Assert::assertEquals(
            Option\none(),
            Option\tryOf(static fn () => throw new \RuntimeException('Ooops'), isSome: static fn () => true),
        );
    }

    public function testIfyWithIsSome(): void
    {
        $strpos = Option\ify(\strpos(...), isSome: \is_int(...));

        Assert::assertEquals(Option\some(2), $strpos(haystack: 'fruits', needle: 'u'));
        Assert::assertEquals(Option\none(), $strpos(haystack: 'fruits', needle: 'z'));
    }

    public function testIsSomeReplacesTheDefaultNoneValue(): void
    {
        Assert::assertEquals(Option\some(null), Option\ify(static fn () => null, isSome: static fn () => true)());
        Assert::assertEquals(Option\none(), Option\ify(static fn () => 1, isSome: static fn () => false)());
    }

    public function testTryIfyWithIsSome(): void
    {
        $tryIfy = Option\tryIfy(
            static fn (int $i): int => $i > 0 ? $i : throw new \RuntimeException('negative'),
            isSome: static fn (int $i): bool => $i % 2 === 0,
        );

        Assert::assertEquals(Option\some(2), $tryIfy(2));
        Assert::assertEquals(Option\none(), $tryIfy(3));
        Assert::assertEquals(Option\none(), $tryIfy(-1));
    }

    /**
     * @return iterable<string, array{\Closure():mixed}>
     */
    public static function noneValueAndIsSome(): iterable
    {
        $isInt = \is_int(...);
        $callback = static fn () => Assert::fail('the callback should not be called');

        yield 'fromValue' => [static fn () => Option\fromValue(1, noneValue: 0, isSome: $isInt)];
        yield 'of' => [static fn () => Option\of($callback, noneValue: 0, isSome: $isInt)];
        yield 'tryOf' => [static fn () => Option\tryOf($callback, noneValue: 0, isSome: $isInt)];
        yield 'ify' => [static fn () => Option\ify($callback, noneValue: 0, isSome: $isInt)];
        yield 'tryIfy' => [static fn () => Option\tryIfy($callback, noneValue: 0, isSome: $isInt)];
    }

    #[DataProvider('noneValueAndIsSome')]
    public function testNoneValueAndIsSomeAreMutuallyExclusive(\Closure $call): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$noneValue and $isSome cannot be used together');

        $call();
    }
}
