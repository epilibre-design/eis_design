<?php

use Spip\Plugin\Autoupdate\Patch\Status;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_autoupdate_finish_dist(?string $arg = null, ?string $hash_sign = null): void {
	// La reprise (plugactu) est lancée par un curl coupé très vite : sans ça, PHP
	// interrompt le hit dès que le client a raccroché, avant finished.txt.
	ignore_user_abort(true);
	register_shutdown_function('autoupdate_finish_log_fatal');

	if ($arg === null) {
		$arg = _request('arg');
		$hash_sign = _request('hash');
	}

	$arg = explode('-', $arg, 2);
	$patch_hash = array_shift($arg);
	$iteration = empty($arg) ? 0 : intval(array_shift($arg));

	// dans tous les cas quand on arrive ici, on retire spip-update.php de la racine si il est encore là
	if (file_exists(_DIR_RACINE . 'spip-update.php')) {
		@unlink(_DIR_RACINE . 'spip-update.php');
		spip_log('action_autoupdate_finish_dist: suppression de spip-update.php à la racine', 'autoupdate' . _LOG_ERREUR);
	}

	$dir_patch_patched = _DIR_TMP . 'autoupdate/ready/' . $patch_hash . '/';
	if (!is_dir($dir_patch_patched)) {
		spip_log("action_autoupdate_finish_dist: aucun répertoire $dir_patch_patched", 'autoupdate' . _LOG_ERREUR);
		return;
	}

	include_spip('inc/autoupdate');

	// et la on vérifie que la signature d'appel de cette action est OK
	$file_manifest = $dir_patch_patched . 'manifest-translated.json';
	if (!file_exists($file_manifest)) {
		spip_log("action_autoupdate_finish_dist: $file_manifest absent, on abandonne", 'autoupdate' . _LOG_ERREUR);
		return;
	}
	$expected_hash = sha_file($file_manifest);
	if (!hash_equals($expected_hash, $hash_sign)) {
		spip_log("action_autoupdate_finish_dist: signature $hash_sign invalide, attendu : $expected_hash", 'autoupdate' . _LOG_ERREUR);
		return;
	}

	$manifest = json_decode((string) file_get_contents($file_manifest), true);
	$version_cible = is_array($manifest) && !empty($manifest['to']) ? (string) $manifest['to'] : '';

	$status = autoupdate_patch_status($dir_patch_patched);
	switch ($status) {
		case Status::UNKNOWN:
		case Status::NONE:
		case Status::PREPARING:
		case Status::READY:
		case Status::PROCESSING:
			spip_log("action_autoupdate_finish_dist: Status $status pour $dir_patch_patched, rien à faire ici", 'autoupdate' . _LOG_ERREUR);
			return;

		case Status::FAILED:
			spip_log("action_autoupdate_finish_dist: La mise à jour $dir_patch_patched a échoué, on ne peut rien faire de plus", 'autoupdate' . _LOG_ERREUR);
			$erreurs = file_exists($dir_patch_patched . 'errors.txt')
				? (string) file_get_contents($dir_patch_patched . 'errors.txt')
				: '';
			autoupdate_notifier_maj_echouee($version_cible, $erreurs);
			autoupdate_envoyer_rapport_auto_si_actif($patch_hash);
			return;

		case Status::FAILED_ROLLBACK:
			spip_log("action_autoupdate_finish_dist: La mise à jour $dir_patch_patched a échoué, et SPIP a été remis dans son état antérieur, on ne peut rien faire de plus", 'autoupdate' . _LOG_ERREUR);
			$erreurs = file_exists($dir_patch_patched . 'errors.txt')
				? (string) file_get_contents($dir_patch_patched . 'errors.txt')
				: '';
			autoupdate_notifier_maj_echouee($version_cible, $erreurs);
			$dir_autoupdate_trash = sous_repertoire(_DIR_TMP . 'autoupdate/', 'trash');
			@rename($dir_patch_patched, $dt = $dir_autoupdate_trash . basename($dir_patch_patched));
			spip_log("action_autoupdate_finish_dist: $dir_patch_patched déplacé dans $dt", 'autoupdate' . _LOG_DEBUG);
			autoupdate_envoyer_rapport_auto_si_actif($patch_hash);
			return;

		case Status::PATCHED_ERRORS:
		case Status::PATCHED_OK:
			if ($iteration === 0) {
				autoupdate_update_progress("Finalisation de l'update", $patch_hash);
				if ($status === Status::PATCHED_ERRORS) {
					$errors = file_get_contents($dir_patch_patched . 'errors.txt');
					spip_log("action_autoupdate_finish_dist: La mise à jour $dir_patch_patched a été réalisé, mais il y a eu des erreurs: \n" . $errors, 'autoupdate' . _LOG_ERREUR);
				} else {
					spip_log("action_autoupdate_finish_dist: La mise à jour $dir_patch_patched a été réalisé sans problème", 'autoupdate' . _LOG_DEBUG);
				}

				spip_log('action_autoupdate_finish_dist: Lancer la mise à jour de la base', 'autoupdate' . _LOG_DEBUG);
				// on lance le mail de notif ici, indépendamment de la réussite de l'upgrade de base
				$warnings = file_exists($dir_patch_patched . 'warnings.txt')
					? trim((string) file_get_contents($dir_patch_patched . 'warnings.txt'))
					: '';
				autoupdate_notifier_maj_finie($GLOBALS['spip_version_branche'], $warnings);
			} else {
				spip_log("action_autoupdate_finish_dist: Continuer la mise à jour de la base (step $iteration)", 'autoupdate' . _LOG_DEBUG);
			}

			$iteration++;
			$url_restart = generer_url_action('autoupdate_finish', 'arg=' . basename($dir_patch_patched) . '-' . (strval($iteration)), true, true);
			$url_restart = parametre_url($url_restart, 'hash', $hash_sign, '&');
			include_spip('inc/filtres');
			$url_restart = url_absolue($url_restart);
			autoupdate_update_progress('Vérification mises à jour de base (core)' . ($iteration > 1 ? " ($iteration)" : ''), $patch_hash);
			autoupdate_upgrade_core($dir_patch_patched, $url_restart);
			autoupdate_update_progress('Vérification mises à jour de base (plugins)' . ($iteration > 1 ? " ($iteration)" : ''), $patch_hash);
			autoupdate_upgrade_plugins($url_restart);

			autoupdate_update_progress('Mise à jour SPIP terminée, version installée : ' . $GLOBALS['spip_version_branche'], $patch_hash);
			touch($dir_patch_patched . 'finished.txt');
			$dir_autoupdate_trash = sous_repertoire(_DIR_TMP . 'autoupdate/', 'trash');
			@rename($dir_patch_patched, $dt = $dir_autoupdate_trash . basename($dir_patch_patched));
			spip_log("action_autoupdate_finish_dist: $dir_patch_patched déplacé dans $dt", 'autoupdate' . _LOG_DEBUG);
			autoupdate_relancer_verification_maj();
			autoupdate_envoyer_rapport_auto_si_actif($patch_hash);
			break;
	}
}

