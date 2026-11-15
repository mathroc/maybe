<?php declare(strict_types=1);

namespace TH\Maybe\Option;

use TH\DocTest\Attributes\ExamplesSetup;
use TH\Maybe\Option;
use TH\Maybe\Result;
use TH\Maybe\Tests\Helpers\IgnoreUnusedResults;
use function TH\Maybe\Internal\assertNoneValueOrIsSome;
use function TH\Maybe\Internal\isOfAnyClass;

/**
 * Return a `Option\Some` option containing `$value`.
 *
 * @template T
 * @param T $value
 * @return Option\Some<T>
 */
function some(mixed $value): Option\Some
{
    return new Option\Some($value);
}

/**
 * Return a `Option\None` option containing no values.
 */
function none(): Option\None
{
    return Option\None::instance;
}

/**
 * Transform a value into an `Option`.
 * It will be a `Some` option containing `$value` if `$value` is different from `$noneValue` (default `null`),
 * or, when `$isSome` is given, if `$isSome($value)` returns `true` (`$strict` is then ignored).
 * `$noneValue` and `$isSome` cannot be used together: an `\InvalidArgumentException` is thrown if both are given.
 *
 * # Examples
 *
 * ```php
 * self::assertEq(Option\fromValue("fruits"), Option\some("fruits"));
 * self::assertEq(Option\fromValue(null), Option\none());
 * ```
 *
 * Using a predicate:
 *
 * ```php
 * self::assertEq(Option\fromValue(3, isSome: is_int(...)), Option\some(3));
 * self::assertEq(Option\fromValue(false, isSome: is_int(...)), Option\none());
 * ```
 *
 * `$noneValue` and `$isSome` are mutually exclusive:
 *
 * ```php
 * Option\fromValue(3, noneValue: 0, isSome: is_int(...));
 * // @throws InvalidArgumentException $noneValue and $isSome cannot be used together
 * ```
 *
 * @template U
 * @param U $value
 * @param (callable(U):bool)|null $isSome
 * @return ($isSome is null ? Option<U> : ($noneValue is null ? Option<U> : never))
 * @throws \InvalidArgumentException if both `$noneValue` and `$isSome` are given
 */
function fromValue(mixed $value, mixed $noneValue = null, bool $strict = true, ?callable $isSome = null): Option
{
    assertNoneValueOrIsSome($noneValue, $isSome);

    if ($isSome !== null) {
        return $isSome($value)
            ? Option\some($value)
            : Option\none();
    }

    $same = $strict
        ? $value === $noneValue
        // @mago-expect analysis:possibly-null-operand -- left: `$value` may be null, comparing it to `$noneValue` is the point
        // @mago-expect analysis:mixed-operand -- right: `$noneValue` can be anything, loose comparison is the point of `$strict = false`
        // @mago-expect lint:identity-comparison
        : $value == $noneValue;

    return $same
        ? Option\none()
        : Option\some($value);
}

/**
 * Execute a callable and transform the result into an `Option`.
 * It will be a `Some` option containing the result if it is different from `$noneValue` (default `null`),
 * or, when `$isSome` is given, if `$isSome($result)` returns `true` (`$noneValue` and `$isSome` cannot be used together).
 *
 * # Examples
 *
 * Successful execution:
 *
 * ```php
 * self::assertEq(Option\of(fn() => "fruits"), Option\some("fruits"));
 * ```
 *
 * Conversion of `null` to `Option\None`:
 *
 * ```php
 * self::assertEq(Option\of(fn() => null), Option\none());
 * ```
 *
 * Using a predicate:
 *
 * ```php
 * self::assertEq(Option\of(fn() => strpos("fruits", "z"), isSome: is_int(...)), Option\none());
 * ```
 *
 * @template U
 * @param callable():U $callback
 * @param (callable(U):bool)|null $isSome
 * @return ($isSome is null ? Option<U> : ($noneValue is null ? Option<U> : never))
 * @throws \InvalidArgumentException if both `$noneValue` and `$isSome` are given
 */
function of(callable $callback, mixed $noneValue = null, bool $strict = true, ?callable $isSome = null): Option
{
    assertNoneValueOrIsSome($noneValue, $isSome);

    return Option\fromValue($callback(), $noneValue, $strict, $isSome);
}

