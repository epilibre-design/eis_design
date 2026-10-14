<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Prépare l’aperçu du rapport de debug, sans l’envoyer.
 *
 * Le JSON est écrit dans tmp puis affiché au retour sur la page de statut.
 */
function action_autoupdate_report_preview_dist(?string $hash = null): void {
	if ($hash === null) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$hash = $securiser_action();
	}
	$hash = (string) $hash;

	include_spip('action/autoupdate_report');
	if (!autoriser('webmestre') || !autoupdate_hash_rapport_valide($hash)) {
		return;
	}

	include_spip('inc/autoupdate');
	if (is_dir($dir = autoupdate_patch_ready_dir($hash)) || is_dir($dir = autoupdate_patch_trash_dir($hash))) {
		// Le fichier déjà envoyé est affiché tel quel : pas une nouvelle génération.
		if (is_file($dir . 'reported.json')) {
			return;
		}
		$report = autoupdate_generer_rapport($dir);
		if ($report) {
			autoupdate_stocker_apercu_rapport($hash, $report);
		}
	}
}
