<?php

use Spip\Plugin\Autoupdate\Patch\Status;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// on veut des logs verbeux dans ce plugin, le temps de roder tout le mécanisme
defined('_LOG_FILTRE_GRAVITE') || define('_LOG_FILTRE_GRAVITE', 7);

function sha_file($file_path) {
	if (!file_exists($file_path)) {
		debug_backtrace();
	}
	return hash_file('sha256', $file_path);
}

function autoupdate_patch_ready_dir(?string $hash_inc_version = null): string {
	$dir_autoupdate_ready = _DIR_TMP . 'autoupdate/ready/';
	$hash_inc_version ??= sha_file(_DIR_RESTREINT . 'inc_version.php');
	$dir_patch_ready = $dir_autoupdate_ready . $hash_inc_version . '/';
	return $dir_patch_ready;
}

function autoupdate_patch_trash_dir(?string $hash_inc_version = null): string {
	$dir_autoupdate_trash = _DIR_TMP . 'autoupdate/trash/';
	$hash_inc_version ??= sha_file(_DIR_RESTREINT . 'inc_version.php');
	$dir_patch_trash = $dir_autoupdate_trash . $hash_inc_version . '/';
	return $dir_patch_trash;
}

function autoupdate_patch_ready_lock(): string {
	$dir_patch_ready = autoupdate_patch_ready_dir();
	$file_lock = rtrim($dir_patch_ready, '/') . '.lock';
	return $file_lock;
}

function autoupdate_patch_status($dir_patch_ready = null, $check_trash = false): int {
	$dir_patch_ready ??= autoupdate_patch_ready_dir();
	$file_lock = autoupdate_patch_ready_lock();
	$is_locked = false;
	if (file_exists($file_lock)) {
		if (filemtime($file_lock) > time() - 3 * 60) {
			$is_locked = true;
		} else {
			// on supprime un verrou trop ancien
			@unlink($file_lock);
		}
	}
	$d = $dir_patch_ready;
	if (!is_dir($d) && !$is_locked && $check_trash) {
		$d = autoupdate_patch_trash_dir(basename($dir_patch_ready));
	}
	if (is_dir($d)) {
		if (!file_exists($d . 'manifest.json')) {
			return $is_locked ? Status::PREPARING : Status::UNKNOWN;
		}
		if (file_exists($d . 'finished.txt')) {
			if (file_exists($d . 'upgraded.txt')) {
				return Status::FINISHED_UPGRADED;
			}
			return Status::FINISHED;
		}
		if (file_exists($d . 'patched.txt')) {
			if (file_exists($d . 'errors.txt')) {
				return Status::PATCHED_ERRORS;
			}
			return Status::PATCHED_OK;
		}
		if (file_exists($d . 'rolledback.txt')) {
			return Status::FAILED_ROLLBACK;
		}
		if (file_exists($f = $d . 'processing.txt')) {
			if (filemtime($f) > time() - 3 * 60) {
				return Status::PROCESSING;
			}
			return Status::FAILED;
		}
		return Status::READY;
	} else {
		if ($is_locked) {
			@unlink($file_lock);
			$is_locked = false;
		}
	}

	return $is_locked ? Status::PREPARING : Status::NONE;
}

/**
 * Choisit la cible de patch la plus récente dans un objet `partial` de l'API `/3/{from}`.
 *
 * @param array<string, array{url?: string, sha256?: string}> $partial
 * @return array{to: string, url_patch: string, hash: string}|null
 */
function autoupdate_pick_partial_target(array $partial): ?array {
	if ($partial === []) {
		return null;
	}
	$versions = array_keys($partial);
	usort($versions, 'version_compare');
	$to = end($versions);
	if (!is_string($to) || empty($partial[$to]['url']) || empty($partial[$to]['sha256'])) {
		return null;
	}

	return [
		'to' => $to,
		'url_patch' => $partial[$to]['url'],
		'hash' => $partial[$to]['sha256'],
	];
}

/**
 * Trouver une URL de patch disponible pour la version en cours
 */