/**
 * Envoie le rapport de debug si l’option `report_auto` est activée.
 *
 * Appelé après une finalisation réussie ou un échec (FAILED / FAILED_ROLLBACK),
 * dès que le répertoire du patch est encore sous ready/ ou trash/.
 */
function autoupdate_envoyer_rapport_auto_si_actif(string $patch_hash): void {
	include_spip('inc/config');
	if (!lire_config('autoupdate/report_auto', 0)) {
		return;
	}
	$autoupdate_report = charger_fonction('autoupdate_report', 'action');
	$autoupdate_report($patch_hash);
}

/**
 * Journalise une erreur fatale PHP survenue pendant la finalisation.
 *
 * Un 500 n'atteint pas spip_log. Le rapport relit tmp/log/autoupdate.log,
 * donc le message s'y retrouvera au prochain envoi.
 */
function autoupdate_finish_log_fatal(): void {
	$err = error_get_last();
	if (
		!is_array($err)
		|| !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)
	) {
		return;
	}
	$fichier = (string) ($err['file'] ?? '');
	$ligne = (string) ($err['line'] ?? '');
	$message = (string) ($err['message'] ?? '');
	spip_log(
		"action_autoupdate_finish_dist: erreur fatale {$message} in {$fichier}:{$ligne}",
		'autoupdate' . _LOG_ERREUR
	);
}

