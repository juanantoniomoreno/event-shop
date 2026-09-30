<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\OrderUpdated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Lesson 2.3: the listener that asks the dispatcher to stop the chain.
 *
 * Note where the power lives: this class only *requests* the stop. Whether the
 * request is honoured depends on the EVENT -- it must implement
 * Psr\EventDispatcher\StoppableEventInterface. OrderUpdated does (via Event);
 * OrderCreated does not, and there the call would be a fatal error.
 *
 * The stop is conditional on the payload on purpose: it shows that with
 * propagation stopping the executed SET of listeners is dynamic, not just the
 * order. `debug:event-dispatcher` still lists both listeners.
 */
#[AsEventListener(event: OrderUpdated::class, priority: 10)]
final class StopWhenNothingChanged
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(OrderUpdated $event): void
    {
        if ($event->amountInCents !== 0) {
            $this->logger->info('Update accepted, letting the chain continue', [
                'orderId' => $event->orderId,
            ]);

            return;
        }

        $this->logger->info('Nothing changed: stopping propagation before the remaining listeners', [
            'orderId' => $event->orderId,
        ]);

        $event->stopPropagation();
    }
}
