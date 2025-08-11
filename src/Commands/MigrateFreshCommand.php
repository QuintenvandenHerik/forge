<?php

namespace Forge\Commands;

use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate:fresh', description: 'Drop all tables and re-run migrations')]
class MigrateFreshCommand extends Command
{
	public function __construct(private PDO $pdo, private MigrateCommand $migrate)
	{
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('<comment>Dropping all tables...</comment>');

		$tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
		foreach ($tables as $table) {
			$this->pdo->exec("DROP TABLE IF EXISTS `$table`");
		}

		$output->writeln('<info>All tables dropped.</info>');

		$output->writeln('<info>Running migrations...</info>');
		$this->migrate->execute(
			new \Symfony\Component\Console\Input\ArrayInput([]),
			$output
		);

		return Command::SUCCESS;
	}
}
