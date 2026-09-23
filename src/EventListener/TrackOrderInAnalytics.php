<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\OrderCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Reacts to OrderCreated by recording the sale in analytics.
 *
 * Second listener on purpose: adding it requires zero changes to the event,
 * to the publisher, or to SendOrderConfirmationEmail.
 */
#[AsEventListener(event: OrderCreated::class, priority: 10)]
final class TrackOrderInAnalytics
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(OrderCreated $event): void
    {
        $this->logger->info('Analytics: sale recorded', [
            'orderId' => $event->orderId,
            'amountInCents' => $event->amountInCents,
        ]);
    }
}
