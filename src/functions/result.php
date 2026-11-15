<?php declare(strict_types=1);

namespace TH\Maybe\Result;

use TH\DocTest\Attributes\ExamplesSetup;
use TH\Maybe\Option;
use TH\Maybe\Result;
use TH\Maybe\Tests\Helpers\IgnoreUnusedResults;
use function TH\Maybe\Internal\isOfAnyClass;

/**
 * Return a `Result\Ok` Result containing `$value`.
 *
 * ```php
 * $x = Result\ok(3);
 * self::assertTrue($x->isOk());
 * self::assertSame(3, $x->unwrap());
 * ```
 *
 * @template U
 * @param U $value
 * @return Result\Ok<U>
 */
function ok(mixed $value): Result\Ok
{
    return new Result\Ok($value);
}

/**
 * Return a `Result\Err` result.
 *
 * # Examples
 *
 * ```php
 * $x = Result\err("nope");
 * self::assertTrue($x->isErr());
 * self::assertSame("nope", $x->unwrapErr());
 * $x->unwrap(); // @throws RuntimeException Unwrapping `Err`: s:4:"nope";
 * ```
 *
 * @template F
 * @param F $value
 * @return Result\Err<F>
 */
function err(mixed $value): Result\Err
{
    return new Result\Err($value);
}

/**
 * Execute a callable and transform the result into an `Result`.
 * It will be a `Result\Ok` containing the result or, if it threw an exception
 * matching `$exceptionClass`, a `Result\Err` containing the exception.
 *
 * When `$toErr` is given, it is called with the result: if it returns `Option\Some`,
 * a `Result\Err` containing its value is returned instead.
 *
 * # Examples
 *
 * Successful execution:
 *
 * ```php
 * self::assertEq(Result\ok(3), Result\trap(fn () => 3));
 * ```
 *
 * Checked exception:
 *
 * ```php
 * $x = Result\trap(fn () => new \DateTimeImmutable("2020-30-30 UTC"));
 * self::assertTrue($x->isErr());
 * $x->unwrap();
 * // @throws Exception Failed to parse time string (2020-30-30 UTC) at position 6 (0): Unexpected character
 * ```
 *
 * Unchecked exception:
 *
 * ```php
 * Result\trap(fn () => 1/0);
 * // @throws DivisionByZeroError Division by zero
 * ```
 *
 * Returned value converted to an error:
 *
 * ```php
 * use TH\Maybe\Option;
 *
 * $x = Result\trap(
 *     fn () => strtotime("nope"),
 *     toErr: fn ($t) => $t === false ? Option\some("invalid date") : Option\none(),
 * );
 * self::assertEq(Result\err("invalid date"), $x);
 * ```
 *
 * @template U
 * @template E of \Throwable
 * @template F
 * @param callable(mixed...):U $callback
 * @param list<class-string<E>>|class-string<E> $exceptionClass
 * @param (callable(U):Option<F>)|null $toErr
 * @return Result<U,E|F>
 * @throws \Throwable
 */
#[ExamplesSetup(new IgnoreUnusedResults())]
function trap(callable $callback, array|string $exceptionClass = \Exception::class, ?callable $toErr = null): Result
{
    try {
        $value = $callback();
    } catch (\Throwable $th) {
        if (isOfAnyClass($th, (array) $exceptionClass)) {
            return Result\err($th);
        }

        throw $th;
    }

    if ($toErr !== null) {
        $err = $toErr($value);

        if ($err instanceof Option\Some) {
            return Result\err($err->unwrap());
        }
    }

    return Result\ok($value);
}

/**
 * Wrap a callable into one that transforms its returned value or thrown exception
 * into a `Result` like `Result\trap()` does (including `$toErr`), but without executing it.
 *
 * # Examples
 *
 * Successful execution:
 *
 * ```php
 * self::assertEq(Result\ok(3), Result\ify(fn () => 3)());
 * ```
 *
 * Checked exception:
 *
 * ```php
 * $x = Result\ify(fn () => new \DateTimeImmutable("2020-30-30 UTC"))();
 * self::assertTrue($x->isErr());
 * $x->unwrap();
 * // @throws Exception Failed to parse time string (2020-30-30 UTC) at position 6 (0): Unexpected character
 * ```
 *
 * Unchecked exception:
 *
 * ```php
 * Result\ify(fn () => 1/0)();
 * // @throws DivisionByZeroError Division by zero
 * ```
 *
 * Result-ify `strtotime()`:
 *
 * ```php
 * use TH\Maybe\Option;
 *
 * $strtotime = Result\ify(
 *     strtotime(...),
 *     toErr: fn ($t) => $t === false
 *         ? Option\some(new \RuntimeException("Could not convert string to time"))
 *         : Option\none(),
 * );
 *
 * self::assertEq($strtotime("2015-09-21 UTC midnight")->unwrap(), 1442793600);
 *
 * $r = $strtotime("nope");
 * self::assertTrue($r->isErr());
 * $r->unwrap(); // @throws RuntimeException Could not convert string to time
 * ```
 *
 * @template U
 * @template E of \Throwable
 * @template F
 * @param callable(mixed...):U $callback
 * @param list<class-string<E>>|class-string<E> $exceptionClass
 * @param (callable(U):Option<F>)|null $toErr
 * @return \Closure(mixed...):Result<U,E|F>
 */
