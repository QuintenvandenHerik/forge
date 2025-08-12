<?php

declare(strict_types=1);

namespace Forge\Support;

class Str {
	/**
	 * Determine if a given string contains a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|iterable<string>  $needles
	 * @param  bool  $ignoreCase
	 * @return bool
	 */
	public static function contains(string $haystack, string|iterable $needles, bool $ignoreCase = false): bool
	{
		if ($ignoreCase) {
			$haystack = mb_strtolower($haystack);
		}
		
		if (! is_iterable($needles)) {
			$needles = (array) $needles;
		}
		
		foreach ($needles as $needle) {
			if ($ignoreCase) {
				$needle = mb_strtolower($needle);
			}
			
			if ($needle !== '' && str_contains($haystack, $needle)) {
				return true;
			}
		}
		
		return false;
	}
}