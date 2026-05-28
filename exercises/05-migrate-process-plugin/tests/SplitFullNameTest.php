<?php

declare(strict_types=1);

namespace Exercises\Kata05\Tests;

use PHPUnit\Framework\TestCase;

final class SplitFullNameTest extends TestCase
{
    private function plugin(): object
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $class = $mode === 'solution'
            ? \Exercises\Kata05\Solution\SplitFullName::class
            : \Exercises\Kata05\Starter\SplitFullName::class;
        return new $class();
    }

    private function executable(): object
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $iface = $mode === 'solution'
            ? \Exercises\Kata05\Solution\MigrateExecutableInterface::class
            : \Exercises\Kata05\Starter\MigrateExecutableInterface::class;
        return $this->createMock($iface);
    }

    private function row(): object
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $cls = $mode === 'solution'
            ? \Exercises\Kata05\Solution\Row::class
            : \Exercises\Kata05\Starter\Row::class;
        return new $cls();
    }

    public function testStandardSplit(): void
    {
        $out = $this->plugin()->transform('Smith, Jane', $this->executable(), $this->row(), 'name');
        self::assertSame(['Jane', 'Smith'], $out);
    }

    public function testWhitespaceTrimmed(): void
    {
        $out = $this->plugin()->transform('  Smith  ,  Jane  ', $this->executable(), $this->row(), 'name');
        self::assertSame(['Jane', 'Smith'], $out);
    }

    public function testOnlyFirstCommaSplits(): void
    {
        $out = $this->plugin()->transform('Smith, Jane, MD', $this->executable(), $this->row(), 'name');
        self::assertSame(['Jane, MD', 'Smith'], $out);
    }

    public function testNoCommaTreatedAsFirstNameOnly(): void
    {
        $out = $this->plugin()->transform('Madonna', $this->executable(), $this->row(), 'name');
        self::assertSame(['Madonna', ''], $out);
    }

    public function testEmptyStringReturnsTwoEmpties(): void
    {
        $out = $this->plugin()->transform('', $this->executable(), $this->row(), 'name');
        self::assertSame(['', ''], $out);
    }

    public function testNullReturnsTwoEmpties(): void
    {
        $out = $this->plugin()->transform(null, $this->executable(), $this->row(), 'name');
        self::assertSame(['', ''], $out);
    }

    public function testNonStringReturnsTwoEmpties(): void
    {
        $out = $this->plugin()->transform(42, $this->executable(), $this->row(), 'name');
        self::assertSame(['', ''], $out);
    }

    public function testMultipleReturnsTrue(): void
    {
        self::assertTrue($this->plugin()->multiple());
    }
}
