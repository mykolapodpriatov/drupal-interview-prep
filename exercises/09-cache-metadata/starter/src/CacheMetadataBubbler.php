<?php

declare(strict_types=1);

namespace Exercises\Kata09\Starter;

/**
 * Bubbles cacheability from child render arrays up into a parent.
 *
 * TODO: implement bubble() to satisfy the kata tests.
 *
 * The `Exercises\Kata09\Tests\CacheableMetadata` and
 * `Exercises\Kata09\Tests\Cache` stubs are available to help.
 *
 * See README.md for the full specification.
 */
final class CacheMetadataBubbler
{
    /**
     * Bubble the cacheability of $children up into $parent.
     *
     * @param array<string, mixed> $parent
     * @param list<array<string, mixed>> $children
     *
     * @return array<string, mixed>
     */
    public function bubble(array $parent, array $children): array
    {
        // TODO: implement this.
        return [];
    }
}
