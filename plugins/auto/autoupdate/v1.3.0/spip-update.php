<?php

$fichier_inc_version = __DIR__ . '/ecrire/inc_version.php';

if (!file_exists($fichier_inc_version)) {
	return;
}

// TTL du script à la racine (600 s) : trop vieux = refuse l'accès et s'auto-supprime (HTTP uniquement).
// Ne pas placer ce contrôle avant le test ecrire/inc_version.php : le même fichier vit aussi dans le plugin.
if (PHP_SAPI !== 'cli') {
	$mtime = @filemtime(__FILE__);
	if ($mtime === false || $mtime < time() - 600) {
		http_response_code(403);
		@unlink(__FILE__);
		exit(1);
	}
}

$hash_version = sha_file($fichier_inc_version);

$dir_racine = __DIR__ . '/';
$dir_autoupdate_ready = $dir_racine . 'tmp/autoupdate/ready/';

$dir_patch = $dir_autoupdate_ready . $hash_version . '/';
if (!is_dir($dir_patch)) {
	exit(1);
}

$hash = $_GET['hash'] ?? '';
$file_manifest = $dir_patch . 'manifest-translated.json';
if (!file_exists($file_manifest) || !hash_equals(sha_file($file_manifest), $hash)) {
	update_fail($dir_patch, ["manifest-translated.json n'a pas le bon hash $hash"]);
}

// Rafraîchir le mtime à chaque step valide pour ne pas couper une MAJ longue.
@touch(__FILE__);

$manifest = file_get_contents($dir_patch . 'manifest-translated.json');
if (!$manifest || !($manifest = json_decode($manifest, true))) {
	update_fail($dir_patch, ['Echec lecture manifest-translated.json']);
}

// indiquer qu'on a démarré
@touch($dir_patch . 'processing.txt');

if (PHP_SAPI !== 'cli') {
	defined('_AUTOUPDATE_TIME_OUT') || define('_AUTOUPDATE_TIME_OUT', 20);
	define('_TIME_OUT', $_SERVER['REQUEST_TIME'] + _AUTOUPDATE_TIME_OUT);
}

$errors = update_check_manifest($dir_racine, $manifest, $dir_patch);
if (!empty($errors)) {
	update_fail($dir_patch, $errors);
}

update_log($dir_patch, 'start: on demare spip-update.php step ' . (string) (($_GET['step'] ?? 0) + 1));
$errors = [];
if (file_exists($dir_patch . 'errors.txt')) {
	$errors = file_get_contents($dir_patch . 'errors.txt');
	if ($errors = trim($errors)) {
		$errors = explode("\n", $errors);
	} else {
		$errors = [];
	}
}

$need_rollback = '';
if (file_exists($dir_patch . 'rollingback.txt')) {
	$need_rollback = file_get_contents($dir_patch . 'rollingback.txt');
} else {
	$errors = update_apply_manifest($dir_racine, $manifest, $dir_patch, $need_rollback, $errors);
}
$patched = true;
if ($need_rollback) {
	if (!empty($manifest['rollback'])) {
		$errors[] = 'Echec application du patch, on rollback';
		file_put_contents($dir_patch . 'rollingback.txt', $need_rollback);
		$errors = update_apply_manifest_rollback($dir_racine, $manifest['rollback'], $dir_patch, $need_rollback, $errors);
		@touch($dir_patch . 'rolledback.txt');
	}
	$patched = false;
}
if ($patched) {
	@touch($dir_patch . 'patched.txt');
}
if (!empty($errors)) {
	update_fail($dir_patch, $errors);
}

// Finir et lancer l'action SPIP qui fera la mise à jour de la base si besoin
exit_and_redirect_finish($dir_patch, 0);

function update_write_errors(string $dir_patch, array $errors) {
	if (!empty($errors)) {
		file_put_contents($dir_patch . 'errors.txt', implode("\n", $errors));
	}
}

function sha_file($file_path) {
	return hash_file('sha256', $file_path);
}

function update_fail(string $dir_patch, array $errors) {
	update_write_errors($dir_patch, $errors);
	exit_and_redirect_finish($dir_patch, 2);
}

