<?php

declare(strict_types=1);

namespace Exercises\Kata09\Tests;

/**
 * Minimal stand-in for Drupal\Core\Cache\CacheableMetadata.
 *
 * A plain, immutable value object that holds the three pieces of
 * cacheability: cache tags, cache contexts, and a max-age. It can extract
 * itself from a render array and render itself back into one, but it does
 * NOT know how to merge — the merge is the exercise (see src/).
 */
final class CacheableMetadata
{
    /**
     * @param list<string> $tags
     * @param list<string> $contexts
     */
    public function __construct(
        public readonly array $tags = [],
        public readonly array $contexts = [],
        public readonly int $maxAge = Cache::PERMANENT,
    ) {
    }

    /**
     * Extract cacheability from a render array's #cache property.
     *
     * A missing #cache, or a missing tags/contexts/max-age within it, means
     * "no constraint contributed".
     *
     * @param array<string, mixed> $build
     */
    public static function fromRenderArray(array $build): self
    {
        $cache = $build['#cache'] ?? [];

        return new self(
            array_values($cache['tags'] ?? []),
            array_values($cache['contexts'] ?? []),
            $cache['max-age'] ?? Cache::PERMANENT,
        );
    }

    /**
     * Render this metadata back into a #cache array.
     *
     * @return array{tags: list<string>, contexts: list<string>, max-age: int}
     */
    public function toCacheArray(): array
    {
        return [
            'tags' => $this->tags,
            'contexts' => $this->contexts,
            'max-age' => $this->maxAge,
        ];
    }
}