/**
 * Execute a callable and transform the result into an `Option` as `Option\of()` does
 * but also return `Option\None` if an exception matching `$exceptionClass` was thrown.
 *
 * # Examples
 *
 * Successful execution:
 *
 * ```php
 * self::assertEq(Option\tryOf(fn () => strtolower("FRUITS")), Option\some("fruits"));
 * ```
 *
 * Conversion of `null` to `Option\None`:
 *
 * ```php
 * self::assertEq(Option\tryOf(fn() => null), Option\none());
 * ```
 *
 * Checked Exception:
 *
 * ```php
 * self::assertEq(Option\tryOf(fn () => new \DateTimeImmutable("nope")), Option\none());
 * ```
 *
 * Unchecked Exception:
 *
 * ```php
 * self::assertEq(Option\tryOf(fn () => 1 / 0), Option\none());
 * // @throws DivisionByZeroError Division by zero
 * ```
 *
 * @template U
 * @template E of \Throwable
 * @param callable():U $callback
 * @param list<class-string<E>>|class-string<E> $exceptionClass
 * @param (callable(U):bool)|null $isSome
 * @return ($isSome is null ? Option<U> : ($noneValue is null ? Option<U> : never))
 * @throws \InvalidArgumentException if both `$noneValue` and `$isSome` are given
 * @throws \Throwable
 */
function tryOf(
    callable $callback,
    mixed $noneValue = null,
    bool $strict = true,
    array|string $exceptionClass = \Exception::class,
    ?callable $isSome = null,
): Option {
    assertNoneValueOrIsSome($noneValue, $isSome);

    try {
        return Option\of($callback, $noneValue, $strict, $isSome);
    } catch (\Throwable $th) {
        if (isOfAnyClass($th, (array) $exceptionClass)) {
            return Option\none();
        }

        throw $th;
    }
}

/**
 * Wrap a callable into one that transforms its result into an `Option`.
 * It will be a `Some` option containing the result if it is different from `$noneValue` (default `null`),
 * or, when `$isSome` is given, if `$isSome($result)` returns `true` (`$noneValue` and `$isSome` cannot be used together).
 *
 * # Examples
 *
 * Successful execution:
 *
 * ```php
 * self::assertEq(Option\ify(strtolower(...))("FRUITS"), Option\some("fruits"));
 * ```
 *
 * Conversion of `null` to `Option\None`:
 *
 * ```php
 * self::assertEq(Option\ify(fn() => null)(), Option\none());
 * ```
 *
 * Option-ify `strpos()`:
 *
 * ```php
 * $strpos = Option\ify(strpos(...), isSome: is_int(...));
 *
 * self::assertEq($strpos("fruits", "u"), Option\some(2));
 * self::assertEq($strpos("fruits", "z"), Option\none());
 * ```
 *
 * @template U
 * @param callable(mixed...):U $callback
 * @param (callable(U):bool)|null $isSome
 * @return ($isSome is null ? \Closure(mixed...):Option<U> : ($noneValue is null ? \Closure(mixed...):Option<U> : never))
 * @throws \InvalidArgumentException if both `$noneValue` and `$isSome` are given
 */
function ify(callable $callback, mixed $noneValue = null, bool $strict = true, ?callable $isSome = null): \Closure
{
    assertNoneValueOrIsSome($noneValue, $isSome);

    // Pick the conversion once here rather than on every call of the returned closure.
    if ($isSome !== null) {
        return static function (mixed ...$args) use ($callback, $isSome): Option {
            $value = $callback(...$args);

            return $isSome($value)
                ? Option\some($value)
                : Option\none();
        };
    }

    if ($strict) {
        return static function (mixed ...$args) use ($callback, $noneValue): Option {
            $value = $callback(...$args);

            return $value === $noneValue
                ? Option\none()
                : Option\some($value);
        };
    }

    return static function (mixed ...$args) use ($callback, $noneValue): Option {
        $value = $callback(...$args);

        // @mago-expect analysis:possibly-null-operand -- left: `$value` may be null, comparing it to `$noneValue` is the point
        // @mago-expect analysis:mixed-operand -- loose comparison is the point of `$strict = false`
        // @mago-expect lint:identity-comparison
        return $value == $noneValue
            ? Option\none()
            : Option\some($value);
    };
}

/**
 * Wrap a callable into one that transforms its result into an `Option` like `Option\ify()` does
 * but also return `Option\None` if an exception matching `$exceptionClass` was thrown.
 *
 * # Examples
 *
 * Successful execution:
 *
 * ```php
 * self::assertEq(Option\tryIfy(strtolower(...))("FRUITS"), Option\some("fruits"));
 * ```
 *
 * Conversion of `null` to `Option\None`:
 *
 * ```php
 * self::assertEq(Option\tryIfy(fn() => null)(), Option\none());
 * ```
 *
 * Checked Exception:
 *
 * ```php
 * self::assertEq(Option\tryIfy(fn () => new \DateTimeImmutable("nope"))(), Option\none());
 * ```
 *
 * Unchecked Exception:
 *
 * ```php
 * self::assertEq(Option\tryIfy(fn () => 1 / 0)(), Option\none());
 * // @throws DivisionByZeroError Division by zero
 * ```
 *
 * @template U
 * @template E of \Throwable
 * @param callable(mixed...):U $callback
 * @param list<class-string<E>>|class-string<E> $exceptionClass
 * @param (callable(U):bool)|null $isSome
 * @return ($isSome is null ? \Closure(mixed...):Option<U> : ($noneValue is null ? \Closure(mixed...):Option<U> : never))
 * @throws \InvalidArgumentException if both `$noneValue` and `$isSome` are given
 */
