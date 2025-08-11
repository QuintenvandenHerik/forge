<?php

namespace Forge\Commands;

use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'migrate:install',
	description: 'Create the migrations table if it does not exist'
)]
class MigrateInstallCommand extends Command
{
	public function __construct(private PDO $pdo)
	{
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL
            )
        ");

		$output->writeln('<info>Migrations table installed successfully.</info>');

		return Command::SUCCESS;
	}
}
