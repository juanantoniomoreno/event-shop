# Feature: Event-Driven Course — event-shop

## Goal

Teaching project: learn event-driven programming in Symfony from zero to expert,
built class by class with the user confirming understanding at each step.
Fresh minimal project `event-shop` (Symfony 7.4 LTS, skeleton only — packages
added on demand as lessons need them). The existing advanced project
`event-driven-orders` (Symfony 7 + Messenger + RabbitMQ + Mercure) is NOT used;
user chose a fresh start.

## Stack decisions

- Symfony 7.4 LTS skeleton (no webapp pack; add doctrine/twig/etc. when needed).
- PHP 8.3, Symfony CLI 5.20, Composer.
- Language: user-facing teaching in Spanish (Rioplatense), artifacts in English.
- No Docker, no RabbitMQ until the advanced phase (Lesson 6+).

## Tasks

- [x] Create project skeleton (`symfony new event-shop --version=lts`)
- [x] Lesson 0: course roadmap + conceptual lesson (delivered, comprehension answers reviewed and passed)
- [x] Lesson 1: first custom event `OrderCreated` + two listeners registered with `#[AsEventListener]` (wiring verified; dispatch demo pending)
- [ ] Lesson 2: priorities, propagation stopping, listener vs subscriber (EventSubscriberInterface)
- [ ] Lesson 3: domain — Order entity + state transitions firing domain events
- [ ] Lesson 4: framework events (kernel.request / kernel.response / kernel.terminate)
- [ ] Lesson 5: Doctrine lifecycle events (prePersist, postUpdate, listeners)
- [ ] Lesson 6: Messenger — from synchronous dispatch to async transports + workers
- [ ] Lesson 7 (expert): retries, failure transport, event bus vs command bus, patterns

## Progress log

- Project skeleton created (Symfony 7.4 LTS). `bin/console` boots; `debug:event-dispatcher`
  and `debug:container` available.
- `config/services.yaml`: `parameters:` -> `parameters: {}` to satisfy the Symfony DI
  YAML schema (yaml-language-server false-positive on the empty key). `lint:yaml` and
  `lint:container` pass. Not yet committed (user owns commits).
- Lesson 0 delivered (roadmap + concept: coupling problem, inversion to events, 4-piece
  anatomy, trade-offs). Comprehension quiz answered by the user and reviewed: answers on target,
  with two refinements recorded (past tense = fact vs command; "what you do NOT touch" +
  payload-as-contract). Gate passed.
- Lesson 1 delivered: `src/Event/OrderCreated.php` (final readonly, `orderId` + `amountInCents`),
  `src/EventListener/SendOrderConfirmationEmail.php` and `src/EventListener/TrackOrderInAnalytics.php`,
  both registered with `#[AsEventListener(event: OrderCreated::class)]`. No `config/services.yaml`
  change was needed: `_defaults.autoconfigure: true` plus `App\` resource discovery handle it.
  Verified: `php bin/console lint:container` [OK]; `php bin/console debug:event-dispatcher OrderCreated`
  lists both listeners (Order #1/#2, Priority 0). The second listener exists on purpose, to
  demonstrate Lesson 0 answer #3 (add a class, touch nothing else).
- Lesson 1 comprehension quiz answered by the user and reviewed. Answer 1 (ordering) contained one
  factual error worth recording: the #1/#2 order is NOT alphabetical by contract -- it is container
  discovery/registration order (stable sort on equal priority) and Symfony guarantees nothing about
  it. Answer 2 (`readonly`) was correct; two refinements recorded: `readonly` is shallow (nested
  objects/arrays stay mutable) and domain-event immutability does not apply to Symfony framework
  events, which are mutable extension points (Lesson 4). Lesson 1 gate passed.
- Dispatch demo delivered (Lesson 1 close-out): `src/Command/CreateOrderCommand.php`
  (`app:order:create [amount]`) injects `EventDispatcherInterface` and publishes `OrderCreated`.
  The command is scaffolding; in a real app the emitter is the domain service (Lesson 3). Verified by
  execution, not just registration: `php bin/console app:order:create -vv` prints BOTH listeners.
- Verbosity gotcha recorded: the minimal skeleton has no monolog, so FrameworkBundle's
  `Symfony\Component\HttpKernel\Log\Logger` derives its min level from `SHELL_VERBOSITY`
  (`-v` = NOTICE, `-vv` = INFO, `-vvv` = DEBUG, no flag = ERROR). At default verbosity the
  listeners DO run but print nothing, which is why the demo needs `-vv`.
- The user edited `src/EventListener/TrackOrderInAnalytics.php` (mtime 23:19, after the initial
  Lesson 1 write) adding `priority: 10`, pre-applying the Lesson 2 snippet. Verified effect: the
  debug table now shows it as #1 with Priority 10, and the dispatch log order became
  analytics -> email. The priority part of the Lesson 2 plan is therefore already done by the
  user's own hand; Lesson 2 should start from the *why*, not from the mechanic.
- Ordering evidence captured for teaching: at the initial state BOTH listeners were Priority 0 and
  the table showed email #1 / analytics #2. That table is built from `getListeners()`, which is the
  exact array `EventDispatcher::dispatch()` iterates, so that was the execution order then -- but it
  is discovery order, never a contract. With the explicit `priority: 10` the order became declared
  and stable.

## ⏸️ RESUME POINT (paused after the Lesson 1 dispatch demo)

**State**: Lessons 0 and 1 complete and verified, including real execution of both listeners.
`app:order:create` is the emitter. `TrackOrderInAnalytics` already carries `priority: 10`
(user's edit, verified).

**First action when resuming**: offer Lesson 2. The priority mechanic is already applied, so start
from the *why* and move to the new material.

**Lesson 2 plan (priority part already done)**:
1. Consolidate what the user proved by hand: explicit priority turns an implicit, unguaranteed order
   into a declared one. Table + log evidence already captured above.
2. The architectural point: needing order means the two listeners are coupled. Priority is for
   infrastructure concerns (logging, security, profiling), NOT for business sequencing. A real domain
   sequence is a second event or a state machine, never listener ordering.
3. `stopPropagation()`: only works for events implementing `Psr\EventDispatcher\StoppableEventInterface`
   (`EventDispatcher::callListeners()` checks `isPropagationStopped()`; Symfony's `Event` base class
   implements it, plain objects like `OrderCreated` do not -- and for a domain fact they should not).
   Add a third listener that stops propagation and show the later one being skipped.
4. `EventSubscriberInterface`: rewrite one listener as a subscriber and compare the two styles (a
   subscriber is a static event->method map; it wins when one class listens to several events).

**Uncommitted work**: `odd/tasks/event-driven-course.md`, `config/services.yaml` (`parameters: {}`
fix), `.gitignore`, `src/Event/`, `src/EventListener/`. Base commit `7600a2c` came from `symfony new`.
User owns the commit decision — offer it, don't do it unilaterally.

## Teaching method

- Each lesson: concept first, then code, then a comprehension question; user
  confirms before moving on.
- Quiz the user with short conceptual questions as lessons advance.
- Compare with `event-driven-orders` only when the user asks.
