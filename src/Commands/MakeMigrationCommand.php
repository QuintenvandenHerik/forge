<?php

namespace Forge\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'make:migration',
	description: 'Create a new migration file'
)]
class MakeMigrationCommand extends Command
{
	protected function configure(): void
	{
		$this->addArgument('name', InputArgument::REQUIRED, 'The name of the migration');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$name = $input->getArgument('name');

		// Ensure migrations directory exists
		$migrationsPath = getcwd() . '/database/migrations';
		if (!is_dir($migrationsPath)) {
			if (!mkdir($migrationsPath, 0777, true) && !is_dir($migrationsPath)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $migrationsPath));
			}
		}

		// Generate filename with timestamp
		$timestamp = date('Y_m_d_His');
		$fileName = "{$timestamp}_{$name}.php";
		$filePath = $migrationsPath . '/' . $fileName;

		// Guess table name & action
		$tableName = null;
		$action = null;

		if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
			$tableName = $matches[1];
			$action = 'create';
		} elseif (preg_match('/^add_.*_to_(.+)_table$/', $name, $matches)) {
			$tableName = $matches[1];
			$action = 'update';
		}

		// Build migration stub
		$stub = $this->buildStub($name, $tableName, $action);

		// Write file
		file_put_contents($filePath, $stub);

		$output->writeln("<info>Created Migration:</info> database/migrations/{$fileName}");

		return Command::SUCCESS;
	}

	private function buildStub(string $className, ?string $tableName, ?string $action): string
	{
		$classNameStudly = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));

		$tableComment = $tableName
			? "// Table: {$tableName} ({$action})"
			: "// Define your table changes here";

		return <<<PHP
<?php

use Forge\\Database\\Migration;
use Forge\\Database\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        {$tableComment}
    }

    public function down(): void
    {
        // Rollback your changes here
    }
};
PHP;
	}
}