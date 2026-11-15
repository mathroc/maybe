<?php declare(strict_types=1);

// This file is analysed by Psalm, PHPStan and Mago they are all configured to
// complain if "suppress" annotations are useless so that we know that those
// invalid examples need their "ignore" annotations.
// Annotation with 🙈 are for Psalm / PHPStan / Mago false positives.
// Annotation with 🫥 marks a tool's blind spot: it fails to report an issue on this line that other tools detect.
// The other annotations - with 🎯 - are expected issues that Psalm / PHPStan / Mago should detect.

namespace TH\Maybe\Tests\TypeHinting\result;

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
