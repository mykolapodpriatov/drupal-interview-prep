<?php

declare(strict_types=1);

namespace Exercises\Kata04\Solution;

final class ArticleFinder
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findVisibleArticles(AccountInterface $account, int $page = 1, int $perPage = 10): Result
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $visible = array_values(array_filter(
            $this->repository->all(),
            fn(ArticleData $article): bool => $this->canView($article, $account),
        ));

        usort(
            $visible,
            static function (ArticleData $a, ArticleData $b): int {
                if ($a->createdTimestamp === $b->createdTimestamp) {
                    return $b->id <=> $a->id;
                }
                return $b->createdTimestamp <=> $a->createdTimestamp;
            },
        );

        $total = count($visible);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($visible, $offset, $perPage);

        return new Result(array_values($items), $total, $page, $perPage);
    }

    private function canView(ArticleData $article, AccountInterface $account): bool
    {
        if ($account->hasPermission('bypass node access')) {
            return true;
        }
        if ($article->published && $account->hasPermission('access content')) {
            return true;
        }
        if (
            $article->ownerId === $account->id()
            && $account->hasPermission('view own unpublished content')
        ) {
            return true;
        }
        return false;
    }
}
