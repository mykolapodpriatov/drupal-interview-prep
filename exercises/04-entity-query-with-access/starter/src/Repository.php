<?php

declare(strict_types=1);

namespace Exercises\Kata04\Starter;

final class Repository
{
    /** @var list<ArticleData> */
    private array $articles;

    /** @param list<ArticleData> $articles */
    public function __construct(array $articles)
    {
        $this->articles = $articles;
    }

    /** @return list<ArticleData> */
    public function all(): array
    {
        return $this->articles;
    }
}
