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
- [x] Lesson 2: listener ordering, propagation stopping, listener vs subscriber
  - [x] 2.1 Consolidate the priority evidence: implicit discovery order -> declared order (no code)
  - [x] 2.2 Architectural point: needing an order means the listeners are coupled; priority is for
        infrastructure concerns (logging, security, profiling), never business sequencing (no code)
  - [x] 2.3 `stopPropagation()`: live demo of a skipped listener, and why `OrderCreated` must NOT
        implement `StoppableEventInterface` (code)
  - [x] 2.4 `EventSubscriberInterface`: rewrite one listener as a subscriber, compare both styles (code)
  - [x] 2.5 Comprehension quiz, evidence recorded, Lesson 2 close-out
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

## Lesson 2 — delivery log (CLOSED 2026-09-30)

Started 2026-09-24. Environment re-verified before starting: PHP 8.3.33;
`debug:event-dispatcher OrderCreated` still reports `TrackOrderInAnalytics` #1 (Priority 10) and
`SendOrderConfirmationEmail` #2 (Priority 0), so the state matches the Lesson 1 evidence exactly.
Routing note: this lesson is interactive teaching with one or two ~25-line teaching files, so it
runs inline in the parent session; a comprehension gate between every step makes delegation a
context-losing round trip. Revisit if the writes grow beyond teaching stubs.

- 2.1 + 2.2 delivered as explanation (repaso). Consolidation: with equal priority the order is
  container discovery/registration order and Symfony guarantees NOTHING about it; the explicit
  `priority: 10` turned it into a declared order that is visible next to the listener. Architectural
  point: needing an order means the listeners are coupled; priority is for infrastructure concerns
  (logging, security, profiling, tracing) with gaps (10/20/30), never for business sequencing; a real
  sequence is a second event, a single orchestrator/state machine, or (Lesson 6) a Messenger handler.
- 2.1/2.2 comprehension check: answers reviewed. Recorded error (repeat of the Lesson 0 pattern): the
  user answered that Symfony *guarantees* email runs before analytics at equal priority; the correct
  answer is that it guarantees NOTHING there. Refinements recorded: (a) the infrastructure-vs-business
  criterion is not "when it runs" but "whether it depends on another listener's result" --
  infrastructure listeners read the event, not a peer's output, and logging/profiling/tracing *wrap*
  rather than precede; (b) "a second event" and "a single orchestrator" are two independent
  alternatives, not one; the missing half of the answer is *why* they beat `priority` (explicit and
  local, typed and testable, one owner, one transactional story). Gate PASSED on re-check: both answers
  correct (Symfony guarantees nothing at equal priority; the two alternatives win because the sequence
  is explicit/local, has a single owner, and gives one place to decide what happens if a step fails,
  whereas a listener chain gets no rollback). 2.1 and 2.2 closed.
- 2.3 delivered (code): `src/Event/OrderUpdated.php` (`extends Event`, so it implements
  `StoppableEventInterface`), `src/EventListener/StopWhenNothingChanged.php` (priority 10; stops the
  chain when `amountInCents === 0`), `src/EventListener/RecalculateOrderTotals.php` (priority 0; the
  one that gets skipped), `src/Command/UpdateOrderCommand.php` (`app:order:update [amount]`).
  `OrderCreated` and the entire Lesson 1 demo are untouched. The four files are teaching scaffolding;
  at the Lesson 2 close-out the user chose to KEEP them in the repo as a live reference, and the
  comments that called them deletable were updated in the same work unit.
  Evidence: `lint:container` [OK]; `debug:event-dispatcher OrderUpdated` -> #1 StopWhenNothingChanged
  (10), #2 RecalculateOrderTotals (0); `app:order:update 2500 -vv` runs BOTH listeners and reports
  `propagation stopped = no`; `app:order:update 0 -vv` runs only the first and reports
  `propagation stopped = YES` (RecalculateOrderTotals absent) -- listed but not executed. Contrast
  proven: `method_exists(OrderCreated::class,'stopPropagation')` = false and
  `is_subclass_of(OrderCreated::class, StoppableEventInterface)` = false; both are true for
  `OrderUpdated`. Gate passed. Refinements recorded: (a) the mechanism was explained correctly but the
  implication for a reader was omitted -- the dispatcher table is a STATIC listing while execution is
  DYNAMIC and conditional, and nothing outside the stopping listener's body reveals who actually runs;
  (b) the design conclusion was right but the reason was fuzzy -- "a domain fact must run and then let
  business logic through" reuses the sequence mental model, whereas the real reason is that a fact is
  broadcast to every independent listener and none of them may silence the others; that is why stopping
  belongs to chains of responsibility with fallbacks (framework/infrastructure), not to a fact fan-out;
  (c) forward-only, no rollback: answered correctly. 2.3 closed.
