<?php

use function \SpipLeague\Component\Kernel\app;

if (!defined('_ECRIRE_INC_VERSION')) {
	die('Forbidden');
}

/* appeler adminer depuis spip */
$GLOBALS['adminer_racine'] = '';

if (!defined('_ECRIRE_INC_VERSION')) {
	while (!file_exists('ecrire/inc_version.php')) {
		$GLOBALS['adminer_racine'] .= '../';
		chdir('..');
	}

	require_once 'ecrire/inc_version.php';
}

include_spip('inc/autoriser');
if (!autoriser('adminer')) {
	die(_T('avis_acces_interdit'));
}

if (
	!is_array($p = @unserialize($GLOBALS['meta']['plugin']))
	|| !isset($p['ADMINER'])
) {
	die('Plugin Adminer pas actif');
}

include_spip('inc/cookie');
spip_setcookie('spip_adminer', 'on');

// desactiver le logout et remplacer par un retour a l'admin spip
if (isset($_POST['logout'])) {

	spip_setcookie('spip_adminer', '', 0);
	session_name('adminer_sid');
	session_start();
	session_destroy();

	unset($_POST['logout']);
	$redir = $GLOBALS['meta']['adresse_site'] . '/ecrire';
	include_spip('inc/headers');
	redirige_par_entete($redir);
}

if (
	!isset($_COOKIE['adminer_sid'])
	|| (_request('username') === null && !_request('file'))
) {

	// forcer un login sur les valeurs du site
	function adminer_connect_db($host, $port, $login, $pass, $db = '', $type = 'mysql', $prefixe = '', $auth = '')
	{
		$drivers = ['mysql' => 'server', 'sqlite3' => 'sqlite'];
		if ($type !== 'mysql') {
			if (defined('_ROOT_CWD')) {
				include_spip('inc/filtres_mini');
				$dir_base = _ROOT_CWD . _DIR_ETC . 'bases/';
				$dir_base = resolve_path($dir_base);
			} elseif (function_exists('\SpipLeague\Component\Kernel\app')) {
				$dir_base = app()->getEtcDir() . 'bases/';
			} else {
				$dir_base = $GLOBALS['adminer_racine'] . _NOM_PERMANENTS_INACCESSIBLES . 'bases/';
			}

			$db = $dir_base . $db . '.sqlite';
		}
		if (!isset($drivers[$type])) {
			die('Type de base de donn&eacute;es ' . $type . ' non reconnu');
		}
		if ($port) {
			$host .= ':' . $port;
		}
		$_POST['auth'] = array(
			'driver' => $drivers[$type],
			'server' => $host,
			'username' => $login,
			'password' => $pass,
			'db' => $db
		);
	}
	lire_fichier(_FILE_CONNECT, $connect);
	$connect = str_replace('spip_connect_db(', 'adminer_connect_db(', $connect);
	eval('?' . '>' . $connect);
}

session_name('adminer_sid');

if (defined('_ADMINER_VERROUILLER_DB')) {
	$_GET['db'] = $GLOBALS['connexions'][0]['db'];
}

# PHP 8+ : les fonctions désactivées n’existent plus,
# mais Adminer (4.8.1) ne vérifie pas
if (PHP_VERSION_ID >= 80000 && !function_exists('ini_set')) {
	function ini_set() {};
}

/**
 * Ne pas utiliser le répertoire `adminer-plugins/` prévu directement
 * qui est auto-démarré par `adminer.php`, ce qui peut conduire 
 * à ouvrir la porte à n’importe qui non authentifié à SPIP,
 * en accédant directement à l’URL d’adminer.php (et en connaissant les chemins physique du site)
 */
function adminer_object()
{
	// autoloader
	foreach (glob(__DIR__ . '/adminer/plugins/*.php') as $filename) {
		require_once $filename;
	}

	$plugins = pipeline(
		'adminer_plugins',
		[
			'data' => [
				new AdminerLoginSqlite(),
				new AdminerDarkSwitcher(),
				new AdminerAllowedDatabases([$GLOBALS['connexions'][0]['db'] ?? '']),
			],
		]
	);
	
	return new Adminer\Plugins($plugins['data']);
}

require_once __DIR__ . '/adminer.php';