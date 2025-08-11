<?php

namespace Forge\Commands;

use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate:status', description: 'Show the status of each migration')]
class MigrateStatusCommand extends Command
{
	public function __construct(private PDO $pdo)
	{
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->ensureMigrationTable();

		$ran = $this->pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN) ?: [];
		$all = glob(getcwd() . '/database/migrations/*.php') ?: [];

		foreach ($all as $file) {
			$migration = basename($file);
			$status = in_array($migration, $ran) ? '<info>Yes</info>' : '<error>No</error>';
			$output->writeln(sprintf("%-70s %s", $migration, $status));
		}

		return Command::SUCCESS;
	}

	private function ensureMigrationTable(): void
	{
		$this->pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL
            )
        ");
	}
}
