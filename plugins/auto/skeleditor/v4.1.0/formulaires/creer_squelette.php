<?php

/**
 * Plugin SkelEditor
 * Editeur de squelette en ligne
 * (c) depuis 2007 Collectif SPIP
 * Licence GPL-v3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/skeleditor');

function formulaires_creer_squelette_charger_dist($path_base) {
	$valeurs = ['filename' => ''];
	$valeurs['editable'] = autoriser('creerdans', 'squelette', $path_base);

	return $valeurs;
}

function formulaires_creer_squelette_verifier_dist($path_base) {
	$erreurs = [];

	$filename = _request('filename');
	if ($e = skeleditor_verifie_nouveau_nom($path_base, $filename)) {
		$erreurs['filename'] = $e;
	}

	return $erreurs;
}

function formulaires_creer_squelette_traiter_dist($path_base) {
	$res = [];

	$filename = _request('filename');
	if (ecrire_fichier($path_base . $filename, '')) {
		$res = ['message_ok' => _T('ok'), 'redirect' => parametre_url(self(), 'f', $path_base . $filename)];
	} else {
		$res['message_erreur'] = _T('skeleditor:erreur_ecriture_fichier');
	}

	return $res;
}
