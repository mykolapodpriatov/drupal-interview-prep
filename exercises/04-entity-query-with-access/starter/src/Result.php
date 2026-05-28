<?php

declare(strict_types=1);

namespace Exercises\Kata04\Starter;

final class Result
{
    /** @param list<ArticleData> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }
}
