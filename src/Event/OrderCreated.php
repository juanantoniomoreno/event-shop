<?php

declare(strict_types=1);

namespace App\Event;

/**
 * A fact that already happened: an order was created.
 *
 * Design rules for events:
 *  - Past tense name: it reports, it does not command.
 *  - Immutable: nobody may rewrite history after the fact.
 *  - Minimal payload: whatever is here becomes a public contract with every listener.
 *  - No behaviour: no business logic, no services, no persistence.
 */
final readonly class OrderCreated
{
    public function __construct(
        public string $orderId,
        public int $amountInCents,
    ) {
    }
}