function exit_and_redirect_finish($dir_patch, $exit_code) {
	if (
		file_exists($dir_patch . 'redirect_finish.txt')
		&& ($url_redirect_finish = file_get_contents($dir_patch . 'redirect_finish.txt'))
	) {
		// attendre le temps nécessaire pour opcode_cache avant de lancer le hit de finalisation...
		spip_attend_invalidation_opcode_cache($dir_patch);
		update_call_url($url_redirect_finish);
	}
	exit($exit_code);
}

function update_restart_next_step_if_timeout(string $dir_patch, array $errors) {
	if (PHP_SAPI === 'cli') {
		return;
	}
	if (time() < _TIME_OUT) {
		return;
	}
	update_write_errors($dir_patch, $errors);
	$step = $_GET['step'] ?? 0;
	$host = $_SERVER['HTTP_HOST'] ?? '';
	$uri = $_SERVER['REQUEST_URI'] ?? '';
	$port = $_ENV['SERVER_PORT'] ?? '80';
	$scheme = $_ENV['REQUEST_SCHEME'] ?? ($port == '443' ? 'https' : 'http');
	if (!$host || !$uri) {
		return;
	}
	if (!in_array($port, [80, 443]) && strpos($host, ':' . $port) === false) {
		$host .= ':' . $port;
	}
	$uri = str_replace('&step=' . (string) $step, '', $uri);
	$step++;
	$uri .= '&step=' . (string) $step;
	$url_restart = $scheme . '://' . $host . $uri;
	update_log($dir_patch, 'timeout: on relance step ' . (string) ($step + 1) . " $url_restart");
	update_call_url($url_restart);
	exit;
}

function update_call_url($url) {
	// setting the curl parameters.
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// cf bug : http://www.php.net/manual/en/function.curl-setopt.php#104597
	curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
	// valeur mini pour que la requete soit lancee : 200
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 400);
	// lancer
	curl_exec($ch);
	if (PHP_VERSION_ID < 80000) {
		curl_close($ch);
	}
}

function update_log(string $dir_patch, string $message) {
	$message = date('Y-m-d H:i:s') . ' : ' . $message . "\n";
	file_put_contents($dir_patch . 'processing.txt', $message, FILE_APPEND);
}

