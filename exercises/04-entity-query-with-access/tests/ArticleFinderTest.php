<?php

declare(strict_types=1);

namespace Exercises\Kata04\Tests;

use Exercises\Kata04\Tests\Fixture\FakeAccount;
use PHPUnit\Framework\TestCase;

final class ArticleFinderTest extends TestCase
{
    private function mode(): string
    {
        return getenv('KATA_MODE') ?: 'starter';
    }

    private function articleClass(): string
    {
        return $this->mode() === 'solution'
            ? \Exercises\Kata04\Solution\ArticleData::class
            : \Exercises\Kata04\Starter\ArticleData::class;
    }

    private function newArticle(int $id, int $created, bool $published, int $ownerId = 1): object
    {
        $cls = $this->articleClass();
        return new $cls($id, 'article', "Article #$id", $created, $published, $ownerId);
    }

    private function finder(array $articles): object
    {
        $mode = $this->mode();
        if ($mode === 'solution') {
            $repo = new \Exercises\Kata04\Solution\Repository($articles);
            return new \Exercises\Kata04\Solution\ArticleFinder($repo);
        }
        $repo = new \Exercises\Kata04\Starter\Repository($articles);
        return new \Exercises\Kata04\Starter\ArticleFinder($repo);
    }

    public function testAnonymousSeesOnlyPublished(): void
    {
        $articles = [
            $this->newArticle(1, 100, true),
            $this->newArticle(2, 200, false),
            $this->newArticle(3, 300, true),
        ];
        $account = new FakeAccount(0, ['access content']);
        $result = $this->finder($articles)->findVisibleArticles($account);

        self::assertSame(2, $result->total);
        self::assertSame([3, 1], array_map(fn($a) => $a->id, $result->items));
    }

    public function testNoAccessContentPermissionSeesNothing(): void
    {
        $articles = [
            $this->newArticle(1, 100, true),
            $this->newArticle(2, 200, true),
        ];
        $account = new FakeAccount(0, []);
        $result = $this->finder($articles)->findVisibleArticles($account);
        self::assertSame(0, $result->total);
        self::assertSame([], $result->items);
    }

    public function testBypassSeesEverything(): void
    {
        $articles = [
            $this->newArticle(1, 100, true),
            $this->newArticle(2, 200, false),
            $this->newArticle(3, 300, false, 99),
        ];
        $admin = new FakeAccount(7, ['bypass node access']);
        $result = $this->finder($articles)->findVisibleArticles($admin);
        self::assertSame(3, $result->total);
    }

    public function testOwnerSeesOwnUnpublishedWithPermission(): void
    {
        $articles = [
            $this->newArticle(1, 100, false, 5),
            $this->newArticle(2, 200, false, 5),
            $this->newArticle(3, 300, false, 99),
        ];
        $owner = new FakeAccount(5, ['view own unpublished content']);
        $result = $this->finder($articles)->findVisibleArticles($owner);
        self::assertSame(2, $result->total);
        $ids = array_map(fn($a) => $a->id, $result->items);
        self::assertNotContains(3, $ids);
    }

    public function testSortedByCreatedDescThenIdDesc(): void
    {
        $articles = [
            $this->newArticle(1, 100, true),
            $this->newArticle(2, 200, true),
            $this->newArticle(3, 200, true),
        ];
        $account = new FakeAccount(0, ['access content']);
        $result = $this->finder($articles)->findVisibleArticles($account);
        self::assertSame([3, 2, 1], array_map(fn($a) => $a->id, $result->items));
    }

    public function testPagination(): void
    {
        $articles = [];
        for ($i = 1; $i <= 25; $i++) {
            $articles[] = $this->newArticle($i, $i * 10, true);
        }
        $account = new FakeAccount(0, ['access content']);

        $page1 = $this->finder($articles)->findVisibleArticles($account, 1, 10);
        self::assertSame(25, $page1->total);
        self::assertCount(10, $page1->items);
        self::assertSame(25, $page1->items[0]->id);

        $page3 = $this->finder($articles)->findVisibleArticles($account, 3, 10);
        self::assertCount(5, $page3->items);
        self::assertSame(5, $page3->items[0]->id);
    }

    public function testPaginationClampsPageBelowOne(): void
    {
        $articles = [$this->newArticle(1, 100, true)];
        $account = new FakeAccount(0, ['access content']);
        $result = $this->finder($articles)->findVisibleArticles($account, 0, 10);
        self::assertSame(1, $result->page);
    }

    public function testPerPageClamped(): void
    {
        $articles = [$this->newArticle(1, 100, true)];
        $account = new FakeAccount(0, ['access content']);
        $result = $this->finder($articles)->findVisibleArticles($account, 1, 999);
        self::assertSame(100, $result->perPage);
    }

    public function testPastEndReturnsEmpty(): void
    {
        $articles = [$this->newArticle(1, 100, true)];
        $account = new FakeAccount(0, ['access content']);
        $result = $this->finder($articles)->findVisibleArticles($account, 99, 10);
        self::assertSame([], $result->items);
        self::assertSame(1, $result->total);
    }
}