function autoupdate_relancer_verification_maj() {
	job_queue_add('mise_a_jour', 'Check mise_a_jour post update', [time()], 'genie/');
	$url_cron = generer_url_action('cron');
	include_spip('inc/queue');
	queue_lancer_url_http_async($url_cron);
}

/**
 * Notifier les webmestre d’une nouvelle version existante (pour mettre à jour)
 *
 * La constante si définie `_MAJ_NOTIF_EMAILS` peut servir
 * - soit à indiquer les emails à notifier à la place des webmestres du site
 * - soit (falsy) pour empêcher cette notification
 */
function autoupdate_notifier_maj_finie(string $version, string $warnings = '') {
	$texte = recuperer_fond(
		'notifications/autoupdate_maj_finie',
		[
			'raw' => true,
			'version' => $version,
			'warnings' => $warnings !== '' ? "\n" . $warnings . "\n" : '',
		],
	);
	if (defined('_MAJ_NOTIF_EMAILS')) {
		$destinataires = constant('_MAJ_NOTIF_EMAILS');
	} else {
		$destinataires = array_column(sql_allfetsel('email', 'spip_auteurs', "statut='0minirezo' AND webmestre='oui'"), 'email');
	}
	if ($destinataires) {
		if (is_string($destinataires)) {
			$destinataires = explode(',', $destinataires);
		}
		$destinataires = array_map('trim', $destinataires);
		include_spip('inc/notifications');
		notifications_envoyer_mails(
			$destinataires,
			$texte,
			'[' . $GLOBALS['meta']['nom_site'] . '] ' . _T('autoupdate:mise_a_jour_version_spip', ['version' => $version])
		);
	}
}

/**
 * Notifier les webmestres d’un échec de mise à jour
 *
 * Mêmes destinataires que `autoupdate_notifier_maj_finie()` (`_MAJ_NOTIF_EMAILS` ou webmestres).
 */
function autoupdate_notifier_maj_echouee(string $version, string $erreurs) {
	$texte = recuperer_fond(
		'notifications/autoupdate_maj_echouee',
		['raw' => true, 'version' => $version, 'erreurs' => $erreurs],
	);
	if (defined('_MAJ_NOTIF_EMAILS')) {
		$destinataires = constant('_MAJ_NOTIF_EMAILS');
	} else {
		$destinataires = array_column(sql_allfetsel('email', 'spip_auteurs', "statut='0minirezo' AND webmestre='oui'"), 'email');
	}
	if ($destinataires) {
		if (is_string($destinataires)) {
			$destinataires = explode(',', $destinataires);
		}
		$destinataires = array_map('trim', $destinataires);
		include_spip('inc/notifications');
		notifications_envoyer_mails(
			$destinataires,
			$texte,
			'[' . $GLOBALS['meta']['nom_site'] . '] ' . _T('autoupdate:echec_mise_a_jour_version_spip', ['version' => $version])
		);
	}
}

/**
 * Maj du noyau si besoin
 * @param string $redirect
 */
