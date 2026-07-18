<?php

declare(strict_types=1);

namespace Exercises\Kata10\Tests;

use PHPUnit\Framework\TestCase;

final class StaleEntityCleanupWorkerTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    private function ns(string $tail): string
    {
        $prefix = $this->mode() === 'solution'
            ? 'Exercises\\Kata10\\Solution\\'
            : 'Exercises\\Kata10\\Starter\\';
        return $prefix . $tail;
    }

    private function makeStorage(): object
    {
        return $this->createMock($this->ns('EntityStorageInterface'));
    }

    private function makeWorker(object $storage): object
    {
        $cls = $this->ns('StaleEntityCleanupWorker');
        return new $cls($storage);
    }

    public function testDeletesEntityThatStillExists(): void
    {
        $entity = new \stdClass();
        $storage = $this->makeStorage();
        $storage->method('load')->with(42)->willReturn($entity);
        $storage->expects($this->once())->method('delete')->with($entity);

        $this->makeWorker($storage)->processItem(['entity_id' => 42]);
    }

    public function testSkipsItemWhenEntityAlreadyGone(): void
    {
        $storage = $this->makeStorage();
        $storage->method('load')->with(7)->willReturn(null);
        $storage->expects($this->never())->method('delete');

        $this->makeWorker($storage)->processItem(['entity_id' => 7]);
    }

    public function testRequeuesOnTransientDeleteFailure(): void
    {
        $entity = new \stdClass();
        $storage = $this->makeStorage();
        $storage->method('load')->willReturn($entity);
        $storage->method('delete')->willThrowException(
            new \RuntimeException('storage temporarily unavailable'),
        );

        $this->expectException($this->ns('RequeueException'));
        $this->makeWorker($storage)->processItem(['entity_id' => 99]);
    }

    public function testMalformedItemIsIgnored(): void
    {
        $storage = $this->makeStorage();
        $storage->expects($this->never())->method('delete');

        // No integer entity_id — nothing to do, and no exception.
        $this->makeWorker($storage)->processItem(['nope' => true]);
    }
}
