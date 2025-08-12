<?php

declare(strict_types=1);

namespace Forge\Database\MySqli;

use Forge\Support\Str;
use mysqli_sql_exception;
use Throwable;
trait DetectsConcurrencyErrors
{
	/**
	 * Determine if the given exception was caused by a concurrency error
	 * such as a deadlock, lock wait timeout, or serialization failure.
	 *
	 * @param  \Throwable  $e
	 * @return bool
	 */
	protected function causedByConcurrencyError(Throwable $e): bool
	{
		// If you're using mysqli, enable exceptions somewhere in bootstrap:
		// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		
		if ($e instanceof mysqli_sql_exception) {
			// In mysqli_sql_exception:
			// - getCode() returns the MySQL error number (e.g. 1213, 1205)
			// - SQLSTATE (e.g. '40001') is available via getSqlState() on modern PHP
			$mysqlErrno = (int) $e->getCode();
			$sqlState = method_exists($e, 'getSqlState')
				? $e->getSqlState()
				: (property_exists($e, 'sqlstate') ? $e->sqlstate : null);
			
			// Serialization/deadlock SQLSTATE
			if ($sqlState === '40001') {
				return true;
			}
			
			// Common MySQL/MariaDB concurrency error numbers
			// 1213 = ER_LOCK_DEADLOCK
			// 1205 = ER_LOCK_WAIT_TIMEOUT
			if (in_array($mysqlErrno, [1213, 1205], true)) {
				return true;
			}
		}
		
		// Fallback: message-based detection (mirrors original list)
		$message = $e->getMessage();
		
		return Str::contains($message, [
			'Deadlock found when trying to get lock',
			'deadlock detected',
			'The database file is locked',
			'database is locked',
			'database table is locked',
			'A table in the database is locked',
			'has been chosen as the deadlock victim',
			'Lock wait timeout exceeded; try restarting transaction',
			'WSREP detected deadlock/conflict and aborted the transaction. Try restarting the transaction',
		]);
	}
}