<?php

/**
 * Autoload PSR-4 pour Spip\Plugin\Autoupdate (SPIP 4.4 ne charge pas le composer.json des plugins).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

spl_autoload_register(static function (string $class): void {
	$prefix = 'Spip\\Plugin\\Autoupdate\\';
	if (!str_starts_with($class, $prefix)) {
		return;
	}
	$relative = substr($class, strlen($prefix));
	$file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
	if (is_file($file)) {
		require_once $file;
	}
});
