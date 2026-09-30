<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\OrderCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Lesson 2.4: the SAME reaction that used to live in TrackOrderInAnalytics,
 * only declared in the subscriber style instead of the attribute style.
 *
 * Compare the two declarations:
 *
 *   Listener (before)                        Subscriber (now)
 *   ---------------------------------------  ----------------------------------------------
 *   #[AsEventListener(                       public static function getSubscribedEvents(): array
 *       event: OrderCreated::class,          {
 *       priority: 10,                            return [OrderCreated::class => ['onOrderCreated', 10]];
 *   )]                                       }
 *   public function __invoke(...): void      public function onOrderCreated(...): void
 *
 * Mechanically both end up as EventDispatcher::addListener() registrations, and
 * `debug:event-dispatcher` cannot tell them apart. The difference is *where the
 * declaration lives*, and that is what decides which style to pick.
 *
 * getSubscribedEvents() is STATIC and runs at container-compile time. It is
 * registration metadata, never business logic: it must not depend on runtime state.
 */
final class OrderAnalyticsSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreated::class => ['onOrderCreated', 10],
        ];
    }

    public function onOrderCreated(OrderCreated $event): void
    {
        $this->logger->info('Analytics: sale recorded', [
            'orderId' => $event->orderId,
            'amountInCents' => $event->amountInCents,
        ]);
    }
}
