<?php

declare(strict_types=1);

namespace Exercises\Kata09\Tests;

use PHPUnit\Framework\TestCase;

final class CacheMetadataBubblerTest extends TestCase
{
    private function bubbler(): object
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $class = $mode === 'solution'
            ? \Exercises\Kata09\Solution\CacheMetadataBubbler::class
            : \Exercises\Kata09\Starter\CacheMetadataBubbler::class;
        return new $class();
    }

    public function testParentOwnCacheIsPreserved(): void
    {
        $parent = ['#cache' => ['tags' => ['config:system.site']]];
        $build = $this->bubbler()->bubble($parent, []);
        self::assertContains('config:system.site', $build['#cache']['tags']);
    }

    public function testTagsAreUnionedDeduplicatedAndSorted(): void
    {
        $parent = ['#cache' => ['tags' => ['node:1']]];
        $children = [
            ['#cache' => ['tags' => ['node:2']]],
            ['#cache' => ['tags' => ['node:1', 'user:5']]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(['node:1', 'node:2', 'user:5'], $build['#cache']['tags']);
    }

    public function testContextsAreUnionedDeduplicatedAndSorted(): void
    {
        $parent = ['#cache' => ['contexts' => ['languages:language_interface']]];
        $children = [
            ['#cache' => ['contexts' => ['user.permissions']]],
            ['#cache' => ['contexts' => ['user.permissions', 'route']]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(
            ['languages:language_interface', 'route', 'user.permissions'],
            $build['#cache']['contexts'],
        );
    }

    public function testMinimumMaxAgeWins(): void
    {
        $parent = ['#cache' => ['max-age' => Cache::PERMANENT]];
        $children = [
            ['#cache' => ['max-age' => 3600]],
            ['#cache' => ['max-age' => 600]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(600, $build['#cache']['max-age']);
    }

    public function testPermanentIsNoConstraint(): void
    {
        $parent = ['#cache' => ['max-age' => Cache::PERMANENT]];
        $children = [
            ['#cache' => ['max-age' => Cache::PERMANENT]],
            ['#cache' => ['tags' => ['node:9']]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(-1, $build['#cache']['max-age']);
    }

    public function testZeroMaxAgeBeatsFiniteAndPermanent(): void
    {
        $parent = ['#cache' => ['max-age' => 900]];
        $children = [
            ['#cache' => ['max-age' => Cache::PERMANENT]],
            ['#cache' => ['max-age' => 0]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(0, $build['#cache']['max-age']);
    }

    public function testChildrenWithoutCacheAreIgnored(): void
    {
        $parent = ['#cache' => ['tags' => ['node:1']]];
        $children = [
            ['#markup' => 'plain, no #cache'],
            ['#cache' => ['tags' => ['node:2']]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(['node:1', 'node:2'], $build['#cache']['tags']);
    }

    public function testNonCacheKeysArePreserved(): void
    {
        $parent = [
            '#type' => 'container',
            'child' => ['#markup' => 'hi'],
            '#cache' => ['tags' => ['node:1']],
        ];
        $build = $this->bubbler()->bubble($parent, [['#cache' => ['tags' => ['node:2']]]]);
        self::assertSame('container', $build['#type']);
        self::assertSame('hi', $build['child']['#markup']);
        self::assertSame(['node:1', 'node:2'], $build['#cache']['tags']);
    }

    public function testResultAlwaysCarriesAllThreeKeys(): void
    {
        $build = $this->bubbler()->bubble([], []);
        self::assertSame([], $build['#cache']['tags']);
        self::assertSame([], $build['#cache']['contexts']);
        self::assertSame(Cache::PERMANENT, $build['#cache']['max-age']);
    }

    public function testFullBubbleAcrossManyChildren(): void
    {
        $parent = [
            '#cache' => [
                'tags' => ['config:system.site'],
                'contexts' => ['languages:language_interface'],
                'max-age' => Cache::PERMANENT,
            ],
        ];
        $children = [
            ['#cache' => ['tags' => ['node:1'], 'contexts' => ['user.permissions'], 'max-age' => 3600]],
            ['#cache' => ['tags' => ['node:2'], 'contexts' => ['user.permissions'], 'max-age' => 1800]],
            ['#cache' => ['tags' => ['node:1', 'node_list']]],
        ];
        $build = $this->bubbler()->bubble($parent, $children);
        self::assertSame(
            ['config:system.site', 'node:1', 'node:2', 'node_list'],
            $build['#cache']['tags'],
        );
        self::assertSame(
            ['languages:language_interface', 'user.permissions'],
            $build['#cache']['contexts'],
        );
        self::assertSame(1800, $build['#cache']['max-age']);
    }
}
