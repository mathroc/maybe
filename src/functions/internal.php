<?php declare(strict_types=1);

namespace TH\Maybe\Internal;

/**
 * Check if the type of the given value is any of the passed classes.
 *
 * @template T
 * @param iterable<class-string<T>> $classes
 * @psalm-assert-if-false !T $value
 * @psalm-assert-if-true T $value
 */
function isOfAnyClass(object $value, iterable $classes): bool
{
    foreach ($classes as $class) {
        // @mago-ignore analysis:less-specific-nested-argument-type -- mago widens class-string<T> to class-string<m>
        if (\is_a($value, $class)) {
            return true;
        }
    }

    return false;
}

/**
 * Throws if both `$noneValue` and `$isSome` are given: they are mutually exclusive.
 *
 * @throws \InvalidArgumentException
 */
function assertNoneValueOrIsSome(mixed $noneValue, ?callable $isSome): void
{
    if ($isSome !== null && $noneValue !== null) {
        throw new \InvalidArgumentException('$noneValue and $isSome cannot be used together');
    }
}
