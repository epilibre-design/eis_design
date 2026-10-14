<?php

use Spip\Plugin\Autoupdate\Patch\Status;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Verifier si une mise a jour est disponible
 *
 * @param int $t
 * @return int
 */
function genie_autoupdate_nettoyer_dist($t) {
	$dir_trash = _DIR_TMP . 'autoupdate/trash/';

	// tous les ready de plus de 1h passent en trash
	$dir_ready = _DIR_TMP . 'autoupdate/ready/';
	if (is_dir($dir_ready)) {
		include_spip('action/autoupdate');
		$subdirs = glob($dir_ready . '*', GLOB_ONLYDIR);
		foreach ($subdirs as $subdir) {
			$purger = false;
			if (!file_exists($subdir . 'manifest.json')) {
				$purger = true;
			} elseif (filemtime($subdir . 'manifest.json') < time() - 3600) {
				$status = autoupdate_patch_status($subdir);
				if (!in_array($status, [Status::PREPARING, Status::READY, Status::PROCESSING])) {
					$purger = true;
				}
			}
			if ($purger) {
				if (!is_dir($dir_trash)) {
					sous_repertoire($dir_trash);
					rename($subdir, $dir_trash . basename($subdir));
					spip_log("genie_autoupdate_nettoyer_dist: passer $subdir en trash/", 'autoupdate' . _LOG_DEBUG);
				}
			}
		}
	}

	// tous les check de plus de 24h passent en trash
	$dir_check = _DIR_TMP . 'autoupdate/check/';
	if (is_dir($dir_check)) {
		include_spip('action/autoupdate');
		$subdirs = glob($dir_check . '*', GLOB_ONLYDIR);
		foreach ($subdirs as $subdir) {
			$subdir = rtrim($subdir, '/') . '/';
			$subsubdirs = glob($subdir . '*', GLOB_ONLYDIR);
			foreach ($subsubdirs as $subsubdir) {
				if (!file_exists($subsubdir . 'manifest-translated.json')
					|| filemtime($subsubdir . 'manifest-translated.json') < time() - 24 * 3600) {
					if (!is_dir($dir_trash)) {
						sous_repertoire($dir_trash);
						rename($subdir, $dir_trash . basename($subdir) . '-' . basename($subsubdir));
						spip_log("genie_autoupdate_nettoyer_dist: passer $subdir en trash/", 'autoupdate' . _LOG_DEBUG);
					}
				}
			}
			if (empty(glob($subdir . '*', GLOB_ONLYDIR))) {
				genie_autoupdate_nettoyer_repertoire($subdir);
			}
		}
	}

	// facile :  on vide tous les trash de plus de 24h
	if (is_dir($dir_trash)) {
		$subdirs = glob($dir_trash . '*', GLOB_ONLYDIR);
		foreach ($subdirs as $subdir) {
			$subdir = rtrim($subdir, '/') . '/';
			if (!file_exists($subdir . 'manifest-translated.json')
			 || filemtime($subdir . 'manifest-translated.json') < time() - 24 * 3600) {
				spip_log("genie_autoupdate_nettoyer_dist: purger $subdir", 'autoupdate' . _LOG_DEBUG);
				genie_autoupdate_nettoyer_repertoire($subdir);
				$hash = basename($subdir);
				@unlink(_DIR_TMP . "autoupdate/progress-{$hash}.txt");
				include_spip('action/autoupdate_report');
				$apercu = autoupdate_fichier_apercu_rapport($hash);
				if ($apercu !== null) {
					@unlink($apercu);
				}
			}
		}
	}

	return 1;
}

function genie_autoupdate_nettoyer_repertoire($dir) {
	include_spip('inc/autoupdate');
	autoupdate_purger_repertoire($dir, ['limit' => 1000, 'subdir' => true]);
	return rmdir($dir);
}
