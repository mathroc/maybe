<?php declare(strict_types=1);

namespace TH\Maybe\Tests\Unit\Option;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use TH\Maybe\Option;

final class FinalTest extends TestCase
{
    public function testNoneCannotBeExtended(): void
    {
        Assert::assertTrue((new \ReflectionClass(Option\None::class))->isEnum());
    }

    public function testSomeCannotBeExtended(): void
    {
        Assert::assertTrue((new \ReflectionClass(Option\Some::class))->isFinal());
    }
}
