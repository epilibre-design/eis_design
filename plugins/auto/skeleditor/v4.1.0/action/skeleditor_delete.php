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


function action_skeleditor_delete_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$file_name = $securiser_action();

	if (autoriser('supprimer', 'squelette', $file_name)) {
		spip_unlink($file_name);
	}
}
