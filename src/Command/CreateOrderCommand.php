<?php

declare(strict_types=1);

namespace App\Command;

use App\Event\OrderCreated;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Lesson 1 dispatch demo: the EMITTER piece of the anatomy.
 *
 * This command is scaffolding. In a real application the emitter is the domain
 * service (see Lesson 3, OrderService), not a console command -- Lesson 6 will
 * move the emitter into the Messenger flow.
 *
 * Note what this class knows: that an order was created, and that a dispatcher
 * exists. It does NOT know that an email or an analytics tracker will react.
 */
#[AsCommand(
    name: 'app:order:create',
    description: 'Publishes an OrderCreated event so the dispatcher fan-out is visible.',
)]
final class CreateOrderCommand extends Command
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
            'Order amount in cents',
            '2500',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orderId = 'ORD-'.strtoupper(bin2hex(random_bytes(4)));
        $amountInCents = (int) $input->getArgument('amount');

        $io->text(sprintf('Publishing OrderCreated for %s, amount %d cents', $orderId, $amountInCents));

        // The entire emitter responsibility: state the fact. Nothing else.
        $this->dispatcher->dispatch(new OrderCreated($orderId, $amountInCents));

        $io->success('OrderCreated published.');

        return Command::SUCCESS;
    }
}