function update_apply_manifest(string $dir_racine, array $manifest, string $dir_patch, &$need_rollback, array $errors) {
	$need_rollback = false;

	// d'abord on ajoute tous les nouveaux fichiers
	if (!empty($manifest['added'])) {
		foreach ($manifest['added'] as $file_path => $hashes) {
			if (!update_replace_file_from_patch($dir_patch, $hashes['to'], $dir_racine . $file_path)) {
				$errors[] = "Echec ajout fichier $file_path depuis le $dir_patch" . $hashes['to'] . '.bin';
			}
			update_restart_next_step_if_timeout($dir_patch, $errors);
		}
	}

	// si il y a des erreurs on arrête là et on demande un rollback,
	// sauf si on est déjà en rollback, dans ce cas on fait tout ce qu'on peut faire
	if (!empty($errors)) {
		$need_rollback = 'added';
		return $errors;
	}

	if (!empty($manifest['changed'])) {
		foreach ($manifest['changed'] as $file_path => $hashes) {
			if ($file_path !== 'ecrire/inc_version.php') {
				if (!update_replace_file_from_patch($dir_patch, $hashes['to'], $dir_racine . $file_path)) {
					$errors[] = "Echec remplacement fichier $file_path par $dir_patch" . $hashes['to'] . '.bin';
				}
			}
			update_restart_next_step_if_timeout($dir_patch, $errors);
		}
	}

	// si il y a des erreurs on arrête là et on demande un rollback
	// sauf si on est déjà en rollback, dans ce cas on fait tout ce qu'on peut faire
	if (!empty($errors)) {
		$need_rollback = 'changed';
		return $errors;
	}

	// en cas d'erreur sur les fichiers deleted, on ne rollback pas, c'est moins grave
	if (!empty($manifest['deleted'])) {
		foreach ($manifest['deleted'] as $file_path => $hashes) {
			$file_delete = $dir_racine . $file_path;
			if (file_exists($file_delete)) {
				$sha_old_file = sha_file($file_delete);
				if (!rename($file_delete, $f = $dir_patch . 'removed/' . $sha_old_file)) {
					@copy($file_delete, $f); // on fait tout pour en garder une copie
					update_log($dir_patch, 'Suppression fichier ' . update_joli_repertoire($file_delete));
					@unlink($file_delete);
				} else {
					update_log($dir_patch, 'Suppression fichier ' . update_joli_repertoire($file_delete) . ' => déplacé vers ' . update_joli_repertoire($f));
				}
				if (file_exists($file_delete)) {
					$errors[] = "Echec suppression fichier $file_path";
				}
			} else {
				update_log($dir_patch, 'Fichier ' . update_joli_repertoire($file_delete) . ' déjà supprimé');
			}
			update_restart_next_step_if_timeout($dir_patch, $errors);
		}
	}

	// et on finit par ecrire/inc_version.php qui doit toujours être dans le patch
	if (isset($manifest['changed'][$file_path = 'ecrire/inc_version.php'])) {
		$hash = $manifest['changed'][$file_path]['to'];
		if (!update_replace_file_from_patch($dir_patch, $hash, $dir_racine . $file_path)) {
			$errors[] = "Echec remplacement fichier $file_path par {$dir_patch}{$hash}.bin";
			$need_rollback = 'all';
		}
	}
	return $errors;
}
function update_joli_repertoire($path) {
	if (strpos($path, __DIR__ . '/') === 0) {
		return substr($path, strlen(__DIR__ . '/'));
	}
	return $path;
}
function update_apply_manifest_rollback(string $dir_racine, array $manifest, string $dir_patch, $need_rollback, array $errors) {
	$dir_rollback = $dir_patch . 'rollback/';
	$dir_removed = $dir_patch . 'removed/';

	switch ($need_rollback) {
		default:
		case 'all':
			// d'abord on remets tous les fichiers qui ont été supprimés
			foreach ($manifest['added'] as $file_path => $hashes) {
				if (!copy($f = $dir_rollback . $hashes['to'] . '.bin', $dir_racine . $file_path)) {
					$errors[] = "rollback: echec restauration fichier supprimé $file_path depuis $f";
				} else {
					update_log($dir_patch, 'rollback: restauration fichier supprimé ' . update_joli_repertoire($dir_racine . $file_path) . ' depuis ' . update_joli_repertoire($f));
				}
				update_restart_next_step_if_timeout($dir_patch, $errors);
			}
			// et on continue
			// no break
		case 'changed':
			foreach ($manifest['changed'] as $file_path => $hashes) {
				if (!copy($f = $dir_rollback . $hashes['to'] . '.bin', $dir_racine . $file_path)) {
					$errors[] = "rollback: echec restauration fichier modifié $file_path depuis $f";
				} else {
					update_log($dir_patch, 'rollback: restauration fichier modifié ' . update_joli_repertoire($dir_racine . $file_path) . ' depuis ' . update_joli_repertoire($f));
					if (strlen($file_path) > 4 && substr($file_path, -4) === '.php') {
						spip_clear_opcode_cache($dir_racine . $file_path);
					}
				}
			}
			update_restart_next_step_if_timeout($dir_patch, $errors);
			// et on continue
			// no break
		case 'added':
			foreach ($manifest['deleted'] as $file_path => $hashes) {
				if (file_exists($dir_racine . $file_path)) {
					$to_delete = null;
					if (!unlink($dir_racine . $file_path)
						&& !rename($dir_racine . $file_path, $dir_removed . 'rollback-' . sha_file($file_path))
						&& !rename($dir_racine . $file_path, $to_delete = $dir_racine . $file_path . '~')
					) {
						$errors[] = "rollback: echec suppression fichier ajouté $file_path";
					} else {
						if ($to_delete) {
							@unlink($to_delete);
						}
						update_log($dir_patch, 'rollback: suppression fichier ajouté ' . update_joli_repertoire($dir_racine . $file_path));
						if (strlen($file_path) > 4 && substr($file_path, -4) === '.php') {
							spip_clear_opcode_cache($dir_racine . $file_path);
						}
					}
				} else {
					update_log($dir_patch, 'rollback: pas de fichier ajouté ' . update_joli_repertoire($dir_racine . $file_path) . ' trouvé');
				}
				update_restart_next_step_if_timeout($dir_patch, $errors);
			}
			break;

	}

	return $errors;
}

