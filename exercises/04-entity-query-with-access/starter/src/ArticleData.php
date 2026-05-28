<?php

declare(strict_types=1);

namespace Exercises\Kata04\Starter;

final class ArticleData
{
    public function __construct(
        public readonly int $id,
        public readonly string $bundle,
        public readonly string $title,
        public readonly int $createdTimestamp,
        public readonly bool $published,
        public readonly int $ownerId,
    ) {
    }
}
