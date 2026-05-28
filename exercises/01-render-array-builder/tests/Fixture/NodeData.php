<?php

declare(strict_types=1);

namespace Exercises\Kata01\Tests\Fixture;

/**
 * Read-only value object that stands in for a Drupal node.
 *
 * Deliberately framework-free so the kata can be solved without
 * pulling in drupal/core.
 */
final class NodeData
{
    public function __construct(
        public readonly int $id,
        public readonly string $bundle,
        public readonly string $title,
        public readonly string $summary,
        public readonly bool $published,
        public readonly bool $sticky,
        public readonly ?string $authorName,
        public readonly int $createdTimestamp,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBundle(): string
    {
        return $this->bundle;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isSticky(): bool
    {
        return $this->sticky;
    }
}
