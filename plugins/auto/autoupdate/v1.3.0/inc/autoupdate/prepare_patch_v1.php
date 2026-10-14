<?php

function inc_autoupdate_prepare_patch_v1_dist($version, $dir_patch): ?string {
	$file_lock = autoupdate_patch_ready_lock();
	@touch($file_lock);
	$manifest = file_get_contents($dir_patch . 'manifest.json');
	if (empty($manifest)) {
		spip_log("inc_autoupdate_prepare_patch_v1: aucun manifest.json dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	// schema, from, to, core, plugins-dist
	$manifest = json_decode($manifest, true);
	if (
		!$manifest
		|| empty($manifest['from'])
		|| empty($manifest['to'])
		|| empty($manifest['core'])
		|| !array_key_exists('plugins-dist', $manifest)
	) {
		spip_log("inc_autoupdate_prepare_patch_v1: manifest.json mal formé dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	$manifest_translated = autoupdate_traduit_patch_v1($version, $dir_patch, $manifest);
	return autoupdate_check_patch_v1($version, $dir_patch, $manifest_translated);
}

function autoupdate_traduit_patch_v1(string $version, string $dir_patch, array $manifest): array {
	include_spip('inc/acces');
	// time et token sont ajoutés pour rendre le sha_file de ce manifest non previsible
	$manifest_translated = [
		'schema' => 1,
		'time' => time(),
		'token' => creer_uniqid(),
		'from' => $manifest['from'],
		'to' => $manifest['to'],
		'added' => $manifest['core']['added'] ?? [],
		'changed' => $manifest['core']['changed'] ?? [],
		'deleted' => $manifest['core']['deleted'] ?? [],
	];

	foreach ($manifest['plugins-dist'] as $prefix => $plugin_manifest) {
		if (!empty($plugin_manifest)) {
			$c = '_DIR_PLUGIN_' . strtoupper($prefix);
			if (defined($c)) {
				$dir_plugin_base = substr(constant($c), strlen(_DIR_RACINE));
				foreach ($plugin_manifest as $what => $files) {
					foreach ($files as $path => $hashes) {
						$manifest_translated[$what][$dir_plugin_base . $path] = $hashes;
					}
				}
			} else {
				spip_log("inc_autoupdate_prepare_patch_v1: plugins-dist $prefix absent de cette installation", 'autoupdate' . _LOG_INFO);
			}
		}
	}
	file_put_contents($dir_patch . 'manifest-translated.json', json_encode($manifest_translated, JSON_PRETTY_PRINT));
	return $manifest_translated;
}

function autoupdate_check_patch_v1(string $version, string $dir_patch, array $manifest): ?string {
	$ignore_changed_files = lire_config('autoupdate/ignore_changed_files', 0);
	$manifest = file_get_contents($dir_patch . 'manifest-translated.json');
	if (empty($manifest)) {
		spip_log("autoupdate_check_patch: aucun manifest-translated.json dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
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
		spip_log("autoupdate_check_patch_v1: manifest-translated.json mal formé dans $dir_patch", 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	if ($manifest['from'] !== $version) {
		spip_log("autoupdate_check_patch_v1: manifest-translated.json ne concerne pas la bonne version ($version attendue," . $manifest['from'] . 'trouvée)', 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	// Si on ignore les fichiers modifiés, traiter les fichiers changed absents comme des ajouts
	// (le patch contient le fichier cible ; le rollback les supprimera au lieu de restaurer un original inexistant)
	if ($ignore_changed_files && !empty($manifest['changed'])) {
		$promoted = [];
		foreach ($manifest['changed'] as $file_path => $hashes) {
			if (!file_exists(_DIR_RACINE . $file_path) && is_array($hashes) && !empty($hashes['to'])) {
				$manifest['added'][$file_path] = ['to' => $hashes['to']];
				unset($manifest['changed'][$file_path]);
				$promoted[] = $file_path;
			}
		}
		if ($promoted) {
			spip_log(
				"autoupdate_check_patch_v1: fichiers absents traités comme ajouts (ignore_changed_files) :\n- " . implode("\n- ", $promoted),
				'autoupdate' . _LOG_INFO_IMPORTANTE
			);
			file_put_contents($dir_patch . 'manifest-translated.json', json_encode($manifest, JSON_PRETTY_PRINT));
		}
	}

	$errors = [];
	foreach ($manifest['added'] as $file_path => $hashes) {
		if (!is_array($hashes) || empty($hashes['to'])) {
			$errors[] = "Hash 'to' manquant pour le fichier ajouté $file_path";
		} else {
			$sha_to = $hashes['to'];
			if (file_exists(_DIR_RACINE . $file_path) && sha_file(_DIR_RACINE . $file_path) !== $sha_to) {
				$errors[] = "Le patch veut ajouter le fichier $file_path déjà présent";
			}
			if (!file_exists($dir_patch . $sha_to . '.bin')) {
				$errors[] = "Fichier {$sha_to}.bin manquant dans le patch pour ajouter $file_path";
			}
			if (!is_dir($d = dirname(_DIR_RACINE . $file_path))) {
				autoupdate_creer_sous_repertoire($d);
				if (!is_dir($d)) {
					$errors[] = "Impossible de créer le répertoire $d pour y ajouter le fichier " . basename($file_path);
				}
			}
		}
	}

	foreach ($manifest['changed'] as $file_path => $hashes) {
		if (!is_array($hashes) || empty($hashes['from']) || empty($hashes['to'])) {
			$errors[] = "Hashes mal formé manquant pour le fichier modifié $file_path";
		} else {
			$sha_from = $hashes['from'];
			$sha_to = $hashes['to'];
			if (!file_exists(_DIR_RACINE . $file_path)) {
				$errors[] = "Le patch veut modifier le fichier $file_path absent";
			} elseif (!is_writable(_DIR_RACINE . $file_path)) {
				$errors[] = "Le patch veut modifier le fichier $file_path, mais il n'est pas writeable";
				// TODO : essayer de chmod?
			} elseif (!$ignore_changed_files && sha_file(_DIR_RACINE . $file_path) !== $sha_from) {
				$errors[] = "Le patch veut modifier le fichier $file_path, mais il a été modifié par rapport à celui de la version $version";
			}
			if (!file_exists($dir_patch . $sha_to . '.bin')) {
				$errors[] = "Fichier {$sha_to}.bin manquant dans le patch pour modifier $file_path";
			}
		}
	}

	foreach ($manifest['deleted'] as $file_path => $hashes) {
		if (!is_array($hashes) || empty($hashes['from'])) {
			$errors[] = "Hashes 'from' manquant pour le fichier supprimé $file_path";
		} else {
			$sha_from = $hashes['from'];
			if (!file_exists(_DIR_RACINE . $file_path)) {
				// Ce n'est pas une erreur, mais peut-être la reprise d'une maj abandonnée ?
				// $errors[] = "Le patch veut supprimer le fichier $file_path absent";
			} elseif (!is_writable(_DIR_RACINE . $file_path)) {
				$errors[] = "Le patch veut supprimer le fichier $file_path, mais il n'est pas writeable";
				// TODO : essayer de chmod?
			} elseif (!$ignore_changed_files && sha_file(_DIR_RACINE . $file_path) !== $sha_from) {
				$errors[] = "Le patch veut supprimer le fichier $file_path, mais il a été modifié par rapport à celui de la version $version";
			}
		}
	}

	if (!empty($errors)) {
		spip_log("autoupdate_check_patch_v1: impossible d'appliquer le patch, il y a des erreurs : \n- " . implode("\n- ", $errors), 'autoupdate' . _LOG_ERREUR);
		return null;
	}

	autoupdate_gerer_htaccess($dir_patch, $manifest);

	return $dir_patch;
}

/**
 * Si le patch touche htaccess.txt, synchroniser ou alerter pour .htaccess.
 *
 * - symlink vers htaccess.txt : rien (la MAJ de htaccess.txt suffit)
 * - copie à l'identique : ajouter .htaccess au manifest avec les mêmes hashes
 * - .htaccess custom (contenu différent) : alerte webmestre, ne pas toucher
 * - .htaccess absent : rien
 *
 * @param array $manifest manifest-translated (passé par référence, peut être muté)
 */
function autoupdate_gerer_htaccess(string $dir_patch, array &$manifest): void {
	$hashes = null;
	$op = null;
	if (!empty($manifest['changed']['htaccess.txt']) && is_array($manifest['changed']['htaccess.txt'])) {
		$hashes = $manifest['changed']['htaccess.txt'];
		$op = 'changed';
	} elseif (!empty($manifest['added']['htaccess.txt']) && is_array($manifest['added']['htaccess.txt'])) {
		$hashes = $manifest['added']['htaccess.txt'];
		$op = 'added';
	} else {
		return;
	}

	$path_htaccess = _DIR_RACINE . '.htaccess';
	$path_txt = _DIR_RACINE . 'htaccess.txt';

	// Absent : on ne crée pas de .htaccess et on n'alerte pas
	if (!file_exists($path_htaccess) && !is_link($path_htaccess)) {
		return;
	}

	// Symlink vers htaccess.txt : la MAJ de htaccess.txt met à jour la cible
	if (is_link($path_htaccess)) {
		$link = readlink($path_htaccess);
		$resolved = $link !== false
			? (realpath(dirname($path_htaccess) . '/' . $link) ?: realpath($link))
			: false;
		$txt_real = realpath($path_txt);
		if ($resolved && $txt_real && $resolved === $txt_real) {
			spip_log('autoupdate_gerer_htaccess: .htaccess est un symlink vers htaccess.txt, rien à faire', 'autoupdate' . _LOG_DEBUG);
			return;
		}
		// Symlink vers autre chose : traiter comme custom
		autoupdate_htaccess_alerter($dir_patch);
		return;
	}

	// Copie à l'identique de htaccess.txt actuel → sync auto sur changed
	if (
		$op === 'changed'
		&& file_exists($path_txt)
		&& !empty($hashes['from'])
		&& !empty($hashes['to'])
		&& sha_file($path_htaccess) === sha_file($path_txt)
	) {
		if (!is_writable($path_htaccess)) {
			spip_log('autoupdate_gerer_htaccess: .htaccess identique mais non writable, alerte', 'autoupdate' . _LOG_INFO_IMPORTANTE);
			autoupdate_htaccess_alerter($dir_patch);
			return;
		}
		$manifest['changed']['.htaccess'] = [
			'from' => $hashes['from'],
			'to' => $hashes['to'],
		];
		file_put_contents($dir_patch . 'manifest-translated.json', json_encode($manifest, JSON_PRETTY_PRINT));
		spip_log('autoupdate_gerer_htaccess: .htaccess synchronisé avec htaccess.txt dans le manifest', 'autoupdate' . _LOG_INFO_IMPORTANTE);
		return;
	}

	// Ajout de htaccess.txt : si .htaccess a déjà le contenu cible, rien à faire
	if ($op === 'added' && !empty($hashes['to']) && sha_file($path_htaccess) === $hashes['to']) {
		spip_log('autoupdate_gerer_htaccess: .htaccess déjà au contenu cible, rien à faire', 'autoupdate' . _LOG_DEBUG);
		return;
	}

	// Contenu différent (personnalisé) : alerte, ne pas toucher
	autoupdate_htaccess_alerter($dir_patch);
}

/**
 * Persiste une alerte htaccess pour le statut UI et le mail de fin.
 */
function autoupdate_htaccess_alerter(string $dir_patch): void {
	include_spip('inc/autoupdate');
	$msg = _T('autoupdate:warning_htaccess');
	file_put_contents($dir_patch . 'warnings.txt', $msg . "\n", FILE_APPEND);
	autoupdate_update_progress($msg);
	spip_log('autoupdate_gerer_htaccess: ' . $msg, 'autoupdate' . _LOG_INFO_IMPORTANTE);
}
