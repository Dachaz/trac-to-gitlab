<?php

namespace Trac2GitLab;

/**
 * Utilities for lazy java developers
 *
 * @author  dachaz
 */
class Utils {
    public static function startsWith($haystack, $needle) {
		return substr($haystack, 0, strlen($needle)) === $needle;
	}
}
?>