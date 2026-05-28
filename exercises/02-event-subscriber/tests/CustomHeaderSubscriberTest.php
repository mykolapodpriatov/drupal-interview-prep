<?php

declare(strict_types=1);

namespace Exercises\Kata02\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class CustomHeaderSubscriberTest extends TestCase
{
    private function subscriber(string $tenantId = 'tenant-foo', array $routes = ['allowed.route']): EventSubscriberInterface
    {
        $mode = getenv('KATA_MODE') ?: 'starter';
        $class = $mode === 'solution'
            ? \Exercises\Kata02\Solution\CustomHeaderSubscriber::class
            : \Exercises\Kata02\Starter\CustomHeaderSubscriber::class;
        return new $class($tenantId, $routes);
    }

    private function event(string $routeName, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->attributes->set('_route', $routeName);
        $response = new Response('hello');
        return new ResponseEvent($kernel, $request, $requestType, $response);
    }

    public function testSubscribesToKernelResponse(): void
    {
        $subscribed = $this->subscriber()::getSubscribedEvents();
        self::assertArrayHasKey(KernelEvents::RESPONSE, $subscribed);
    }

    public function testAddsHeaderOnAllowedRoute(): void
    {
        $event = $this->event('allowed.route');
        $this->subscriber()->onResponse($event);
        self::assertSame('tenant-foo', $event->getResponse()->headers->get('X-Tenant-Id'));
    }

    public function testDoesNothingOnOtherRoute(): void
    {
        $event = $this->event('different.route');
        $this->subscriber()->onResponse($event);
        self::assertFalse($event->getResponse()->headers->has('X-Tenant-Id'));
    }

    public function testSkipsSubRequests(): void
    {
        $event = $this->event('allowed.route', HttpKernelInterface::SUB_REQUEST);
        $this->subscriber()->onResponse($event);
        self::assertFalse($event->getResponse()->headers->has('X-Tenant-Id'));
    }

    public function testOverwritesExistingHeader(): void
    {
        $event = $this->event('allowed.route');
        $event->getResponse()->headers->set('X-Tenant-Id', 'stale-value');
        $this->subscriber()->onResponse($event);
        self::assertSame('tenant-foo', $event->getResponse()->headers->get('X-Tenant-Id'));
    }

    public function testMissingRouteAttribute(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        // no _route attribute set
        $response = new Response('hello');
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->subscriber()->onResponse($event);
        self::assertFalse($event->getResponse()->headers->has('X-Tenant-Id'));
    }
}
