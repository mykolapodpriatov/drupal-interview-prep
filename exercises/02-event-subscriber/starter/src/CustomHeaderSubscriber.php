<?php

declare(strict_types=1);

namespace Exercises\Kata02\Starter;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Adds an X-Tenant-Id header to responses on allowed routes.
 *
 * TODO: implement.
 */
final class CustomHeaderSubscriber implements EventSubscriberInterface
{
    /**
     * @param list<string> $allowedRoutes
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly array $allowedRoutes,
    ) {
    }

    public function onResponse(ResponseEvent $event): void
    {
        // TODO: implement.
    }

    public static function getSubscribedEvents(): array
    {
        // TODO: subscribe to kernel.response.
        return [];
    }
}
