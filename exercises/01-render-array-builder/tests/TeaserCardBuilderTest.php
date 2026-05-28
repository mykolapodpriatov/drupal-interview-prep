<?php

declare(strict_types=1);

namespace Exercises\Kata01\Tests;

use Exercises\Kata01\Tests\Fixture\NodeData;
use PHPUnit\Framework\TestCase;

final class TeaserCardBuilderTest extends TestCase
{
    private function builder(): object
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $class = $mode === 'solution'
            ? \Exercises\Kata01\Solution\TeaserCardBuilder::class
            : \Exercises\Kata01\Starter\TeaserCardBuilder::class;
        return new $class();
    }

    private function sampleNode(array $overrides = []): NodeData
    {
        $defaults = [
            'id' => 42,
            'bundle' => 'article',
            'title' => 'Hello world',
            'summary' => 'A short summary.',
            'published' => true,
            'sticky' => false,
            'authorName' => 'Jane Doe',
            'createdTimestamp' => 1700000000,
        ];
        $args = array_merge($defaults, $overrides);
        return new NodeData(...$args);
    }

    public function testRootIsContainer(): void
    {
        $build = $this->builder()->build($this->sampleNode());
        self::assertSame('container', $build['#type'] ?? null);
    }

    public function testRootHasBaseAndBundleClass(): void
    {
        $build = $this->builder()->build($this->sampleNode());
        $classes = $build['#attributes']['class'] ?? [];
        self::assertContains('teaser-card', $classes);
        self::assertContains('teaser-card--article', $classes);
    }

    public function testStickyAddsClass(): void
    {
        $build = $this->builder()->build($this->sampleNode(['sticky' => true]));
        self::assertContains('is-sticky', $build['#attributes']['class']);
    }

    public function testUnpublishedAddsClassAndTag(): void
    {
        $build = $this->builder()->build($this->sampleNode(['published' => false]));
        self::assertContains('is-unpublished', $build['#attributes']['class']);
        self::assertContains('node_list:unpublished', $build['#cache']['tags']);
    }

    public function testChildrenOrderAndMarkup(): void
    {
        $build = $this->builder()->build($this->sampleNode());
        $childKeys = array_values(array_filter(array_keys($build), fn($k) => !str_starts_with((string)$k, '#')));
        self::assertSame(['title', 'summary', 'meta'], $childKeys);
        self::assertSame('Hello world', $build['title']['#markup']);
        self::assertSame('A short summary.', $build['summary']['#markup']);
    }

    public function testMetaIsContainerWithAuthorAndDate(): void
    {
        $build = $this->builder()->build($this->sampleNode());
        self::assertSame('container', $build['meta']['#type'] ?? null);
        self::assertSame('Jane Doe', $build['meta']['author']['#markup']);
        self::assertSame('2023-11-14', $build['meta']['date']['#markup']);
    }

    public function testAnonymousAuthorFallback(): void
    {
        $build = $this->builder()->build($this->sampleNode(['authorName' => null]));
        self::assertSame('Anonymous', $build['meta']['author']['#markup']);
    }

    public function testCacheMetadata(): void
    {
        $build = $this->builder()->build($this->sampleNode());
        self::assertContains('node:42', $build['#cache']['tags']);
        self::assertContains('node_list:article', $build['#cache']['tags']);
        self::assertContains('user.permissions', $build['#cache']['contexts']);
        self::assertContains('languages:language_interface', $build['#cache']['contexts']);
        self::assertSame(-1, $build['#cache']['max-age']);
    }

    public function testUnpublishedHasNoExtraUnrelatedTags(): void
    {
        $build = $this->builder()->build($this->sampleNode(['published' => false, 'id' => 7]));
        self::assertContains('node:7', $build['#cache']['tags']);
        self::assertContains('node_list:unpublished', $build['#cache']['tags']);
    }
}
