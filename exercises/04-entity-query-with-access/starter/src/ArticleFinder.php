<?php

declare(strict_types=1);

namespace Exercises\Kata04\Starter;

/**
 * TODO: implement findVisibleArticles().
 */
final class ArticleFinder
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findVisibleArticles(AccountInterface $account, int $page = 1, int $perPage = 10): Result
    {
        // TODO: implement.
        return new Result([], 0, $page, $perPage);
    }
}