function update_replace_file_from_patch(string $dir_patch, string $hash_file, string $file_dest) {
	$tmp_file = null;
	if (file_exists($file_dest)) {
		if (sha_file($file_dest) === $hash_file) {
			return true;
		}
		$tmp_file = $file_dest . '~';
		while (file_exists($tmp_file)) {
			$tmp_file .= '~';
		}
		$tmp_file .= date('YmdHis');
		if (!rename($file_dest, $tmp_file) || !file_exists($tmp_file)) {
			update_log($dir_patch, 'Echec rename ' . update_joli_repertoire($file_dest) . ' => ' . update_joli_repertoire($tmp_file));
			return false;
		}
	}
	$file_patch = $dir_patch . $hash_file . '.bin';
	// on fait une copie car le même fichier patch peut servir plusieurs fois
	if (!copy($file_patch, $file_dest)
		|| !file_exists($file_dest)) {
		if ($tmp_file) {
			// tentons de remettre l'ancien fichier
			if (rename($tmp_file, $file_dest) || copy($tmp_file, $file_dest)) {
				@unlink($tmp_file);
				update_log($dir_patch, 'Echec remplacement ' . update_joli_repertoire($file_dest) . ', on a remis l\'ancien fichier');
			} else {
				update_log($dir_patch, 'Echec remplacement ' . update_joli_repertoire($file_dest) . ', fichier perdu');
			}
			return false;
		}
	}
	if (strlen($file_dest) > 4 && substr($file_dest, -4) === '.php') {
		spip_clear_opcode_cache($file_dest);
	}
	if ($tmp_file) {
		$sha_tmp_file = sha_file($tmp_file);
		if (!rename($tmp_file, $f = $dir_patch . 'removed/' . $sha_tmp_file . '.bin')) {
			@copy($tmp_file, $f); // on fait tout pour en garder une copie
			update_log($dir_patch, 'Suppression fichier ' . update_joli_repertoire($tmp_file));
			@unlink($tmp_file);
		} else {
			update_log($dir_patch, 'Suppression fichier ' . update_joli_repertoire($tmp_file) . ' => déplacé vers ' . update_joli_repertoire($f));
		}
	}

	if (sha_file($file_dest) !== $hash_file) {
		update_log($dir_patch, 'Echec copie vers ' . update_joli_repertoire($file_dest) . ", le fichier n'a pas le hash attendu $hash_file");
		return false;
	}
	update_log($dir_patch, 'Copie de ' . update_joli_repertoire($dir_patch . $hash_file) . ' vers ' . update_joli_repertoire($file_dest) . ' OK');
	return true;
}

