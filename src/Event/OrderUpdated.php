<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Lesson 2.3 teaching event, deliberately kept separate from OrderCreated.
 *
 * It extends Symfony's Event base class, and that base class implements
 * Psr\EventDispatcher\StoppableEventInterface -- which is the ONLY reason
 * $event->stopPropagation() exists here at all.
 *
 * Contrast with App\Event\OrderCreated: a domain fact that extends nothing, so
 * it has no stopPropagation() and no isPropagationStopped(). That absence is a
 * design decision, not an oversight (see the Lesson 2.3 notes).
 *
 * This class exists to demonstrate the mechanic. It is not part of the shop domain;
 * the Lesson 2 close-out kept it in the repo as a permanent reference, so the demo
 * still replays with `php bin/console app:order:update 0 -vv`.
 */
final class OrderUpdated extends Event
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $amountInCents,
    ) {
    }
}
