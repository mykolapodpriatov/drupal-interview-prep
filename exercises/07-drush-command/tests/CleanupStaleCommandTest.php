<?php

declare(strict_types=1);

namespace Exercises\Kata07\Tests;

use PHPUnit\Framework\TestCase;

final class CleanupStaleCommandTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    private function ns(string $tail): string
    {
        $prefix = $this->mode() === 'solution'
            ? 'Exercises\\Kata07\\Solution\\'
            : 'Exercises\\Kata07\\Starter\\';
        return $prefix . $tail;
    }

    private function makeStorage(array $stale): object
    {
        $mock = $this->createMock($this->ns('EntityStorageInterface'));
        $mock->method('findStaleIds')->willReturn($stale);
        return $mock;
    }

    private function makeClock(int $timestamp): object
    {
        $mock = $this->createMock($this->ns('ClockInterface'));
        $mock->method('now')->willReturn((new \DateTimeImmutable())->setTimestamp($timestamp));
        return $mock;
    }

    private function makeCommand(object $storage, object $clock): object
    {
        $cls = $this->ns('CleanupStaleCommand');
        return new $cls($storage, $clock);
    }

    public function testReturnsIdsThatWereDeleted(): void
    {
        $storage = $this->makeStorage([1, 2, 3]);
        $storage->expects(self::once())->method('delete')->with([1, 2, 3]);
        $cmd = $this->makeCommand($storage, $this->makeClock(1_700_000_000));

        $deleted = $cmd->cleanupStale('article', 30);
        self::assertSame([1, 2, 3], $deleted);
    }

    public function testDryRunDoesNotDelete(): void
    {
        $storage = $this->makeStorage([7, 8]);
        $storage->expects(self::never())->method('delete');
        $cmd = $this->makeCommand($storage, $this->makeClock(1_700_000_000));

        $would = $cmd->cleanupStale('article', 30, ['dry_run' => true]);
        self::assertSame([7, 8], $would);
    }

    public function testCutoffTimestampIsCorrect(): void
    {
        $now = 1_700_000_000;
        $expectedCutoff = $now - 30 * 86400;

        $storage = $this->createMock($this->ns('EntityStorageInterface'));
        $storage->expects(self::once())
            ->method('findStaleIds')
            ->with('article', $expectedCutoff)
            ->willReturn([]);
        $storage->expects(self::never())->method('delete');

        $cmd = $this->makeCommand($storage, $this->makeClock($now));
        $cmd->cleanupStale('article', 30);
    }

    public function testThrowsOnEmptyContentType(): void
    {
        $cmd = $this->makeCommand($this->makeStorage([]), $this->makeClock(1));
        $this->expectException(\InvalidArgumentException::class);
        $cmd->cleanupStale('', 30);
    }

    public function testThrowsOnZeroDays(): void
    {
        $cmd = $this->makeCommand($this->makeStorage([]), $this->makeClock(1));
        $this->expectException(\InvalidArgumentException::class);
        $cmd->cleanupStale('article', 0);
    }

    public function testThrowsOnNegativeDays(): void
    {
        $cmd = $this->makeCommand($this->makeStorage([]), $this->makeClock(1));
        $this->expectException(\InvalidArgumentException::class);
        $cmd->cleanupStale('article', -1);
    }

    public function testEmptyResultDoesNotCallDelete(): void
    {
        $storage = $this->makeStorage([]);
        $storage->expects(self::never())->method('delete');
        $cmd = $this->makeCommand($storage, $this->makeClock(1_700_000_000));

        $deleted = $cmd->cleanupStale('article', 30);
        self::assertSame([], $deleted);
    }
}
