<?php

namespace Forge;

use Forge\Commands\MakeMigrationCommand;
use Forge\Commands\MigrateCommand;
use Forge\Commands\MigrateFreshCommand;
use Forge\Commands\MigrateInstallCommand;
use Forge\Commands\MigrateRefreshCommand;
use Forge\Commands\MigrateRollbackCommand;
use Forge\Commands\MigrateStatusCommand;
use Forge\Foundation\Application as Forge;
use Forge\Support\AppInfo;
use Forge\Commands\AboutCommand;
use PDO;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

require_once __DIR__ . '/Support/helpers.php';

final class ApplicationFactory
{
	/**
	 * @param string $basePath The host application's base path
	 * @param array  $config   Optional app metadata (name, version, author, homepage, env)
	 * @param Command[] $extraCommands Any additional commands to register
	 */
	public static function make(string $basePath, array $config = [], array $extraCommands = []): Application
	{
		$forge = new Forge($basePath);
		
		$info = new AppInfo($basePath, $config);

		$app = new Application($info->name(), $info->version());

		// Built-ins
		$app->add(new AboutCommand($info));
		$app->add(new MakeMigrationCommand());

		// Example: pass PDO connection into migration commands
		$pdo = new PDO('mysql:host=127.0.0.1;dbname=test_forge', 'root', '');

		$app->add($migrate       = new MigrateCommand($pdo));
		$app->add($rollback      = new MigrateRollbackCommand($pdo));
		$app->add($status        = new MigrateStatusCommand($pdo));
		$app->add($refresh       = new MigrateRefreshCommand($rollback, $migrate));
		$app->add($fresh         = new MigrateFreshCommand($pdo, $migrate));
		$app->add($install       = new MigrateInstallCommand($pdo));

		// Host extras
		foreach ($extraCommands as $cmd) {
			$app->add($cmd);
		}

		return $app;
	}
}