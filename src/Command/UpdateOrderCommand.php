<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\OrderUpdated;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lesson 2.3 demo emitter.
 *
 * Run it twice to see the executed SET of listeners change:
 *   php bin/console app:order:update 2500 -vv   -> both listeners run
 *   php bin/console app:order:update 0    -vv   -> the second one is skipped
 *
 * Printing isPropagationStopped() after dispatch makes the invisible visible:
 * dispatch() returns the same object, carrying the decision the listeners made.
 *
 * Scaffolding, same as CreateOrderCommand. Kept as a permanent reference for the
 * Lesson 2.3 demo (see App\Event\OrderUpdated).
 */
#[AsCommand(
    name: 'app:order:update',
    description: 'Lesson 2.3 demo: publishes a stoppable OrderUpdated event.',
)]
final class UpdateOrderCommand extends Command
{
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'amount',
            InputArgument::OPTIONAL,
            'New amount in cents; 0 means "nothing changed" and stops propagation',
            '2500',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orderId = 'ORD-'.strtoupper(bin2hex(random_bytes(4)));
        $amountInCents = (int) $input->getArgument('amount');

        $io->text(sprintf('Dispatching OrderUpdated for %s, amount %d cents', $orderId, $amountInCents));

        $event = new OrderUpdated($orderId, $amountInCents);
        $this->dispatcher->dispatch($event);

        $io->text(sprintf(
            'After dispatch: propagation stopped = %s',
            $event->isPropagationStopped() ? 'YES' : 'no',
        ));

        return Command::SUCCESS;
    }
}
