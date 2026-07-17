<?php

declare(strict_types=1);

namespace Exercises\Kata09\Solution;

use Exercises\Kata09\Tests\Cache;
use Exercises\Kata09\Tests\CacheableMetadata;

/**
 * Reference implementation of the cache-metadata bubbler.
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
        // Start from the parent's own cacheability, then fold in each child.
        $merged = CacheableMetadata::fromRenderArray($parent);
        foreach ($children as $child) {
            $merged = $this->mergeOne($merged, CacheableMetadata::fromRenderArray($child));
        }

        // Non-#cache keys on the parent are preserved untouched.
        $parent['#cache'] = $merged->toCacheArray();

        return $parent;
    }

    /**
     * Merge two CacheableMetadata into a new, combined one.
     */
    private function mergeOne(CacheableMetadata $a, CacheableMetadata $b): CacheableMetadata
    {
        return new CacheableMetadata(
            $this->union($a->tags, $b->tags),
            $this->union($a->contexts, $b->contexts),
            $this->mergeMaxAge($a->maxAge, $b->maxAge),
        );
    }

    /**
     * Union of two string lists: de-duplicated and sorted for determinism.
     *
     * @param list<string> $a
     * @param list<string> $b
     *
     * @return list<string>
     */
    private function union(array $a, array $b): array
    {
        $merged = array_values(array_unique(array_merge($a, $b)));
        sort($merged);

        return $merged;
    }

    /**
     * The more restrictive of two max-ages.
     *
     * Cache::PERMANENT (-1) means "no time constraint", so it never wins
     * against a finite age; the smaller finite age wins, and 0 (uncacheable)
     * beats everything.
     */
    private function mergeMaxAge(int $a, int $b): int
    {
        if ($a === Cache::PERMANENT) {
            return $b;
        }
        if ($b === Cache::PERMANENT) {
            return $a;
        }

        return min($a, $b);
    }
}