function tryIfy(
    callable $callback,
    mixed $noneValue = null,
    bool $strict = true,
    array|string $exceptionClass = \Exception::class,
    ?callable $isSome = null,
): \Closure {
    $ify = Option\ify($callback, $noneValue, $strict, $isSome);

    return static function (mixed ...$args) use ($ify, $exceptionClass): Option {
        try {
            return $ify(...$args);
        } catch (\Throwable $th) {
            if (isOfAnyClass($th, (array) $exceptionClass)) {
                return Option\none();
            }

            // @mago-ignore analysis:unhandled-thrown-type -- rethrowing exceptions not in $exceptionClass is the point
            throw $th;
        }
    };
}

/**
 * Converts from `Option<Option<T>>` to `Option<T>`.
 *
 * # Examples
 *
 * ```php
 * $x = Option\Some("vegetables");
 * self::assertSame(Option\flatten(Option\some($x)), $x);
 * self::assertSame(Option\flatten(Option\some(Option\none())), Option\none());
 * self::assertSame(Option\flatten(Option\none()), Option\none());
 * ```
 *
 * @template U
 * @param Option<Option<U>> $option
 * @return Option<U>
 */
function flatten(Option $option): Option
{
    return $option instanceof Option\None
        ? $option
        // @mago-ignore analysis:unhandled-thrown-type -- unwrap() cannot throw: $option is not None
        : $option->unwrap();
}

/**
 * Unzips an option containing a tuple of two options.
 *
 * If `self` is `Some([a, b])` this method returns `[Some(a), Some(b)]`. Otherwise, `[None, None]` is returned.
 *
 * ```php
 * $x = Option\Some("vegetables");
 * self::assertEq(Option\unzip(Option\some(["a", 2])), [Option\some("a"), Option\some(2)]);
 * self::assertSame(Option\unzip(Option\none()), [Option\none(), Option\none()]);
 * ```
 *
 * @template U
 * @template V
 * @param Option<array{U, V}> $option
 * @return array{Option<U>, Option<V>}
 */
function unzip(Option $option): array
{
    return $option->mapOrElse(static fn (array $a): array => [
        Option\some($a[0]),
        Option\some($a[1]),
    ], static fn (): array => [Option\none(), Option\none()]);
}

/**
 * Transposes an `Option` of a `Result` into a `Result` of an `Option`.
 *
 * `None` will be mapped to `Ok(None)`.
 * `Some(Ok(_))` and `Some(Err(_))` will be mapped to `Ok(Some(_))` and `Err(_)`.
 *
 * ```php
 * use TH\Maybe\Result;
 *
 * self::assertEq(Result\ok(Option\some(4)), Option\transpose(Option\some(Result\ok(4))));
 * self::assertEq(Result\err("meat"), Option\transpose(Option\some(Result\err("meat"))));
 * self::assertEq(Option\transpose(Option\none()), Result\ok(Option\none()));
 * ```
 *
 * @template U
 * @template E
 * @param Option<Result<U, E>> $option
 * @return Result<Option<U>, E>
 */
#[ExamplesSetup(new IgnoreUnusedResults())]
function transpose(Option $option): Result
{
    return $option->mapOrElse(
        static fn (Result $result) => $result->map(Option\some(...)),
        static fn () => Result\ok(Option\none()),
    );
}

/**
 * Takes each element in the iterable: if it is a `None`, no further elements are
 * taken and `None` is returned. Should no `None` occur, a `Some` containing the
 * list of all the values is returned.
 *
 * # Examples
 *
 * ```php
 * self::assertEq(Option\collect([Option\some(1), Option\some(2)]), Option\some([1, 2]));
 * self::assertEq(Option\collect([Option\some(1), Option\none()]), Option\none());
 * self::assertEq(Option\collect([Option\none(), Option\some(2)]), Option\none());
 * self::assertEq(Option\collect([]), Option\some([]));
 * ```
 *
 * @template U
 * @param iterable<Option<U>> $options
 * @return Option<list<U>>
 */
function collect(iterable $options): Option
{
    $values = [];

    foreach ($options as $option) {
        if ($option instanceof Option\None) {
            return Option\none();
        }

        // @mago-ignore analysis:unhandled-thrown-type -- unwrap() cannot throw: $option is not None
        $values[] = $option->unwrap();
    }

    return Option\some($values);
}
