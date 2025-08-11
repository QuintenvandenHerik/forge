<?php

namespace Forge\Commands;

use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate', description: 'Run the database migrations')]
class MigrateCommand extends Command
{
	public function __construct(private PDO $pdo)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Run the given number of migrations', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->ensureMigrationTable();

		$ran = $this->getRanMigrations();
		$migrations = $this->getMigrationFiles();

		$pending = array_diff($migrations, $ran);
		$step = $input->getOption('step');

		if ($step !== null) {
			$pending = array_slice($pending, 0, (int) $step);
		}

		if (empty($pending)) {
			$output->writeln('<info>No pending migrations.</info>');
			return Command::SUCCESS;
		}

		$batch = $this->getNextBatchNumber();

		foreach ($pending as $migration) {
			$output->writeln("<comment>Running:</comment> {$migration}");
			$instance = require $this->migrationPath($migration);
			$instance->up();
			$this->logMigration($migration, $batch);
		}

		$output->writeln('<info>Migrations complete.</info>');
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

	private function getRanMigrations(): array
	{
		return $this->pdo->query("SELECT migration FROM migrations ORDER BY migration")
			->fetchAll(PDO::FETCH_COLUMN) ?: [];
	}

	private function getNextBatchNumber(): int
	{
		$batch = $this->pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
		return $batch ? $batch + 1 : 1;
	}

	private function logMigration(string $migration, int $batch): void
	{
		$stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
		$stmt->execute([$migration, $batch]);
	}

	private function getMigrationFiles(): array
	{
		$files = glob(getcwd() . '/database/migrations/*.php') ?: [];
		return array_map(fn($f) => basename($f), $files);
	}

	private function migrationPath(string $file): string
	{
		return getcwd() . '/database/migrations/' . $file;
	}
}