function autoupdate_urls_patch(string $version, ?string $rollback_to = null): ?array {

	include_spip('genie/mise_a_jour');
	$infos = info_maj_cache_v(3, false, $version);
	if (empty($infos)) {
		return null;
	}

	$status = $infos['status'] ?? 'ok';
	if ($status === 'invalid_from') {
		spip_log("autoupdate_urls_patch: version installée invalide pour l'API ($version)", 'autoupdate' . _LOG_ERREUR);
		return null;
	}
	if ($status === 'unknown_from') {
		spip_log("autoupdate_urls_patch: version $version absente du catalogue spip.net", 'autoupdate' . _LOG_INFO);
	}

	if (empty($rollback_to)) {
		$partial = is_array($infos['partial'] ?? null) ? $infos['partial'] : [];
		return autoupdate_pick_partial_target($partial);
	}

	$rollback = is_array($infos['rollback'] ?? null) ? $infos['rollback'] : [];
	if (!empty($rollback[$rollback_to]['url']) && !empty($rollback[$rollback_to]['sha256'])) {
		return [
			'to' => $rollback_to,
			'url_patch' => $rollback[$rollback_to]['url'],
			'hash' => $rollback[$rollback_to]['sha256'],
		];
	}

	return null;
}

/**
 * Purge un répertoire de ses fichiers
 *
 * @param string $dir
 *     Chemin du répertoire à purger
 * @param array $options
 *     Tableau des options. Peut être :
 *     - atime : timestamp pour ne supprimer que les fichiers antérieurs  à cette date (via fileatime)
 *     - mtime : timestamp pour ne supprimer que les fichiers antérieurs à cette date (via filemtime)
 *     - limit : nombre maximum de suppressions
 *     - subdir : supprimer les sous-repertoires aussi
 * @return int
 *     Nombre de fichiers supprimés
 */
function autoupdate_purger_repertoire($dir, $options = []) {
	if (!is_dir($dir) || !is_readable($dir)) {
		return 0;
	}

	// sur certains sites on veut absolument garder certains caches référencés dans un CDN
	// on peut donc inhiber la purge de ces répertoires pour eviter tout probleme
	if (file_exists(rtrim($dir, '/') . '/inhib_purger_repertoire.txt')) {
		return 0;
	}

	$handle = opendir($dir);
	if (!$handle) {
		return 0;
	}

	$total = 0;

	while (($fichier = @readdir($handle)) !== false) {
		// Eviter ".", "..", tout le reste on purge
		if (in_array($fichier, ['.', '..'], true)) {
			continue;
		}
		$chemin = "$dir/$fichier";
		if (is_file($chemin)) {
			if (
				(!isset($options['atime']) || @fileatime($chemin) < $options['atime'])
				&& (!isset($options['mtime']) || @filemtime($chemin) < $options['mtime'])
			) {
				@unlink($chemin);
				$total++;
			}
		} else {
			if (is_dir($chemin)) {
				$opts = $options;
				if (isset($options['limit'])) {
					$opts['limit'] = $options['limit'] - $total;
				}
				$total += autoupdate_purger_repertoire($chemin, $opts);
				if (isset($options['subdir']) && $options['subdir']) {
					@rmdir($chemin);
				}
			}
		}

		if (isset($options['limit']) && $total >= $options['limit']) {
			break;
		}
	}
	closedir($handle);

	return $total;
}

function autoupdate_purger_update_precedent_same_hash() {
	$dir_ready = autoupdate_patch_ready_dir();
	$hash = basename($dir_ready);
	foreach ([$dir_ready, autoupdate_patch_trash_dir($hash)] as $d) {
		if (is_dir($d)) {
			autoupdate_purger_repertoire($d, ['subdir' => true]);
			@rmdir($d);
		}
	}
	@unlink(autoupdate_patch_ready_lock());
	@unlink(_DIR_TMP . "autoupdate/progress-$hash.txt");
}

function autoupdate_update_progress(string $status, ?string $hash = null) {
	$hash ??= basename(autoupdate_patch_ready_dir());
	$pre = date('Y-m-d H:i:s : ');
	file_put_contents(_DIR_TMP . "autoupdate/progress-$hash.txt", $pre . trim($status) . "\n", FILE_APPEND);
}

/**
 * Créer récursivement un sous-repertoire via la fonction sous_repertoire() de SPIP
 */
function autoupdate_creer_sous_repertoire($path): string {
	$base = dirname($path);
	$sous = basename($path);
	if (!is_dir($base)) {
		$base = autoupdate_creer_sous_repertoire($base);
	}
	return sous_repertoire($base, $sous);
}
