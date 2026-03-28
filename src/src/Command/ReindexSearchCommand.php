<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SearchReindexService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:search:reindex', description: 'Rebuild Manticore search index from PostgreSQL orders')]
final class ReindexSearchCommand extends Command
{
    public function __construct(
        private readonly SearchReindexService $reindexService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum number of orders to index in one run',
            '10000',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, min(50000, (int) $input->getOption('limit')));

        try {
            $count = $this->reindexService->reindex($limit);

            $io->success(sprintf('Reindex completed successfully. Indexed %d order(s).', $count));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('Reindex failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
