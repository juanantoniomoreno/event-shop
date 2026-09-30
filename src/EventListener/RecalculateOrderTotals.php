<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\OrderUpdated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Lesson 2.3: the listener that gets skipped.
 *
 * It is a perfectly valid listener, registered and visible in
 * `debug:event-dispatcher OrderUpdated`. It simply will not run when
 * StopWhenNothingChanged stops propagation first.
 *
 * That gap -- listed but not executed -- is the danger of propagation stopping:
 * it makes runtime behaviour non-local and invisible to static inspection.
 */
#[AsEventListener(event: OrderUpdated::class)]
final class RecalculateOrderTotals
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(OrderUpdated $event): void
    {
        $this->logger->info('Recalculating order totals', [
            'orderId' => $event->orderId,
            'amountInCents' => $event->amountInCents,
        ]);
    }
}
