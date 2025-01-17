<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * @param int $id_auteur
 * @param int $id_message
 * @return void
 */
function action_effacer_messagerecu_dist($id_auteur = null, $id_message = null) {
	if (is_null($id_auteur) or is_null($id_message)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
		[$id_auteur, $id_message] = explode('-', $arg);
	}


	include_spip('inc/autoriser');
	if (autoriser('effacer', 'messagerecu', $id_message, null, ['id_auteur' => $id_auteur])) {
		include_spip('inc/messages');
		messagerie_effacer_message_recu($id_auteur, $id_message);
	}
}
