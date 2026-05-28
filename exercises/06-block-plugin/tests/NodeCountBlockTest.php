<?php

declare(strict_types=1);

namespace Exercises\Kata06\Tests;

use PHPUnit\Framework\TestCase;

final class NodeCountBlockTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    private function ns(string $tail): string
    {
        $prefix = $this->mode() === 'solution'
            ? 'Exercises\\Kata06\\Solution\\'
            : 'Exercises\\Kata06\\Starter\\';
        return $prefix . $tail;
    }

    private function makeStorage(array $countsByBundle): object
    {
        $iface = $this->ns('NodeStorageInterface');
        $mock = $this->createMock($iface);
        $mock->method('countByBundle')->willReturnCallback(
            fn(string $b): int => $countsByBundle[$b] ?? 0,
        );
        $mock->method('knownBundles')->willReturn(array_keys($countsByBundle));
        return $mock;
    }

    private function makeContainer(object $storage): object
    {
        $etmIface = $this->ns('EntityTypeManagerInterface');
        $etm = $this->createMock($etmIface);
        $etm->method('getStorage')->with('node')->willReturn($storage);

        $containerCls = $this->ns('Container');
        /** @var object $c */
        $c = new $containerCls();
        $c->set('entity_type.manager', $etm);
        return $c;
    }

    /** @param array<string, mixed> $configuration */
    private function makeBlock(array $configuration, object $container): object
    {
        $blockCls = $this->ns('NodeCountBlock');
        return $blockCls::create($container, $configuration, 'node_count_block', []);
    }

    public function testCreateInjectsEntityTypeManager(): void
    {
        $container = $this->makeContainer($this->makeStorage(['article' => 3]));
        $block = $this->makeBlock(['content_types' => ['article']], $container);
        $build = $block->build();
        self::assertSame('3', $build['items']['article']['count']['#markup']);
    }

    public function testRendersConfiguredBundlesOnly(): void
    {
        $container = $this->makeContainer($this->makeStorage([
            'article' => 5,
            'page' => 2,
            'event' => 11,
        ]));
        $block = $this->makeBlock(['content_types' => ['article', 'event']], $container);
        $build = $block->build();
        $itemKeys = array_keys($build['items']);
        self::assertSame(['article', 'event'], $itemKeys);
    }

    public function testFallsBackToKnownBundlesWhenConfigEmpty(): void
    {
        $container = $this->makeContainer($this->makeStorage([
            'article' => 5,
            'page' => 2,
        ]));
        $block = $this->makeBlock([], $container);
        $build = $block->build();
        self::assertSame(['article', 'page'], array_keys($build['items']));
    }

    public function testCacheTagsIncludeNodeListPerBundle(): void
    {
        $container = $this->makeContainer($this->makeStorage(['article' => 1, 'page' => 0]));
        $block = $this->makeBlock(['content_types' => ['article', 'page']], $container);
        $build = $block->build();
        self::assertContains('node_list:article', $build['#cache']['tags']);
        self::assertContains('node_list:page', $build['#cache']['tags']);
    }

    public function testCacheContextsIncludeUserPermissions(): void
    {
        $container = $this->makeContainer($this->makeStorage(['article' => 1]));
        $block = $this->makeBlock(['content_types' => ['article']], $container);
        $build = $block->build();
        self::assertContains('user.permissions', $build['#cache']['contexts']);
    }

    public function testRootIsContainer(): void
    {
        $container = $this->makeContainer($this->makeStorage(['article' => 1]));
        $block = $this->makeBlock(['content_types' => ['article']], $container);
        $build = $block->build();
        self::assertSame('container', $build['#type'] ?? null);
    }
}
