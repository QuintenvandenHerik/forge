<?php

namespace Forge\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate:refresh', description: 'Reset and re-run all migrations')]
class MigrateRefreshCommand extends Command
{
	public function __construct(
		private MigrateRollbackCommand $rollback,
		private MigrateCommand $migrate
	) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('<info>Rolling back all migrations...</info>');
		$this->rollback->execute(
			new \Symfony\Component\Console\Input\ArrayInput(['--step' => PHP_INT_MAX]),
			$output
		);

		$output->writeln('<info>Re-running all migrations...</info>');
		$this->migrate->execute(
			new \Symfony\Component\Console\Input\ArrayInput([]),
			$output
		);

		return Command::SUCCESS;
	}
}