function autoupdate_upgrade_core($dir_patch, $redirect) {
	spip_log("autoupdate_upgrade_core: $redirect", 'autoupdate' . _LOG_DEBUG);

	include_spip('inc/autoriser');
	include_spip('base/upgrade');
	include_spip('inc/headers');
	autoriser_exception('webmestre', null, null);

	// si le fichier upgraded.txt existe, c'est que l'upgrade de base a déjà été fait
	if (file_exists($dir_patch . 'upgraded.txt')) {
		return;
	}

	// fix : certains sites n'ont plus de meta version_installee et ne jouent pas les upgrade de base
	// on met la dernière valeur stable avant découverte du problème, partant du principe que les sites
	// qui tournaient avec cette version qui n'a pas changé pendant 3 ans
	// avaient résolus leurs problèmes de base via une réparation de la base
	if (empty($GLOBALS['meta']['version_installee']) && !empty($GLOBALS['meta'])) {
		$GLOBALS['meta']['version_installee'] = 2022_02_23_03;
	}

	if (
		!empty($GLOBALS['meta']['version_installee'])
		&& $GLOBALS['spip_version_base'] != $GLOBALS['meta']['version_installee']
	) {

		spip_log('action_autoupdate_finish_dist: Maj Base : ' . $GLOBALS['meta']['version_installee'] . ' vs ' . $GLOBALS['spip_version_base'], 'autoupdate' . _LOG_DEBUG);

		// une shutdown function pour relancer l'upgrade si pas fini
		register_shutdown_function('autoupdate_shutdown_restart', $redirect);

		// quand on rentre par ici, c'est toujours une mise a jour de SPIP
		// lancement de l'upgrade SPIP avec retour sur l'url actuelle
		$res = maj_base($GLOBALS['spip_version_base'], $redirect);

		if ($res) {
			// on arrete tout ici !
			exit;
		}
		spip_log('Fin de mise a jour SQL. Debut m-a-j acces et config', 'maj.' . _LOG_INFO_IMPORTANTE);
		@touch($dir_patch . 'upgraded.txt');

		// supprimer quelques fichiers temporaires qui peuvent se retrouver invalides
		@spip_unlink(_CACHE_RUBRIQUES);
		@spip_unlink(_CACHE_PIPELINES);
		@spip_unlink(_CACHE_PLUGINS_PATH);
		@spip_unlink(_CACHE_PLUGINS_OPT);
		@spip_unlink(_CACHE_PLUGINS_FCT);
		@spip_unlink(_CACHE_CHEMIN);
		@spip_unlink(_DIR_TMP . 'plugin_xml_cache.gz');

		include_spip('inc/auth');
		auth_synchroniser_distant();

		$config = charger_fonction('config', 'inc');
		$config();

		// on quitte, le shutdown va nous relancer pour l'upgrade des plugins
		exit();
	}

}

function autoupdate_upgrade_plugins($redirect) {
	spip_log("autoupdate_upgrade_core: $redirect", 'autoupdate' . _LOG_DEBUG);

	include_spip('inc/autoriser');
	include_spip('base/upgrade');
	include_spip('inc/headers');
	autoriser_exception('webmestre', null, null);

	$redirect = parametre_url($redirect, 'plugactu', _request('plugactu'), '&');

	include_spip('inc/plugin');
	if (!_request('plugactu')) {
		$redirect = parametre_url($redirect, 'plugactu', 'ok', '&');
		// maj des plugins si besoin
		actualise_plugins_actifs();
		// on se relance pour jouer la ma des plugins
		autoupdate_shutdown_restart($redirect);
		exit();
	}

	include_spip('inc/texte');
	include_spip('inc/filtres');

	define('_REDIRECT_MAJ_PLUGIN', $redirect);
	// on installe les plugins maintenant,
	// cela permet aux scripts d'install de faire des affichages (moches...)
	plugin_installes_meta();

	// et on finit en rechargeant les options de CK, si le plugin est actif
	if (
		include_spip('formulaires/configurer_ck')
		&& function_exists('formulaires_configurer_ck_charger_dist')
		&& function_exists('ck_produire_code')
		&& function_exists('ck_produire_options')
	) {
		$c = formulaires_configurer_ck_charger_dist();
		$code = ck_produire_code($c);
		ck_produire_options($code);
	}

	spip_log('Fin de mise a jour site', 'maj.' . _LOG_INFO_IMPORTANTE);

	// on purge les eventuelles erreurs d'activation
	plugin_donne_erreurs();

}

function autoupdate_shutdown_restart($url_restart) {
	// setting the curl parameters.
	$ch = curl_init($url_restart);
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
