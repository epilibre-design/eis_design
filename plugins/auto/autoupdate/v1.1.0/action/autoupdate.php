<?php

use Spip\Plugin\Autoupdate\Patch\Status;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/autoupdate');

function action_autoupdate_dist(?string $version = null, ?string $rollback_to = null): ?int {
	$loginit = null;
	if ($version === null) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
		$arg = explode('-', $arg, 2);
		$version = array_shift($arg);
		$rollback_to = empty($arg) ? null : array_shift($arg);
		if (!autoriser('webmestre') || $version !== $GLOBALS['spip_version_branche']) {
			return null;
		}
		// on lance l'update du progress
		$urls = autoupdate_urls_patch($version, $rollback_to);
		if (empty($urls)) {
			return null;
		}
		$loginit = 'Lancement ' . ($rollback_to ? 'du rollback' : 'de la mise à jour') . " de $version vers " . $urls['to'] . '...';
		include_spip('public/aiguiller');
		$redirect = securiser_redirect_action(_request('redirect'));
		http_response_code(302); // No Content
		header('Location: ' . $redirect);
		header('Connection: close');
		// et on continue
	}

	$status = autoupdate_patch_status();
	switch ($status) {
		case Status::PREPARING:
			return Status::PREPARING;
		case Status::FAILED:
			if (is_dir($d = autoupdate_patch_ready_dir())) {
				autoupdate_purger_update_precedent_same_hash();
				$status = autoupdate_patch_status();
				if ($status !== Status::NONE) {
					return $status;
				}
				spip_log('action_autoupdate_dist: Tentative update précédente failed nettoyée, on recommence', 'autoupdate' . _LOG_INFO_IMPORTANTE);
				echo "Cleaned, on recommence...\n";
			} else {
				return Status::FAILED;
			}
			// no break
		case Status::NONE:
			autoupdate_purger_update_precedent_same_hash();
			if ($loginit) {
				autoupdate_update_progress($loginit);
			}
			$dir_patch_to_check = autoupdate_telecharge_patch($version, $rollback_to);
			if ($dir_patch_to_check === null) {
				autoupdate_update_progress('Téléchargement échoué');
				return null;
			}
			$dir_patch_ready = autoupdate_prepare_patch($version, $dir_patch_to_check);
			if ($dir_patch_ready === null) {
				autoupdate_update_progress('Préparation echouée');
				return null;
			}
			// et on enchaine
			// no break
		case Status::READY:
			$res = autoupdate_start_update(autoupdate_patch_ready_dir());
			if ($res !== Status::PROCESSING) {
				autoupdate_update_progress('Lancement echoué');
			}
			return $res;

		case Status::PATCHED_OK:
		case Status::PATCHED_ERRORS:
			if (is_dir($d = autoupdate_patch_ready_dir())) {
				$patch_hash = basename($d);
				$sign = sha_file($d . 'manifest-translated.json');
				$autoupdate_finish = charger_fonction('autoupdate_finish', 'action');
				/** @uses action_autoupdate_finish_dist() */
				$autoupdate_finish($patch_hash, $sign);
			}
			// no break
		default:
			return $status;
	}
}

