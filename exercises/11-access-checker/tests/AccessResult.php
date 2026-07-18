<?php

declare(strict_types=1);

namespace Exercises\Kata11\Tests;

/**
 * Minimal stand-in for Drupal\Core\Access\AccessResult.
 *
 * Carries a three-valued verdict (allowed / forbidden / neutral) plus the
 * cacheability metadata that lets Drupal cache the access decision safely:
 * cache contexts, cache tags and a max-age. The fluent setters mirror the
 * real API and return $this so a checker can chain them.
 */
final class AccessResult
{
    public const PERMANENT = -1;

    /** @var list<string> */
    private array $cacheContexts = [];

    /** @var list<string> */
    private array $cacheTags = [];

    private int $cacheMaxAge = self::PERMANENT;

    private function __construct(private readonly string $verdict)
    {
    }

    public static function allowed(): self
    {
        return new self('allowed');
    }

    public static function forbidden(): self
    {
        return new self('forbidden');
    }

    public static function neutral(): self
    {
        return new self('neutral');
    }

    /**
     * Allowed when the condition holds, forbidden otherwise.
     */
    public static function allowedIf(bool $condition): self
    {
        return $condition ? self::allowed() : self::forbidden();
    }

    public function isAllowed(): bool
    {
        return $this->verdict === 'allowed';
    }

    public function isForbidden(): bool
    {
        return $this->verdict === 'forbidden';
    }

    public function isNeutral(): bool
    {
        return $this->verdict === 'neutral';
    }

    /**
     * @param list<string> $contexts
     */
    public function addCacheContexts(array $contexts): self
    {
        $merged = array_values(array_unique([...$this->cacheContexts, ...$contexts]));
        sort($merged);
        $this->cacheContexts = $merged;

        return $this;
    }

    /**
     * @param list<string> $tags
     */
    public function addCacheTags(array $tags): self
    {
        $merged = array_values(array_unique([...$this->cacheTags, ...$tags]));
        sort($merged);
        $this->cacheTags = $merged;

        return $this;
    }

    public function setCacheMaxAge(int $maxAge): self
    {
        $this->cacheMaxAge = $maxAge;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCacheContexts(): array
    {
        return $this->cacheContexts;
    }

    /**
     * @return list<string>
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    public function getCacheMaxAge(): int
    {
        return $this->cacheMaxAge;
    }
}
