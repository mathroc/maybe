<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Option;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Option;
use TH\Maybe\Tests\Provider;

final class IfyTest extends TestCase
{
    use Provider\Options;

    /**
     * @param Option<mixed> $expected
     */
    #[DataProvider('fromValueMatrix')]
    public function testIfy(Option $expected, mixed $value, mixed $noneValue, bool $strict = true): void
    {
        Assert::assertEquals($expected, Option\ify(static fn () => $value, $noneValue, strict: $strict)());
    }

    /**
     * @param Option<mixed> $expected
     */
    #[DataProvider('fromValueMatrix')]
    public function testTryIfy(Option $expected, mixed $value, mixed $noneValue, bool $strict = true): void
    {
        Assert::assertEquals($expected, Option\tryIfy(static fn () => $value, $noneValue, strict: $strict)());
    }

    public function testIfyDefaultToNull(): void
    {
        Assert::assertEquals(Option\none(), Option\ify(static fn () => null)());
        Assert::assertEquals(Option\some(1), Option\ify(static fn () => 1)());
    }

    public function testTryIfyDefaultToNull(): void
    {
        Assert::assertEquals(Option\none(), Option\tryIfy(static fn () => null)());
        Assert::assertEquals(Option\some(1), Option\tryIfy(static fn () => 1)());
    }

    public function testIfyDefaultToStrict(): void
    {
        $o = (object)[];

        Assert::assertEquals(Option\none(), Option\ify(static fn () => $o, (object)[], strict: false)());
        Assert::assertEquals($o, Option\ify(static fn () => $o, (object)[])()->unwrap());
    }

    public function testTryIfyDefaultToStrict(): void
    {
        $o = (object)[];

        Assert::assertEquals(Option\none(), Option\tryIfy(static fn () => $o, (object)[], strict: false)());
        Assert::assertEquals($o, Option\tryIfy(static fn () => $o, (object)[])()->unwrap());
    }

    public function testTryIfyExceptions(): void
    {
        Assert::assertEquals(Option\none(), Option\tryIfy(static fn () => new \DateTimeImmutable('nope'))());

        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');

        Option\tryIfy(static fn () => \intdiv(num1: 1, num2: 0))();
    }

    public function testTryIfyExceptionsWithExpectedClassList(): void
    {
        Assert::assertEquals(
            Option\none(),
            Option\tryIfy(
                static fn () => \intdiv(num1: 1, num2: 0),
                exceptionClass: [\LogicException::class, \DivisionByZeroError::class],
            )(),
        );
    }

    public function testTryIfyExceptionsOutsideExpectedClassArePropagated(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');

        Option\tryIfy(static fn () => \intdiv(num1: 1, num2: 0), exceptionClass: \LogicException::class)();
    }
}