function update_check_manifest(string $dir_racine, array $manifest, string $dir_patch) {
	$errors = [];
	if (!empty($manifest['added'])) {
		foreach ($manifest['added'] as $file_path => $hashes) {
			if (file_exists($dir_racine . $file_path) && sha_file($dir_racine . $file_path) !== $hashes['to']) {
				$errors[] = "Le patch veut ajouter le fichier $file_path déjà présent";
			}
			if (!file_exists($dir_patch . $hashes['to'] . '.bin')) {
				$errors[] = 'Fichier ' . $hashes['to'] . ".bin manquant dans le patch pour ajouter $file_path";
			}
		}
	}
	if (!empty($manifest['changed'])) {
		foreach ($manifest['changed'] as $file_path => $hashes) {
			if (!file_exists($dir_racine . $file_path)) {
				$errors[] = "Le patch veut modifier le fichier $file_path absent";
			} elseif (!is_writable($dir_racine . $file_path)) {
				$errors[] = "Le patch veut modifier le fichier $file_path, mais il n'est pas writeable";
				// TODO : essayer de chmod?
			}
			if (!file_exists($dir_patch . $hashes['to'] . '.bin')) {
				$errors[] = 'Fichier ' . $hashes['to'] . ".bin manquant dans le patch pour remplacer $file_path";
			}
		}
	}
	if (!empty($manifest['deleted'])) {
		foreach ($manifest['deleted'] as $file_path => $hashes) {
			if (!file_exists($dir_racine . $file_path)) {
				// est-ce vraiment une erreur ?
				// $errors[] = "Le patch veut supprimer le fichier $file_path absent";
			} elseif (!is_writable($dir_racine . $file_path)) {
				$errors[] = "Le patch veut supprimer le fichier $file_path, mais il n'est pas writeable";
				// TODO : essayer de chmod?
			}
		}
	}
	return $errors;
}

function spip_opcache_reset() {
	if (function_exists('opcache_reset')) {
		opcache_reset();
	}
}

/**
 * Invalidates a PHP file from any active opcode caches.
 *
 * If the opcode cache does not support the invalidation of individual files,
 * the entire cache will be flushed.
 * kudo : http://cgit.drupalcode.org/drupal/commit/?id=be97f50
 *
 * @param string $filepath
 *   The absolute path of the PHP file to invalidate.
 */
function spip_clear_opcode_cache(string $filepath) {
	clearstatcache(true, $filepath);

	// Zend OPcache
	if (function_exists('opcache_invalidate')) {
		$invalidate = @opcache_invalidate($filepath, true);
		// si l'invalidation a echoue lever un flag
		if (!$invalidate && !defined('_spip_attend_invalidation_opcode_cache')) {
			define('_spip_attend_invalidation_opcode_cache', true);
		}
	} elseif (!defined('_spip_attend_invalidation_opcode_cache')) {
		// n'agira que si opcache est effectivement actif (il semble qu'on a pas toujours la fonction opcache_invalidate)
		define('_spip_attend_invalidation_opcode_cache', true);
	}
	// APC.
	if (function_exists('apc_delete_file')) {
		// apc_delete_file() throws a PHP warning in case the specified file was
		// not compiled yet.
		// @see http://php.net/apc-delete-file
		@apc_delete_file($filepath);
	}
}

/**
 * Attendre l'invalidation de l'opcache
 *
 * Si opcache est actif et en mode `validate_timestamps`,
 * le timestamp du fichier ne sera vérifié qu'après une durée
 * en secondes fixée par `revalidate_freq`.
 *
 * Il faut donc attendre ce temps là pour être sûr qu'on va bien
 * bénéficier de la recompilation du fichier par l'opcache.
 *
 * Ne fait rien en dehors de ce cas
 *
 * @note
 *     C'est une config foireuse déconseillée de opcode cache mais
 *     malheureusement utilisée par Octave.
 * @see http://stackoverflow.com/questions/25649416/when-exactly-does-php-5-5-opcache-check-file-timestamp-based-on-revalidate-freq
 * @see http://wiki.mikejung.biz/PHP_OPcache
 */
function spip_attend_invalidation_opcode_cache(string $dir_patch, $timestamp = null) {
	spip_opcache_reset();
	if (
		function_exists('opcache_get_configuration')
		&& @ini_get('opcache.enable')
		&& @ini_get('opcache.validate_timestamps')
		&& (($duree = intval(@ini_get('opcache.revalidate_freq'))) || $duree = 2)
		&& defined('_spip_attend_invalidation_opcode_cache') // des invalidations ont echouees
	) {
		$wait = $duree + 1;
		if ($timestamp) {
			$wait -= (time() - $timestamp);
			if ($wait < 0) {
				$wait = 0;
			}
		}
		update_log($dir_patch, 'Probleme de configuration opcache.revalidate_freq ' . $duree . 's : on attend ' . $wait . 's');
		if ($wait) {
			sleep($wait);
		}
	}
}
