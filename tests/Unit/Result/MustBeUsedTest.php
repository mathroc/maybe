<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Result;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Result;
use TH\Maybe\Result\ResultCreationTrace;
use TH\Maybe\Result\UnusedResultException;

final class MustBeUsedTest extends TestCase
{
    /**
     * @param callable(): Result<mixed, mixed> $factory
     */
    #[DataProvider('resultsFactory')]
    public function testNotUsingAResultThrowAnExceptionWhenFreed(callable $factory): void
    {
        $this->expectException(UnusedResultException::class);

        (static function () use ($factory): void {
            $factory();
        })();
    }

    /**
     * @param callable(): Result<mixed, mixed> $factory
     */
    #[DataProvider('resultsFactory')]
    public function testNotUsingAResultThrowAnExceptionWithTheCreationTraceAsPrevious(callable $factory): void
    {
        (static function () use ($factory): void {
            try {
                $factory();
            } catch (UnusedResultException $exception) {
                Assert::assertSame('Unused Result dropped', $exception->getMessage());
                Assert::assertInstanceOf(ResultCreationTrace::class, $exception->getPrevious());
                Assert::assertSame('Result must be used. Created', $exception->getPrevious()->getMessage());
            }
        })();
    }

    /**
     * @param callable(): Result<mixed, mixed> $factory
     */
    #[DataProvider('resultsFactory')]
    public function testUsingAResultAvoidTheExceptionWhenFreed(callable $factory): void
    {
        (static function () use ($factory): void {
            $result = $factory();
            Assert::assertNull($result->unwrapOr(null));
        })();
    }

    /**
     * @param callable(): Result<mixed, mixed> $factory
     */
    #[DataProvider('resultsFactory')]
    public function testAClonedResultMustBeUsed(callable $factory): void
    {
        $this->expectException(UnusedResultException::class);

        (static function () use ($factory): void {
            $result = $factory();
            Assert::assertNull($result->unwrapOr(null));

            clone $result;
        })();
    }

    /**
     * @param callable(): Result<mixed, mixed> $factory
     * @throws \ReflectionException
     */
    #[DataProvider('resultsFactory')]
    public function testUsingAnUnregisteredResultThrowsALogicException(callable $factory): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Object not registered to be used');

        (static function () use ($factory): void {
            $result = $factory();
            Assert::assertNull($result->unwrapOr(null));

            // simulate an unregistered-but-live Result (as an unserialized one is):
            // drop its reaper entry, then use it again
            \assert($result instanceof Result\Ok || $result instanceof Result\Err);
            $method = new \ReflectionMethod($result::class, 'reapers');
            // @mago-ignore analysis:mixed-assignment -- reflection returns mixed by design
            $reapers = $method->invoke(null);
            \assert($reapers instanceof \WeakMap);
            $reapers->offsetUnset($result);

            $result->unwrapOr(null);
        })();
    }

    /**
     * @throws \ReflectionException
     */
    public function testRegisteringAResultTwiceThrowsALogicException(): void
    {
        $result = Result\ok(2);
        Assert::assertSame(2, $result->unwrap());

        // calling it again on the same object hits the "already registered" guard
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Object already register to be used');

        $used = new \ReflectionMethod(Result\Ok::class, 'mustBeUsed');
        $used->invoke($result);
    }

    /**
     * @param callable(): Result<mixed, mixed> $factory
     */
    #[DataProvider('resultsFactory')]
    public function testAnUnserializedResultDontHaveToBeUsed(callable $factory): void
    {
        (static function () use ($factory): void {
            $result = $factory();
            Assert::assertNull($result->unwrapOr(null));

            \unserialize(\serialize($result));
        })();
    }

    /**
     * @return iterable<array{callable():Result<mixed,mixed>}>
     */
    public static function resultsFactory(): iterable
    {
        yield 'ok' => [static fn (): Result => Result\ok(null)];
        yield 'err' => [static fn (): Result => Result\err(null)];
    }
}
