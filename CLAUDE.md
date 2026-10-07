# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A **teaching project**: a course on event-driven programming in Symfony, built lesson by lesson with the user. It is not a production app. Every class exists to demonstrate one concept, and the docblocks carry the lesson notes, so keep that explanatory style when editing or adding classes.

The source of truth for course state is `odd/tasks/event-driven-course.md`: roadmap (Lessons 0–7), the current lesson's sub-tasks, scope decisions, the delivery log, and the commit ids for each work unit. Read it before starting or resuming any lesson work.

## Stack

- Symfony 7.4 LTS **skeleton only** (console, framework-bundle, dotenv, yaml). PHP >= 8.2 (8.3 used locally).
- No Doctrine, Twig, Monolog, Messenger, or Docker yet. Packages are added only when a lesson needs them: Doctrine in Lesson 5, Messenger in Lesson 6.
- No test suite, PHPUnit, or static analysis is installed. `autoload-dev` maps `App\Tests\` to `tests/`, but that directory does not exist.

## Commands

```bash
composer install                                      # required first; bin/console fails without vendor/
php bin/console lint:container                        # verify DI wiring
php bin/console lint:yaml config                      # verify YAML config
php bin/console debug:event-dispatcher OrderCreated   # REGISTERED listeners, priority and order
php bin/console app:order:create [amountInCents] -vv  # dispatch OrderCreated
php bin/console app:order:update [amountInCents] -vv  # dispatch stoppable OrderUpdated; 0 stops propagation
```

**Always pass `-vv` to the demo commands.** Without Monolog, the framework logger's minimum level comes from `SHELL_VERBOSITY` (no flag = ERROR, `-v` = NOTICE, `-vv` = INFO, `-vvv` = DEBUG). Listeners log at INFO, so without `-vv` they still run but print nothing.

`debug:event-dispatcher` shows which listeners are *registered*. The `-vv` dispatch log shows which ones actually *executed*. With propagation stopping these two sets differ, and the course teaches that difference on purpose.

## Architecture

The four pieces of an event (emitter, event, dispatcher, listener):

- `src/Event/` holds events as immutable facts named in the past tense.
  - `OrderCreated` is a domain fact. It is `final readonly` and extends nothing, so it is **deliberately not stoppable**. Do not make it extend `Event` or implement `StoppableEventInterface`.
  - `OrderUpdated` extends Symfony's `Event` only to demonstrate `stopPropagation()`. It is a permanent teaching reference, not part of the shop domain.
- `src/EventListener/` holds the reactions. They are auto-registered through `autoconfigure: true` and the `App\` resource in `config/services.yaml`, so new listeners need no YAML. Both registration styles exist on purpose for comparison:
  - the attribute style, `#[AsEventListener(event: ..., priority: ...)]` with `__invoke`;
  - the subscriber style, `EventSubscriberInterface` (`OrderAnalyticsSubscriber`). `getSubscribedEvents()` is static and is resolved at container-compile time, so it must never depend on runtime state.
- `src/Command/` holds the console commands. They are **scaffolding emitters** that inject `EventDispatcherInterface` and dispatch. Lesson 3 moves the emitter role into a domain `OrderService`, and `CreateOrderCommand` becomes a thin CLI adapter.

Principles the code demonstrates; new code must not contradict them:
- Listener order at equal priority is discovery order, not a contract. Use priority for infrastructure concerns only, never to sequence business logic.
- An emitter knows nothing about its listeners, and a listener knows nothing about other listeners.

## Lesson 3 constraints (in progress)

- Pure PHP with an **in-memory repository and no Doctrine**. The ORM belongs to Lesson 5.
- The `Order` entity *records* domain events and never holds the dispatcher. `OrderService` persists the order and then releases the recorded events to the dispatcher.

## Working conventions

- Teaching flow: concept first, then code, then a comprehension question. Wait for the user's answer before moving to the next step. Lessons run inline, not delegated, because a comprehension gate sits between steps.
- Conversation is in Spanish (Rioplatense). Code, comments, docs, and commits are in English.
- Each lesson gets its own branch (`course/lesson-N-...`), is fast-forward merged into `main`, and the branch is then deleted. Commits use Conventional Commits, one per work unit, and each lesson closes with a `docs:` commit that records its commit ids in the course doc.
- Compare with the separate `event-driven-orders` project only when the user asks.