#[ExamplesSetup(new IgnoreUnusedResults())]
function ify(callable $callback, array|string $exceptionClass = \Exception::class, ?callable $toErr = null): \Closure
{
    // @mago-ignore analysis:unhandled-thrown-type -- rethrowing exceptions not in $exceptionClass is the point
    return static fn (mixed ...$args): Result => Result\trap(
        static fn () => $callback(...$args),
        $exceptionClass,
        $toErr,
    );
}

/**
 * Converts from `Result<Result<T, E>, E>` to `Result<T, E>`.
 *
 * # Examples
 *
 * ```php
 * $x = Result\ok(3);
 * self::assertSame(Result\flatten(Result\ok($x)), $x);
 *
 * $x = Result\err("deity");
 * self::assertSame(Result\flatten(Result\ok($x)), $x);
 *
 * self::assertEq(Result\flatten($x), Result\err("deity"));
 * ```
 *
 * @template T
 * @template E
 * @template E1 of E
 * @template E2 of E
 * @param Result<Result<T, E1>, E2> $result
 * @return Result<T, E>
 */
#[ExamplesSetup(new IgnoreUnusedResults())]
function flatten(Result $result): Result
{
    return $result->mapOrElse(static fn (Result $r) => $r, static fn (mixed $err) => Result\err($err));
}

/**
 * Transposes a `Result` of an `Option` into an `Option` of a `Result`.
 *
 * `Ok(None)` will be mapped to `None`.
 * `Ok(Some(_))` and `Err(_)` will be mapped to `Some(Ok(_))` and `Some(Err(_))`.
 *
 * ```php
 * use TH\Maybe\Option;
 *
 * self::assertSame(Result\transpose(Result\ok(Option\none())), Option\none());
 *
 * $x = Result\ok(Option\some(4));
 * self::assertEq(Result\transpose($x), Option\some(Result\ok(4)));
 *
 * $x = Result\err("meat");
 * self::assertEq(Result\transpose($x), Option\some($x));
 * ```
 *
 * @template U
 * @template F
 * @param Result<Option<U>, F> $result
 * @return Option<Result<U, F>>
 */
#[ExamplesSetup(new IgnoreUnusedResults())]
function transpose(Result $result): Option
{
    // psalm infers Option<Ok<mixed>>|Some<Result<Option<U>, F>> without this cast
    /** @var Option<Result<U, F>> */
    return $result->mapOrElse(
        static fn (Option $option) => $option->map(Result\ok(...)),
        static fn () => Option\some(clone $result),
    );
}

/**
 * Takes each element in the iterable: if it is an `Err`, no further elements are
 * taken and the first `Err` is returned. Should no `Err` occur, an `Ok` containing
 * the list of all the values is returned.
 *
 * With `$drain` the whole iterable is consumed even after the first `Err`, using
 * every remaining result: handy when the results already exist (e.g. a literal
 * array) and letting the must-be-used invariant fire on the leftovers would be
 * noise. Values collected before the `Err` are still discarded.
 *
 * # Examples
 *
 * ```php
 * self::assertSame(Result\collect([Result\ok(1), Result\ok(2)])->unwrap(), [1, 2]);
 * self::assertSame(Result\collect([Result\ok(1), Result\err("nope")])->unwrapErr(), "nope");
 * self::assertSame(Result\collect([Result\err("nope"), Result\ok(2)], drain: true)->unwrapErr(), "nope");
 * self::assertSame(Result\collect([])->unwrap(), []);
 * ```
 *
 * @template U
 * @template E
 * @param iterable<Result<U, E>> $results
 * @param bool $drain consume the whole iterable & use every result, even after the first `Err`
 * @return Result<list<U>, E>
 */
#[ExamplesSetup(new IgnoreUnusedResults())]
function collect(iterable $results, bool $drain = false): Result
{
    $values = [];

    $firstErr = null;

    foreach ($results as $result) {
        if (!$result->isErr()) {
            $values[] = $result->unwrap();

            continue;
        }

        $firstErr ??= clone $result;

        if (!$drain) {
            break;
        }
    }

    return $firstErr ?? Result\ok($values);
}
