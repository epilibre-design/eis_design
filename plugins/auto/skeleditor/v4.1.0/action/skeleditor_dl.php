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

function action_skeleditor_dl_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$file_name = $securiser_action();

	if (autoriser('download', 'squelette', $file_name)) {
		include_spip('inc/livrer_fichier');
		spip_livrer_fichier($file_name, 'application/octet-stream', ['attachment' => true]);
		exit;
	}
}