- 2.4 delivered (code): `TrackOrderInAnalytics` replaced by
  `src/EventListener/OrderAnalyticsSubscriber.php` (`implements EventSubscriberInterface`;
  `getSubscribedEvents()` -> `[OrderCreated::class => ['onOrderCreated', 10]]`). Scope chosen by the
  user: single-event subscriber, to keep the Lesson 1 tables intact; the multi-event advantage was
  shown as a chat snippet instead of code.
  Evidence: `lint:container` [OK]; `debug:event-dispatcher OrderCreated` -> #1
  `OrderAnalyticsSubscriber::onOrderCreated()` (10), #2 `SendOrderConfirmationEmail::__invoke()` (0) --
  the exact same table as before the swap, which proves the dispatcher cannot tell a subscriber from a
  listener; `app:order:create -vv` still prints both reactions. OrderUpdated demo unaffected (still
  #1 StopWhenNothingChanged 10 / #2 RecalculateOrderTotals 0). Teaching point carried: `getSubscribedEvents()`
  is static and runs at container-compile time -- registration metadata, never runtime state.
  Gate PASSED (2026-09-30; the first attempt needed one correction). First Q2 answer was off-question:
  it described a conditional business flow ('cancelled -> the subscriber stops the flow; otherwise it
  confirms and redirects to payment'), which is (a) not about the declaration style at all -- a
  subscriber stops nothing, propagation control is 2.3 and needs a stoppable event -- and (b) the
  sequence mental model again, already closed in 2.1/2.2. Re-checked answer was correct and precise:
  the subscriber wins when ONE class reacts to SEVERAL events of the same concern, because the map
  centralises the wiring and groups by concern instead of by event; the attribute style wins for one
  reaction to one event. Q3 (why `getSubscribedEvents()` is static) was declared unknown and had to be
  re-taught, then answered correctly in substance: it runs once at container-compile time and the
  registrations get baked into the compiled container, so only constant data belongs there -- no
  service, no `$_ENV`, nothing variable. Refinement recorded on Q3: the failure mode is not always
  'it errors'; a value read there is evaluated once at compile time and FROZEN into the cached
  container, so the real danger is stale, silently wrong wiring until `cache:clear` -- worse than a
  loud error. `#[AsEventListener]` is the same contract with even less freedom. 2.4 closed.
- 2.5 quiz delivered (2026-09-30) in the hybrid format the user chose: 8 fact MC (distractors built
  from the user's own recorded errors) + 3 short-answer (criterion), one per lesson axis. Result: 6/8
  facts, and SA9/SA10 do NOT pass. Gate OPEN; one correction round in progress.
  - MC5 wrong: why `OrderCreated` is not stoppable -> answered "because it does not extend `Event`, and
    that was an oversight". That is the mechanism plus the wrong judgement; the expected answer is the
    broadcast reason (a domain fact reaches independent listeners and none may silence the others).
  - MC6 wrong: one listener in the log of `app:order:update 0 -vv` vs two in the debug table ->
    answered that the second one ran but did not print for lack of `-vvv`. Wrong twice over: `-vv` is
    INFO and `info()` does print (Lesson 1 evidence), and the second listener did not run at all. New
    symptom worth watching: the Lesson 1 verbosity gotcha was over-applied to a case where it does
    not apply.
  - SA9 does not pass: correctly named that priority is not for business logic and that a failure in
    the first listener affects the second; missed the *fragility* dimension (the order can change with
    `cache:clear`, new classes or file order, and it fails SILENTLY). The proposed remedy was wrong --
    a subscriber, which is a declaration-style answer to a sequencing problem. Second occurrence of
    that same confusion (see 2.4 Q2 -- and it was directly corrected there), so it is systematic
    rather than noise. Remedy: a second event or an orchestrator/state machine.
  - SA10 does not pass: the conclusion was right but unexplained and circular (it restated the
    proposal). The missing insight is that `OrderCreated` is a BROADCAST, so stopping it silences every
    later listener, including the email -- a global effect for a local intent. Distinction to record:
    cutting the chain is a decision about OTHERS; not doing the work is a decision about ONESELF. Valid
    placements for the condition: the emitter (do not publish a fact that did not happen -- best) or a
    guard inside the listener's own body. Credit where due: the user did place it in the handler,
    which is one of the two valid spots.
  - SA11 mostly right but incomplete: the "why static" half was correct and well put (at compile time
    there is no object and no `$this`, so it describes the class, not the behaviour of an object), as
    was the placement of the conditional decision in the handler. But the required failure mode was
    omitted -- the same refinement already recorded at the 2.4 gate, which did not stick: it does not
    error, the value is read once and FROZEN into the cached container, so the wiring goes stale and
    silently wrong until `cache:clear`.
  - 2.5 correction round (4 targeted re-checks): ALL FOUR PASSED. 1) stopping on a domain fact
    silences everything after it, concretely the email confirmation; the two valid placements for the
    condition are the emitter (best -- do not publish a fact that did not happen) and a guard in the
    listener's own body. 2) the debug table answers "what is wired" and the log answers "what actually
    fired"; they do not contradict -- one is the registration, the other the result of the execution.
    3) a subscriber does NOT help make analytics depend on the email having succeeded; the user stated
    the rule himself this time (a subscriber is only another way of declaring listeners and does not
    affect flow control) and chose an orchestrator, which is valid -- a second event is the lighter
    option. 4) `getSubscribedEvents()` runs only at container compile time; the read is legal so there
    is no error; the value gets baked in, so removing the env variable afterwards is not detected and
    the old value keeps being used, and only a recompile (`cache:clear`) picks it up; the user also
    noted the inverse case (unset variable compiling to null and then failing). Only causality wobble,
    corrected in chat: the absence of an error has nothing to do with `static` -- `static` explains
    why there is no object, not why nothing errors.
  - **2.5 GATE PASSED. Lesson 2 closed** (2026-09-30). The "subscriber as a comodin" pattern from
    2.4 Q2 / 2.5 SA9 was self-corrected in re-check 3, which is the point of the correction round.

