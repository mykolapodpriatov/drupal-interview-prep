<?php

declare(strict_types=1);

namespace Exercises\Kata02\Solution;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Reference implementation.
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
        if (!$event->isMainRequest()) {
            return;
        }
        $routeName = $event->getRequest()->attributes->get('_route');
        if (!is_string($routeName) || !in_array($routeName, $this->allowedRoutes, true)) {
            return;
        }
        $event->getResponse()->headers->set('X-Tenant-Id', $this->tenantId, true);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 10],
        ];
    }
}
