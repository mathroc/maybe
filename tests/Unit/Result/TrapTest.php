<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Result;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Option;
use TH\Maybe\Result;
use TH\Maybe\Tests\Assert;
use TH\Maybe\Tests\Provider;

final class TrapTest extends TestCase
{
    use Provider\Values;

    #[DataProvider('values')]
    public function testTrapOk(mixed $value): void
    {
        $callback = static fn () => $value;

        Assert::assertEquals($value, Result\trap($callback)->unwrap());
    }

    public function testTrapCheckedException(): void
    {
        Assert::assertEquals(
            new \Exception('Ooops'),
            Result\trap(static fn () => throw new \Exception('Ooops'))->unwrapErr(),
        );
    }

    public function testTrapUncheckedException(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');

        Result\trap(static fn () => \intdiv(num1: 1, num2: 0));
    }

    public function testTrapWithToErr(): void
    {
        $toErr = static fn (int $i): Option => $i > 5 ? Option\some('too big') : Option\none();

        Assert::assertEquals(3, Result\trap(static fn () => 3, toErr: $toErr)->unwrap());
        Assert::assertEquals('too big', Result\trap(static fn () => 6, toErr: $toErr)->unwrapErr());
    }
}
