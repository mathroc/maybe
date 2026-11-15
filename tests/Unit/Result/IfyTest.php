<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Result;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Option;
use TH\Maybe\Result;
use TH\Maybe\Tests\Assert;
use TH\Maybe\Tests\Provider;

final class IfyTest extends TestCase
{
    use Provider\Values;

    #[DataProvider('values')]
    public function testIfyOk(mixed $value): void
    {
        $callback = static fn () => $value;

        Assert::assertEquals($value, Result\ify($callback)()->unwrap());
    }

    public function testIfyCheckedException(): void
    {
        Assert::assertEquals(
            new \Exception('Ooops'),
            Result\ify(static fn () => throw new \Exception('Ooops'))()->unwrapErr(),
        );
    }

    public function testIfyUncheckedException(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');

        Result\ify(static fn () => \intdiv(num1: 1, num2: 0))();
    }

    public function testIfyExceptionsWithExpectedClassList(): void
    {
        $result = Result\ify(
            static fn () => \intdiv(num1: 1, num2: 0),
            exceptionClass: [\LogicException::class, \DivisionByZeroError::class],
        )();

        Assert::assertTrue($result->isErr());
    }

    public function testIfyForwardsArguments(): void
    {
        $intdiv = Result\ify(\intdiv(...), \DivisionByZeroError::class);

        Assert::assertEquals(3, $intdiv(num1: 7, num2: 2)->unwrap());
        Assert::assertTrue($intdiv(num1: 7, num2: 0)->isErr());
    }

    public function testIfyWithToErr(): void
    {
        $strtotime = Result\ify(
            \strtotime(...),
            toErr: static fn (int|false $t): Option => $t === false ? Option\some('invalid date') : Option\none(),
        );

        Assert::assertEquals(1_442_793_600, $strtotime('2015-09-21 UTC midnight')->unwrap());
        Assert::assertEquals('invalid date', $strtotime('nope')->unwrapErr());
    }

    public function testIfyToErrIsNotCalledOnException(): void
    {
        $result = Result\ify(
            static fn () => throw new \RuntimeException('Ooops'),
            toErr: static fn (): Option => Assert::fail('toErr should not be called'),
        )();

        Assert::assertEquals(new \RuntimeException('Ooops'), $result->unwrapErr());
    }
}
