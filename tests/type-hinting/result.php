<?php declare(strict_types=1);

// This file is analysed by Psalm, PHPStan and Mago they are all configured to
// complain if "suppress" annotations are useless so that we know that those
// invalid examples need their "ignore" annotations.
// Annotation with 🙈 are for Psalm / PHPStan / Mago false positives.
// Annotation with 🫥 marks a tool's blind spot: it fails to report an issue on this line that other tools detect.
// The other annotations - with 🎯 - are expected issues that Psalm / PHPStan / Mago should detect.

namespace TH\Maybe\Tests\TypeHinting\result;

use TH\Maybe\Option;
use TH\Maybe\Result;

/**
 * @param Result<string,int> $result
 * @throws \Throwable
 * @psalm-suppress InvalidReturnType
 */
function test_generic_type(Result $result): int
{
    /** @psalm-suppress RedundantConditionGivenDocblockType 🎯 Docblock type string always contains string */
    // @mago-expect analysis:redundant-condition 🎯
    // @phpstan-ignore-next-line 🎯 Call to function is_string() with string will always evaluate to true.
    if (\is_string($result->unwrap())) {
        // @mago-expect analysis:invalid-return-statement 🎯
        /** @psalm-suppress InvalidReturnStatement 🎯 */
        // @phpstan-ignore-next-line 🎯 Function test_generic_type() should return int but returns string.
        return $result->unwrap();
    }

    // @mago-expect analysis:impossible-type-comparison 🎯 `$result` of type `Ok<string>` can never be `Err`
    // @mago-expect analysis:never-return 🎯
    /** @psalm-suppress NoValue 🎯 This function or method call never returns output */
    // @phpstan-ignore-next-line 🎯 Unreachable statement - code above always terminates.
    return $result->unwrapErr();
}

/**
 * @param Result<int,string> $result
 */
function test_is_ok(Result $result): int
{
    if ($result->isOk()) {
        /** @psalm-suppress MissingThrowsDocblock 🙈 Throwable is thrown but not caught - please either catch or add a @throws annotation */
        return $result->unwrap();
    }

    // 🫥 phpstan: blind spot — misses that unwrap() always throws here (mago flags the dead return, psalm the undocumented throw)
    // @mago-expect analysis:impossible-type-comparison 🎯 `$result` of type `Err<int>` can never be `Ok`
    // @mago-expect analysis:never-return 🎯
    /**
     * @psalm-suppress MissingThrowsDocblock 🎯 Throwable is thrown but not caught - please either catch or add a @throws annotation */
    return $result->unwrap();
}

/**
 * @param Result<int,string> $result
 */
function test_is_none(Result $result): string
{
    if ($result->isErr()) {
        // @mago-ignore analysis:mixed-return-statement 🙈 mago loses E when narrowing via isErr(), sees unwrapErr() as mixed
        /** @psalm-suppress MissingThrowsDocblock 🙈 Throwable is thrown but not caught - please either catch or add a @throws annotation */
        return $result->unwrapErr();
    }

    /** @psalm-suppress MissingThrowsDocblock 🙈 Throwable is thrown but not caught - please either catch or add a @throws annotation */
    return (string) $result->unwrap();
}

/**
 * @return Result<int,\RuntimeException|string>
 * @throws \Throwable
 */
function test_trap_to_err(): Result
{
    return Result\trap(
        static fn (): int => \random_int(min: 0, max: 10),
        exceptionClass: \RuntimeException::class,
        toErr: static fn (int $i): Option => $i > 5 ? Option\some('too big') : Option\none(),
    );
}

/**
 * @return Result<int|false,\RuntimeException|string>
 */
function test_ify_to_err(): Result
{
    $strtotime = Result\ify(
        // @phpstan-ignore-next-line 🙈 `callable(mixed...)` rejects `Closure(string, int|null=)` (https://github.com/phpstan/phpstan/issues/6873)
        \strtotime(...),
        exceptionClass: \RuntimeException::class,
        toErr: static fn (int|false $t): Option => $t === false ? Option\some('invalid date') : Option\none(),
    );

    return $strtotime('2015-09-21 UTC midnight');
}

/**
 * @throws \Throwable
 */
function test_to_err_unwrap_err_type(): string
{
    $result = Result\trap(
        static fn (): int => \random_int(min: 0, max: 10),
        exceptionClass: \RuntimeException::class,
        toErr: static fn (int $i): Option => $i > 5 ? Option\some('too big') : Option\none(),
    );

    if ($result->isOk()) {
        return 'ok';
    }

    // 🫥 psalm: blind spot — misses that the error is `RuntimeException|string`, not `string` (phpstan & mago both flag it)
    // @mago-expect analysis:invalid-return-statement 🎯
    // @phpstan-ignore-next-line 🎯 should return string but returns RuntimeException|string.
    return $result->unwrapErr();
}

/**
 * @throws \Throwable
 */
function test_to_err_must_return_an_option(): void
{
    /** @psalm-suppress InvalidArgument 🎯 on the `toErr` argument below */
    // @phpstan-ignore-next-line 🎯 Unable to resolve the template type F in call to function TH\Maybe\Result\trap
    Result\trap(
        static fn (): int => \random_int(min: 0, max: 10),
        // @mago-expect analysis:invalid-argument 🎯
        // @phpstan-ignore-next-line 🎯 Parameter $toErr of function TH\Maybe\Result\trap expects (callable(int<0, 10>): TH\Maybe\Option<mixed>)|null, Closure(int): (''|'too big') given.
        toErr: static fn (int $i): string => $i > 5 ? 'too big' : '',
    );
}

/**
 * @throws \Throwable
 */
function test_to_err_wrong_parameter_type(): void
{
    Result\trap(
        static fn (): int => \random_int(min: 0, max: 10),
        // 🫥 psalm: blind spot — accepts a `Closure(string)` for an int result (phpstan & mago both flag it)
        // @mago-expect analysis:invalid-argument 🎯
        // @phpstan-ignore-next-line 🎯 Parameter $toErr of function TH\Maybe\Result\trap expects (callable(int<0, 10>): TH\Maybe\Option<string>)|null, Closure(string): TH\Maybe\Option\Some<string> given.
        toErr: static fn (string $s): Option => Option\some($s),
    );
}

/**
 * @return Result<int,\Exception|string>
 * @throws \Throwable
 */
function test_to_err_default_exception_class(): Result
{
    // @mago-ignore analysis:less-specific-return-statement 🙈 mago infers E from its bound (Throwable) instead of the `\Exception::class` default
    return Result\trap(
        static fn (): int => \random_int(min: 0, max: 10),
        toErr: static fn (int $i): Option => $i > 5 ? Option\some('too big') : Option\none(),
    );
}
