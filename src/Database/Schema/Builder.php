<?php /** @noinspection PhpUnused */

/** @noinspection StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace Forge\Database\Schema;

use Closure;
use Forge\Database\Connection;
use Forge\Database\Schema\BlueprintInterface as BlueprintContract;
use Forge\Database\Schema\BuilderInterface as BuilderContract;
use InvalidArgumentException;
use LogicException;

;

class Builder implements BuilderContract {
	/**
	 * The database connection instance.
	 *
	 * @var Connection
	 */
	protected Connection $connection; // TODO: To Pure PHP and QueryBuilder Support
	
	/**
	 * The schema grammar instance.
	 *
	 * @var \Forge\Database\Schema\Grammars\Grammar
	 */
	protected Grammar $grammar;
	
	/**
	 * The default string length for migrations.
	 *
	 * @var int|null
	 */
	public static ?int $defaultStringLength = 255;
	
	/**
	 * The default time precision for migrations.
	 */
	public static ?int $defaultTimePrecision = 0;
	
	/**
	 * The default relationship morph key type.
	 *
	 * @var string
	 */
	public static string $defaultMorphKeyType = 'int';
	
	/**
	 * Create a new database Schema manager.
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)// TODO: To Pure PHP and QueryBuilder Support
	{
		//		$this->connection = $connection;
		//		$this->grammar = $connection->getSchemaGrammar();
	}
	
	/**
	 * Set the default string length for migrations.
	 *
	 * @param int $length
	 *
	 * @return void
	 */
	public static function defaultStringLength(int $length): void
	{
		static::$defaultStringLength = $length;
	}
	
	/**
	 * Set the default time precision for migrations.
	 */
	public static function defaultTimePrecision(?int $precision): void
	{
		static::$defaultTimePrecision = $precision;
	}
	
	/**
	 * Set the default morph key type for migrations.
	 *
	 * @param  string  $type
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	public static function defaultMorphKeyType(string $type): void
	{
		if (! in_array($type, ['int', 'uuid', 'ulid'])) {
			throw new InvalidArgumentException("Morph key type must be 'int', 'uuid', or 'ulid'.");
		}
		
		static::$defaultMorphKeyType = $type;
	}
	
	/**
	 * Set the default morph key type for migrations to UUIDs.
	 *
	 * @return void
	 */
	public static function morphUsingUuids(): void {
		static::defaultMorphKeyType('uuid');
	}
	
	/**
	 * Set the default morph key type for migrations to ULIDs.
	 *
	 * @return void
	 */
	public static function morphUsingUlids(): void {
		static::defaultMorphKeyType('ulid');
	}
	
	/**
	 * Create a database in the schema.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function createDatabase(string $name): bool // TODO: To Pure PHP and QueryBuilder Support
	{
		return $this->connection->statement(
			$this->grammar->compileCreateDatabase($name)
		);
	}
	
	/**
	 * Drop a database from the schema if the database exists.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function dropDatabaseIfExists(string $name): bool // TODO: To Pure PHP and QueryBuilder Support
	{
		return $this->connection->statement(
			$this->grammar->compileDropDatabaseIfExists($name)
		);
	}
	
	/**
	 * Get the schemas that belong to the connection.
	 *
	 * @return list<array{name: string, path: string|null, default: bool}>
	 */
	public function getSchemas(): array // TODO: To Pure PHP and QueryBuilder Support
	{
		return $this->connection->getPostProcessor()->processSchemas(
			$this->connection->selectFromWriteConnection($this->grammar->compileSchemas())
		);
	}
	
	/**
	 * Determine if the given table exists.
	 *
	 * @param string $table
	 *
	 * @return bool
	 */
	public function hasTable(string $table): bool // TODO: To Pure PHP and QueryBuilder Support
	{
		[$schema, $table] = $this->parseSchemaAndTable($table);
		
		$table = $this->connection->getTablePrefix().$table;
		
		if ($sql = $this->grammar->compileTableExists($schema, $table)) {
			return (bool) $this->connection->scalar($sql);
		}
		
		foreach ($this->getTables($schema ?? $this->getCurrentSchemaName()) as $value) {
			if (strtolower($table) === strtolower($value['name'])) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Determine if the given view exists.
	 *
	 * @param string $view
	 *
	 * @return bool
	 */
	public function hasView(string $view): bool // TODO: To Pure PHP and QueryBuilder Support
	{
		[$schema, $view] = $this->parseSchemaAndTable($view);
		
		$view = $this->connection->getTablePrefix().$view;
		
		foreach ($this->getViews($schema ?? $this->getCurrentSchemaName()) as $value) {
			if (strtolower($view) === strtolower($value['name'])) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get the tables that belong to the connection.
	 *
	 * @param string|string[]|null $schema
	 *
	 * @return list<array{name: string, schema: string|null, schema_qualified_name: string, size: int|null, comment: string|null, collation: string|null, engine: string|null}>
	 */
	public function getTables(null|array|string $schema = null): array // TODO: To Pure PHP and QueryBuilder Support
	{
		return $this->connection->getPostProcessor()->processTables(
			$this->connection->selectFromWriteConnection($this->grammar->compileTables($schema))
		);
	}
	
	/**
	 * Get the names of the tables that belong to the connection.
	 *
	 * @param string|string[]|null $schema
	 * @param bool $schemaQualified
	 *
	 * @return list<string>
	 */
	public function getTableListing(null|array|string $schema = null, bool $schemaQualified = true): array
	{
		return array_column(
			$this->getTables($schema),
			$schemaQualified ? 'schema_qualified_name' : 'name'
		);
	}
	
	/**
	 * Get the views that belong to the connection.
	 *
	 * @param string|string[]|null $schema
	 *
	 * @return list<array{name: string, schema: string|null, schema_qualified_name: string, definition: string}>
	 */
	public function getViews(null|array|string $schema = null): array // TODO: To Pure PHP and QueryBuilder Support
	{
		return $this->connection->getPostProcessor()->processViews(
			$this->connection->selectFromWriteConnection($this->grammar->compileViews($schema))
		);
	}
	
	/**
	 * Get the user-defined types that belong to the connection.
	 *
	 * @param string|string[]|null $schema
	 *
	 * @return list<array{name: string, schema: string, type: string, type: string, category: string, implicit: bool}>
	 */
	public function getTypes(null|array|string $schema = null): array // TODO: To Pure PHP and QueryBuilder Support
	{
		return $this->connection->getPostProcessor()->processTypes(
			$this->connection->selectFromWriteConnection($this->grammar->compileTypes($schema))
		);
	}
	
	/**
	 * Determine if the given table has a given column.
	 *
	 * @param string $table
	 * @param string $column
	 *
	 * @return bool
	 */
	public function hasColumn(string $table, string $column): bool
	{
		return in_array(strtolower($column), array_map(strtolower(...), $this->getColumnListing($table)), true);
	}
	
	/**
	 * Determine if the given table has given columns.
	 *
	 * @param string $table
	 * @param  array<string>  $columns
	 *
	 * @return bool
	 */
	public function hasColumns(string $table, array $columns): bool
	{
		$tableColumns = array_map(strtolower(...), $this->getColumnListing($table));
		
		foreach ($columns as $column) {
			if (!in_array(strtolower($column), $tableColumns, true)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Execute a table builder callback if the given table has a given column.
	 *
	 * @param  string  $table
	 * @param  string  $column
	 * @param Closure $callback
	 *
	 * @return void
	 */
	public function whenTableHasColumn(string $table, string $column, Closure $callback): void
	{
		if ($this->hasColumn($table, $column)) {
			$this->table($table, fn (BlueprintContract $table) => $callback($table));
		}
	}
	
	/**
	 * Execute a table builder callback if the given table doesn't have a given column.
	 *
	 * @param  string  $table
	 * @param  string  $column
	 * @param Closure $callback
	 *
	 * @return void
	 */
	public function whenTableDoesntHaveColumn(string $table, string $column, Closure $callback): void
	{
		if (! $this->hasColumn($table, $column)) {
			$this->table($table, fn (BlueprintContract $table) => $callback($table));
		}
	}
	
	/**
	 * Get the data type for the given column name.
	 *
	 * @param string $table
	 * @param string $column
	 * @param bool $fullDefinition
	 *
	 * @return string
	 */
	public function getColumnType(string $table, string $column, bool $fullDefinition = false): string
	{
		$columns = $this->getColumns($table);
		
		foreach ($columns as $value) {
			if (strtolower($value['name']) === strtolower($column)) {
				return $fullDefinition ? $value['type'] : $value['type_name'];
			}
		}
		
		throw new InvalidArgumentException("There is no column with name '$column' on table '$table'.");
	}
	
	/**
	 * Get the column listing for a given table.
	 *
	 * @param string $table
	 *
	 * @return list<string>
	 */
	public function getColumnListing(string $table): array
	{
		return array_column($this->getColumns($table), 'name');
	}
	
	/**
	 * Get the columns for a given table.
	 *
	 * @param string $table
	 *
	 * @return list<array{name: string, type: string, type_name: string, nullable: bool, default: mixed, auto_increment: bool, comment: string|null, generation: array{type: string, expression: string|null}|null}>
	 */
	public function getColumns(string $table): array // TODO: To Pure PHP and QueryBuilder Support
	{
		[$schema, $table] = $this->parseSchemaAndTable($table);
		
		$table = $this->connection->getTablePrefix().$table;
		
		return $this->connection->getPostProcessor()->processColumns(
			$this->connection->selectFromWriteConnection(
				$this->grammar->compileColumns($schema, $table)
			)
		);
	}
	
	/**
	 * Get the indexes for a given table.
	 *
	 * @param string $table
	 *
	 * @return list<array{name: string, columns: list<string>, type: string, unique: bool, primary: bool}>
	 */
	public function getIndexes(string $table): array // TODO: To Pure PHP and QueryBuilder Support
	{
		[$schema, $table] = $this->parseSchemaAndTable($table);
		
		$table = $this->connection->getTablePrefix().$table;
		
		return $this->connection->getPostProcessor()->processIndexes(
			$this->connection->selectFromWriteConnection(
				$this->grammar->compileIndexes($schema, $table)
			)
		);
	}
	
	/**
	 * Get the names of the indexes for a given table.
	 *
	 * @param string $table
	 *
	 * @return list<string>
	 */
	public function getIndexListing(string $table): array
	{
		return array_column($this->getIndexes($table), 'name');
	}
	
	/**
	 * Determine if the given table has a given index.
	 *
	 * @param string $table
	 * @param array|string $index
	 * @param string|null $type
	 *
	 * @return bool
	 */
	public function hasIndex(string $table, array|string $index, ?string $type = null): bool
	{
		$type = is_null($type) ? $type : strtolower($type);
		
		foreach ($this->getIndexes($table) as $value) {
			$typeMatches = is_null($type)
				|| ($type === 'primary' && $value['primary'])
				|| ($type === 'unique' && $value['unique'])
				|| $type === $value['type'];
			
			if (($value['name'] === $index || $value['columns'] === $index) && $typeMatches) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get the foreign keys for a given table.
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	public function getForeignKeys(string $table): array  // TODO: To Pure PHP and QueryBuilder Support
	{
		[$schema, $table] = $this->parseSchemaAndTable($table);
		
		$table = $this->connection->getTablePrefix().$table;
		
		return $this->connection->getPostProcessor()->processForeignKeys(
			$this->connection->selectFromWriteConnection(
				$this->grammar->compileForeignKeys($schema, $table)
			)
		);
	}
	
	/**
	 * Modify a table on the schema.
	 *
	 * @param string $table
	 * @param Closure $callback
	 *
	 * @return void
	 */
	public function table(string $table, Closure $callback): void
	{
		$this->build($this->createBlueprint($table, $callback));
	}
	
	/**
	 * Create a new table on the schema.
	 *
	 * @param string $table
	 * @param Closure $callback
	 *
	 * @return void
	 */
	public function create(string $table, Closure $callback): void
	{
		$this->build(tap($this->createBlueprint($table), function (BlueprintContract $blueprint) use ($callback) {
			$blueprint->create();
			
			$callback($blueprint);
		}));
	}
	
	/**
	 * Drop a table from the schema.
	 *
	 * @param string $table
	 *
	 * @return void
	 */
	public function drop(string $table): void
	{
		$this->build(tap($this->createBlueprint($table), function ($blueprint) {
			$blueprint->drop();
		}));
	}
	
	/**
	 * Drop a table from the schema if it exists.
	 *
	 * @param string $table
	 *
	 * @return void
	 */
	public function dropIfExists(string $table): void
	{
		$this->build(tap($this->createBlueprint($table), function ($blueprint) {
			$blueprint->dropIfExists();
		}));
	}
	
	/**
	 * Drop columns from a table schema.
	 *
	 * @param string $table
	 * @param string|array<string> $columns
	 *
	 * @return void
	 */
	public function dropColumns(string $table, array|string $columns): void
	{
		$this->table($table, function (BlueprintContract $blueprint) use ($columns) {
			$blueprint->dropColumn($columns);
		});
	}
	
	/**
	 * Drop all tables from the database.
	 *
	 * @return void
	 *
	 * @throws LogicException
	 */
	public function dropAllTables(): void
	{
		throw new LogicException('This database driver does not support dropping all tables.');
	}
	
	/**
	 * Drop all views from the database.
	 *
	 * @return void
	 *
	 * @throws LogicException
	 */
	public function dropAllViews(): void
	{
		throw new LogicException('This database driver does not support dropping all views.');
	}
	
	/**
	 * Drop all types from the database.
	 *
	 * @return void
	 *
	 * @throws LogicException
	 */
	public function dropAllTypes(): void
	{
		throw new LogicException('This database driver does not support dropping all types.');
	}
	
	/**
	 * Rename a table on the schema.
	 *
	 * @param string $from
	 * @param string $to
	 *
	 * @return void
	 */
	public function rename(string $from, string $to): void
	{
		$this->build(tap($this->createBlueprint($from), function ($blueprint) use ($to) {
			$blueprint->rename($to);
		}));
	}
	
	// Start Missing Other functions
	// public function enableForeignKeyConstraints()
	// public function disableForeignKeyConstraints()
	// End Missing Other functions
	
	/**
	 * Execute the blueprint to build / modify the table.
	 *
	 * @param  BlueprintContract  $blueprint
	 * @return void
	 */
	protected function build(BlueprintContract $blueprint): void
	{
		$blueprint->build();
	}
	
	/**
	 * Create a new command set with a Closure.
	 *
	 * @param  string  $table
	 * @param Closure|null  $callback
	 * @return BlueprintContract
	 */
	protected function createBlueprint(string $table, ?Closure $callback = null): BlueprintContract // TODO: To Pure PHP and QueryBuilder Support
	{
		$connection = $this->connection;
		
		//	if (isset($this->resolver)) {
		//		return call_user_func($this->resolver, $connection, $table, $callback);
		//	}
		
		return new Blueprint($connection, $table, $callback);
		//	return Container::getInstance()->make(\Forge\Database\Schema\Blueprint::class, compact('connection', 'table', 'callback'));
	}
	
	/**
	 * Get the names of the current schemas for the connection.
	 *
	 * @return string[]|null
	 */
	public function getCurrentSchemaListing(): ?array
	{
		return null;
	}
	
	/**
	 * Get the default schema name for the connection.
	 *
	 * @return string|null
	 */
	public function getCurrentSchemaName(): ?string
	{
		return $this->getCurrentSchemaListing()[0] ?? null;
	}
	
	
	/**
	 * Parse the given database object reference and extract the schema and table.
	 *
	 * @param string $reference
	 * @param bool|string|null $withDefaultSchema
	 *
	 * @return array
	 */
	public function parseSchemaAndTable(string $reference, null|bool|string $withDefaultSchema = null): array
	{
		$segments = explode('.', $reference);
		
		if (count($segments) > 2) {
			throw new InvalidArgumentException(
				"Using three-part references is not supported, you may use `Schema::connection('{$segments[0]}')` instead."
			);
		}
		
		$table = $segments[1] ?? $segments[0];
		
		$schema = match (true) {
			isset($segments[1]) => $segments[0],
			is_string($withDefaultSchema) => $withDefaultSchema,
			$withDefaultSchema => $this->getCurrentSchemaName(),
			default => null,
		};
		
		return [$schema, $table];
	}
	// Start Missing Other functions
	// public function getConnection()
	// public function blueprintResolver(Closure $resolver)
	// End Missing Other functions
}