## ⏸️ RESUME POINT (Lesson 2 closed and committed; next up Lesson 3)

**State**: Lessons 0 and 1 complete and verified, including real execution of both listeners.
`app:order:create` is the emitter. `TrackOrderInAnalytics` already carries `priority: 10`
(user's edit, verified). Lesson 2 tasks 2.1-2.4 are DELIVERED AND CLOSED: 2.1/2.2 gates PASSED on
re-check, 2.3 gate PASSED with refinements, 2.4 gate PASSED with refinements (see the delivery log).
2.5 was delivered on 2026-09-30 and its gate PASSED on the correction round, so **Lesson 2 is CLOSED**
(see the delivery log). The close-out decisions were taken on 2026-09-30: the four 2.3 demo files are
kept, and the lesson was committed on a `course/lesson-2-listener-flow` branch plus a fast-forward
merge into `master` (same pattern as Lesson 1).
Working tree CLEAN. Lesson 2 is committed: two code work units (`4f6d235` feat 2.3 demo, `a4afdb1`
refactor 2.4 subscriber) plus this docs commit, all fast-forward merged into `master`.
Next step: **Lesson 3** -- Order entity + state transitions firing domain events.

**Lesson 2 plan (executed; items 1-4 are DONE -- kept as the lesson outline, see the log above)**:
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

**Repo state**: Lessons 0 and 1 are merged into `master` (base `7600a2c` from `symfony new`); the
working tree was clean at the moment of the merge. No remote is configured and nothing has been
pushed.

## Commits (work-unit evidence)

### Lesson 1

| Commit | Work unit |
| --- | --- |
| `d479d92` | `chore: fix services.yaml schema and ignore local runtime state` |
| `e374264` | `feat(events): publish OrderCreated and fan it out to two listeners` |
| `7dab48e` | `docs: record lesson 1 work-unit commit ids` |

All three are on `master`.

### Lesson 2

| Commit | Work unit |
| --- | --- |
| `4f6d235` | `feat(events): add stoppable OrderUpdated and propagation-stopping demo` |
| `a4afdb1` | `refactor(events): declare the analytics reaction as an event subscriber` |
| the commit that adds this table | `docs: record lesson 2 close-out, decisions and work-unit commit ids` |

The two code commits were created on `course/lesson-2-listener-flow` and fast-forward merged into
`master`, then the branch was deleted, so no merge commit exists. The user chose at close-out to KEEP
the four 2.3 demo files instead of discarding them, so that work unit is committed rather than
dropped; the comments that called them deletable were corrected in the same work unit. The `course/lesson-1-event-dispatcher` branch was fast-forward merged into
`master` and deleted at the user's request, so no merge commit exists. Commits were created only
after the user explicitly asked for them.

## Teaching method

- Each lesson: concept first, then code, then a comprehension question; user
  confirms before moving on.
- Quiz the user with short conceptual questions as lessons advance.
- Compare with `event-driven-orders` only when the user asks.
