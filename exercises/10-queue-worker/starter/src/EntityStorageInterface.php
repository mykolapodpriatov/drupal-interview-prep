<?php

declare(strict_types=1);

namespace Exercises\Kata10\Starter;

/**
 * Minimal stub of an entity storage handler.
 */
interface EntityStorageInterface
{
    /**
     * Load an entity by id, or null if it no longer exists.
     */
    public function load(int $id): ?object;

    /**
     * Delete the given entity. May throw on a transient backend error.
     */
    public function delete(object $entity): void;
}