function autoupdate_telecharge_patch(string $version, ?string $rollback_to = null): ?string {
	if (autoupdate_patch_status() !== Status::NONE) {
		spip_log('autoupdate_prepare_patch: Il y a déjà un patch présent, on ne fait rien ' . json_encode([autoupdate_patch_ready_dir(), autoupdate_patch_status()]), 'autoupdate' . _LOG_ERREUR);
		return null;
	}
	$dir_autoupdate = sous_repertoire(_DIR_TMP, 'autoupdate');
	$dir_autoupdate_dl = sous_repertoire($dir_autoupdate, 'download');
	$dir_autoupdate_check = sous_repertoire($dir_autoupdate, 'check');
	$dir_autoupdate_ready = sous_repertoire($dir_autoupdate, 'ready');

	$url = autoupdate_urls_patch($version, $rollback_to);
	if ($url === null) {
		spip_log('autoupdate_prepare_patch: rien à télécharger', 'autoupdate' . _LOG_ERREUR);
		return null;
	}
	$operation = (version_compare($GLOBALS['spip_version_branche'], $url['to'], '<') ? 'Mise à jour' : 'Rollback');
	autoupdate_update_progress($operation . ' vers ' . $url['to']);

	$url_patch = $url['url_patch'];
	$hash_patch_expected = $url['hash'];
	$file_patch_zip = $dir_autoupdate_dl . basename(parse_url($url_patch, PHP_URL_PATH));
	autoupdate_update_progress("Téléchargement $url_patch");
	include_spip('inc/distant');
	if (!file_exists($file_patch_zip)) {
		$res = recuperer_url($url_patch, ['file' => $file_patch_zip]);
		if (!$res || $res['status'] !== 200) {
			spip_log("autoupdate_prepare_patch: Pas de patch disponible pour autoupdate SPIP $version : " . json_encode($res), 'autoupdate' . _LOG_INFO);
			return null;
		}
	}
	if (!file_exists($file_patch_zip)) {
		spip_log("autoupdate_prepare_patch: Fichier patch $file_patch_zip non trouvé pour autoupdate SPIP $version", 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	// verifier sha
	$hash_patch = sha_file($file_patch_zip);
	if (!hash_equals($hash_patch_expected, $hash_patch)) {
		spip_log("autoupdate_prepare_patch: hash Fichier patch $file_patch_zip $hash_patch ne correspond pas à ce qu'annonce spip.net: $hash_patch_expected", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_patch_zip);
		return null;
	}

	// deballer le zip
	include_spip('inc/archives');
	$archive = new Spip\Archives\SpipArchives($file_patch_zip);
	if (!($infos = $archive->informer())
		|| empty($infos['fichiers'])) {
		spip_log("autoupdate_prepare_patch: Archive $file_patch_zip invalide, impossible de lire le zip", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_patch_zip);
		return null;
	}

	$files = array_column($infos['fichiers'], 'filename');
	$files = array_map('basename', $files);
	if (!in_array('manifest.json', $files)) {
		spip_log("autoupdate_prepare_patch: Archive $file_patch_zip ne contient aucun manifest.json", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_patch_zip);
		return null;
	}

	$racine = $infos['proprietes']['racine'] ?? '';
	do {
		$tmp_dir = md5(uniqid(random_int(0, mt_getrandmax()), true));
		$dir_dest = $dir_autoupdate_check . $tmp_dir . '/';
	} while (is_dir($dir_dest));

	sous_repertoire($dir_dest);
	if (!is_dir($dir_dest)) {
		spip_log("autoupdate_prepare_patch: echec création répertoire temporaire $dir_dest pour déballer $file_patch_zip", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_patch_zip);
		return null;
	}

	$dir_deballe = $dir_dest;
	if (empty($racine)) {
		$racine = basename($file_patch_zip, '.zip') . '/';
		$dir_deballe .= $racine;
		mkdir($dir_deballe);
	}
	$dir_dest .= $racine;

	$file_lock = autoupdate_patch_ready_lock();
	@touch($file_lock);
	try {
		$archive->deballer($dir_deballe);
	} catch (Exception $e) {
		spip_log("autoupdate_prepare_patch: echec deballage archive $file_patch_zip:" . $e->getMessage(), 'autoupdate' . _LOG_ERREUR);
		@unlink($file_patch_zip);
		@unlink($file_lock);
		return null;
	}

	if (!is_dir($dir_dest) || !file_exists($dir_dest . 'manifest.json')) {
		spip_log("autoupdate_prepare_patch: echec inconnu lord du deballage archive $file_patch_zip, répertoire attendu $dir_dest vide ou incomplet", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_patch_zip);
		@unlink($file_lock);
		return null;
	}

	// TODO: verifier le sha du manifest.json ? normalemant on a vérifié le zip, ça doit suffire

	@unlink($file_patch_zip);
	// normalement tout est là dans dir_dest
	return $dir_dest;
}

function autoupdate_prepare_patch($version, $dir_patch): ?string {
	$file_lock = autoupdate_patch_ready_lock();
	@touch($file_lock);
	autoupdate_update_progress('Préparation du patch ' . basename($dir_patch));
	$manifest = file_get_contents($dir_patch . 'manifest.json');
	if (empty($manifest)) {
		spip_log("autoupdate_prepare_patch: aucun manifest.json dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_lock);
		return null;
	}
	$manifest = json_decode($manifest, true);
	if (!$manifest
	  || empty($manifest['schema'])
	  || !is_numeric($manifest['schema'])
	  || !intval($manifest['schema'])
	) {
		spip_log("autoupdate_prepare_patch: manifest.json mal formé (pas de schema) dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_lock);
		return null;
	}

	$schema = intval($manifest['schema']);
	$prepare_patch = charger_fonction('prepare_patch_v' . $schema, 'inc/autoupdate');
	/** @uses inc_autoupdate_prepare_patch_v1_dist() */
	$dir_patch = $prepare_patch($version, $dir_patch);
	if ($dir_patch === null) {
		@unlink($file_lock);
		return null;
	}

	// on prepare aussi un rollback mirroir
	autoupdate_generer_rollback($dir_patch);

	// si le patch contient spip-update.php, le mettre à jour dans ce plugin
	// (copie vers la racine uniquement au lancement, dans autoupdate_start_update)
	$spip_update = 'spip-update.php';
	if (file_exists(_DIR_RACINE . $dir_patch . $spip_update)) {
		@copy(_DIR_RACINE . $dir_patch . $spip_update, __DIR__ . '/../spip-update.php');
		if (
			!file_exists(__DIR__ . '/../spip-update.php')
			|| sha_file(__DIR__ . '/../spip-update.php') !== sha_file(_DIR_RACINE . $dir_patch . $spip_update)
		) {
			spip_log('autoupdate_check_patch: impossible de deployer le spip-update.php contenu dans le patch', 'autoupdate' . _LOG_ERREUR);
			@unlink($file_lock);
			return null;
		}
	}

	$dir_patch_ready = autoupdate_patch_ready_dir();

	// on ajoute l'URL de redirect pour revenir après spip-update.php
	include_spip('inc/filtres');
	// on utilise pas la signature hash habituelle car il suffit que le calcul change entre avant/apres et l'upgrade ne se fera plus
	$redirect_post_update = generer_url_action('autoupdate_finish', 'arg=' . basename($dir_patch_ready), true, true);
	$redirect_post_update = parametre_url($redirect_post_update, 'hash', sha_file($dir_patch . 'manifest-translated.json'), '&');
	$redirect_post_update = url_absolue($redirect_post_update);
	file_put_contents($dir_patch . 'redirect_finish.txt', $redirect_post_update);

	// le patch est bon, on le mets à disposition, sauf si le rename foire
	if (!rename($dir_patch, $dir_patch_ready)
		|| !is_dir($dir_patch_ready)) {
		spip_log("autoupdate_check_patch: echec renommage $dir_patch => $dir_patch_ready", 'autoupdate' . _LOG_ERREUR);
		@unlink($file_lock);
		return null;
	}

	// clean les reliquats dans autoupdate/check
	$dir_to_clean = explode('/autoupdate/check/', $dir_patch, 2);
	$dir_to_clean = end($dir_to_clean);
	$dir_to_clean = explode('/', $dir_to_clean);
	if ($dir_to_clean = reset($dir_to_clean)) {
		$dir_to_clean = _DIR_TMP . 'autoupdate/check/' . $dir_to_clean . '/';
		spip_log("autoupdate_check_patch: purger le repertoire $dir_to_clean", 'autoupdate' . _LOG_DEBUG);
		autoupdate_purger_repertoire($dir_to_clean, ['subdir' => true]);
		rmdir($dir_to_clean);
	}

	// on ajoute un sous dossier removed/ dans le patch pour que spip-update.php y stocke les fichier modifiés/supprimés
	sous_repertoire($dir_patch_ready, 'removed');
	if (!is_dir($dir_patch_ready . 'removed')) {
		spip_log('autoupdate_check_patch: echec création sous-repertoire removed/', 'autoupdate' . _LOG_ERREUR);
		@unlink($file_lock);
		return null;
	}

	// on est pret, on peut enlever le verrou qui ne sert plus
	@unlink($file_lock);
	return $dir_patch_ready;
}

function autoupdate_start_update($dir_patch_ready): int {
	autoupdate_update_progress('Appliquer la mise à jour via spip-update.php');
	$spip_update = 'spip-update.php';
	@copy(__DIR__ . '/../spip-update.php', _DIR_RACINE . $spip_update);
	// touch : copy() peut conserver l'ancien mtime (Windows), ce qui ferait refuser le script immédiatement
	@touch(_DIR_RACINE . $spip_update);
	if (!file_exists(_DIR_RACINE . $spip_update)
		|| sha_file(_DIR_RACINE . $spip_update) !== sha_file(__DIR__ . '/../spip-update.php')) {
		spip_log("autoupdate_start_update: pas de fichier $spip_update à la racine (ou fichier pas à jour)", 'autoupdate' . _LOG_ERREUR);
		return Status::FAILED;
	}

	$url_update = rtrim(url_de_base(), '/') . '/spip-update.php?hash=' . sha_file($dir_patch_ready . 'manifest-translated.json');
	spip_log("autoupdate_start_update: lancer l'update sur $url_update", 'autoupdate' . _LOG_DEBUG);
	include_spip('inc/queue');
	$res = queue_lancer_url_http_async($url_update);
	return $res ? Status::PROCESSING : Status::FAILED;
}

function autoupdate_generer_rollback($dir_patch) {
	$manifest = file_get_contents($dir_patch . 'manifest-translated.json');
	if (empty($manifest)) {
		spip_log("autoupdate_generer_rollback: aucun manifest-translated.json dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	$manifest = json_decode($manifest, true);
	if (
		!$manifest
		|| empty($manifest['schema'])
		|| empty($manifest['from'])
		|| empty($manifest['to'])
		|| !array_key_exists('added', $manifest)
		|| !array_key_exists('changed', $manifest)
		|| !array_key_exists('deleted', $manifest)
	) {
		spip_log("autoupdate_generer_rollback: manifest-translated.json mal formé dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	$dir_rollback = sous_repertoire($dir_patch, 'rollback');
	$manifest_rollback = [
		'added' => [],
		'changed' => [],
		'deleted' => [],
	];

	foreach ($manifest['added'] as $file_path => $hashes) {
		if (is_array($hashes) && !empty($hashes['to'])) {
			$manifest_rollback['deleted'][$file_path] = ['from' => $hashes['to'], 'to' => null];
		}
	}
	foreach ($manifest['deleted'] as $file_path => $hashes) {
		if (is_array($hashes) && !empty($hashes['from'])) {
			$hash_from = sha_file(_DIR_RACINE . $file_path);
			copy(_DIR_RACINE . $file_path, $dir_rollback . $hash_from . '.bin');
			$manifest_rollback['added'][$file_path] = ['from' => null, 'to' => $hash_from];
		}
	}
	foreach ($manifest['changed'] as $file_path => $hashes) {
		if (is_array($hashes) && !empty($hashes['from']) && !empty($hashes['to'])) {
			$hash_from = sha_file(_DIR_RACINE . $file_path);
			copy(_DIR_RACINE . $file_path, $dir_rollback . $hash_from . '.bin');
			$manifest_rollback['changed'][$file_path] = ['from' => $hashes['to'], 'to' => $hash_from];
		}
	}

	$manifest['rollback'] = $manifest_rollback;
	file_put_contents($dir_patch . 'manifest-translated.json', json_encode($manifest, JSON_PRETTY_PRINT));
	return $dir_rollback;
}
