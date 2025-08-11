<?php

namespace Forge\Commands;

use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate:rollback', description: 'Rollback the last database migration batch')]
class MigrateRollbackCommand extends Command
{
	public function __construct(private PDO $pdo)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Number of migrations to rollback', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->ensureMigrationTable();

		$step = $input->getOption('step');

		if ($step !== null) {
			// Rollback a given number of migrations (most recent first)
			$stmt = $this->pdo->prepare("SELECT migration FROM migrations ORDER BY batch DESC, migration DESC LIMIT ?");
			$stmt->bindValue(1, (int) $step, PDO::PARAM_INT);
			$stmt->execute();
			$migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
		} else {
			// Rollback last batch
			$batch = $this->pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
			if (!$batch) {
				$output->writeln('<info>No migrations to rollback.</info>');
				return Command::SUCCESS;
			}
			$stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY migration DESC");
			$stmt->execute([$batch]);
			$migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
		}

		foreach ($migrations as $migration) {
			$output->writeln("<comment>Rolling back:</comment> {$migration}");
			$instance = require getcwd() . '/database/migrations/' . $migration;
			$instance->down();
			$this->pdo->prepare("DELETE FROM migrations WHERE migration = ?")->execute([$migration]);
		}

		$output->writeln('<info>Rollback complete.</info>');
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
