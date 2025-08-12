<?php

declare(strict_types=1);

namespace Forge\Database\MySqli;

use Closure;
use Forge\Database\Connection as AbstractConnection;
use mysqli;

class Connection extends AbstractConnection
{
	use DetectsConcurrencyErrors,
		DetectsLostConnections
//		Concerns\ManagesTransactions TODO
	;
	
	/**
	 * The active mysqli connection.
	 *
	 * @var \mysqli|\Closure
	 */
	protected $mysqli;
	
	/**
	 * The active mysqli connection used for reads.
	 *
	 * @var \mysqli|\Closure
	 */
	protected $readMysqli;
	
	/**
	 * Create a new database connection instance.
	 *
	 * @param  mysqli|Closure  $mysqli
	 * @param  string  $database
	 * @param  string  $tablePrefix
	 * @param  array  $config
	 */
	public function __construct(mysqli|Closure $mysqli, string $database = '', string $tablePrefix = '', array $config = [])
	{
		parent::__construct($mysqli, $database, $tablePrefix, $config);
		$this->mysqli = $mysqli;
		
		// First we will setup the default properties. We keep track of the DB
		// name we are connected to since it is needed when some reflective
		// type commands are run such as checking whether a table exists.
		$this->database = $database;
		
		$this->tablePrefix = $tablePrefix;
		
		$this->config = $config;
		
		// We need to initialize a query grammar and the query post processors
		// which are both very important parts of the database abstractions
		// so we initialize these to their default values while starting.
		$this->useDefaultQueryGrammar();
		
		$this->useDefaultPostProcessor();
	}
}