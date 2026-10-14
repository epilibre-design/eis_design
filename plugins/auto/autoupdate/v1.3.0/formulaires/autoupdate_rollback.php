<?php

/**
 * Formulaire CVT de rollback SPIP (step-up password, modèle SVP).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * @return array|false
 */
function formulaires_autoupdate_rollback_charger_dist() {
	if (!autoriser('webmestre')) {
		return false;
	}

	include_spip('autoupdate_fonctions');
	$versions = autoupdate_rollback_disponibles();
	if (!$versions) {
		return false;
	}

	return [
		'password' => '',
		'to' => $versions[0],
		'_versions' => $versions,
	];
}

/**
 * @return array
 */
function formulaires_autoupdate_rollback_verifier_dist() {
	$erreurs = [];

	if (!autoriser('webmestre')) {
		$erreurs['message_erreur'] = _T('autoupdate:erreur_rollback_interdit');
		set_request('password');
		return $erreurs;
	}

	include_spip('autoupdate_fonctions');
	$versions = autoupdate_rollback_disponibles();
	$to = (string) _request('to');
	if ($to === '' || !in_array($to, $versions, true)) {
		$erreurs['to'] = _T('autoupdate:erreur_rollback_version_invalide');
	}

	if (empty($password = _request('password'))) {
		$erreurs['password'] = _T('info_obligatoire');
	} else {
		include_spip('inc/auth');
		if (!auth_controler_password_auteur_connecte($password)) {
			$erreurs['message_erreur'] = _T('autoupdate:erreur_password_incorrect');
		}
	}

	if ($erreurs) {
		set_request('password');
	}

	return $erreurs;
}

/**
 * @return array
 */
function formulaires_autoupdate_rollback_traiter_dist() {
	set_request('password');

	if (!autoriser('webmestre')) {
		return ['message_erreur' => _T('autoupdate:erreur_rollback_interdit')];
	}

	$version = $GLOBALS['spip_version_branche'];
	$to = (string) _request('to');

	include_spip('autoupdate_fonctions');
	$versions = autoupdate_rollback_disponibles($version);
	if ($to === '' || !in_array($to, $versions, true)) {
		return ['message_erreur' => _T('autoupdate:erreur_rollback_version_invalide')];
	}

	include_spip('inc/autoupdate');
	$urls = autoupdate_urls_patch($version, $to);
	if (empty($urls)) {
		return ['message_erreur' => _T('autoupdate:erreur_rollback_version_invalide')];
	}

	// Redirect fixe (jamais _request('redirect')) puis lancer le patch dans le même hit,
	// comme le chemin web historique de action/autoupdate.
	$redirect = generer_url_ecrire('autoupdate_status');
	autoupdate_update_progress(
		'Lancement du rollback de ' . $version . ' vers ' . $urls['to'] . '...'
	);

	$action_autoupdate = charger_fonction('autoupdate', 'action');
	/** @uses action_autoupdate_dist() */

	if (!headers_sent()) {
		http_response_code(302);
		header('Location: ' . $redirect);
		header('Connection: close');
		$action_autoupdate($version, $to);
		exit;
	}

	$action_autoupdate($version, $to);

	return ['redirect' => $redirect];
}
