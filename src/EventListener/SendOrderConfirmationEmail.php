<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\OrderCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Reacts to OrderCreated by sending a confirmation email.
 *
 * Notes:
 *  - The attribute is enough: autoconfigure + src/ auto-discovery register this class.
 *  - It knows NOTHING about who published the event, nor about the other listeners.
 *  - Its dependencies (logger here, MailerInterface in a real app) are its own.
 */
#[AsEventListener(event: OrderCreated::class)]
final class SendOrderConfirmationEmail
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(OrderCreated $event): void
    {
        $this->logger->info('Order confirmation email queued', [
            'orderId' => $event->orderId,
            'amountInCents' => $event->amountInCents,
        ]);
    }
}
