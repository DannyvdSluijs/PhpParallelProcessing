<?php

declare(strict_types=1);

namespace PhpParallelProcessing\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:process',
    description: 'Run the main process'
)]

class MainCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command runs the main process')
        ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'How many process iterations should be ran?', 10)
        ->addOption('parallel', 'p', InputOption::VALUE_OPTIONAL, 'How many parallel processes should be used?', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $iterations = (int) $input->getOption('iterations');
        $parallel = (int) $input->getOption('parallel');

        $output->writeln('Starting process');

        if ($parallel === 1) {
            $progressBar = new ProgressBar($output, $iterations);
            $progressBar->start();

            for ($x = 0; $x < $iterations; $x++) {
                (new \PhpParallelProcessing\Process\Sleep(1))->__invoke();
                $progressBar->advance();
            }

            $progressBar->finish();
            $output->writeln('');
            $output->writeln('Completed process');
            return Command::SUCCESS;
        }

        $numPerBatch = ceil($iterations / $parallel);
        $progressBar = new ProgressBar($output, $parallel);
        $progressBar->start();
        $childProcs = [];

        $pid = pcntl_fork();

        for ($batch = 0; $batch < $parallel; $batch++) {
            if ($pid === -1) {
                throw new \RuntimeException('Failed to create child process');
            } elseif ($pid) {
                // we are the parent
                $childProcs[$pid] = $pid;

            } else {
                $startAt = ($batch * $numPerBatch);
                if ($startAt >= $iterations) {
                    break;
                }

                $endAt = ($startAt + $numPerBatch);
                if ($endAt > $iterations) {
                    $endAt = $iterations;
                }

                for ($x = $startAt; $x < $endAt; $x++) {
                    (new \PhpParallelProcessing\Process\Sleep(1))->__invoke();
                }
                return Command::SUCCESS;
            }
        }

        $success = true;
        while (count($childProcs) > 0) {
            $pid = pcntl_waitpid(0, $status);
            if ($pid <= 0) {
                continue;
            }

            $childProcessStatus = pcntl_wexitstatus($status);
            if ($childProcessStatus !== 0) {
                $success = false;
            }

            unset($childProcs[$pid]);
            $progressBar->advance();

        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('Completed process');
        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}
