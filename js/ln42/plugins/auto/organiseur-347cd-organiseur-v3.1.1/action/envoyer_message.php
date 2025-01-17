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
 * @param int $id_message
 * @return void
 */
function action_envoyer_message_dist($id_message = null) {

	if (is_null($id_message)) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_message = $securiser_action();
	}

	include_spip('inc/autoriser');
	if (
		intval($id_message)
		and $type = sql_getfetsel('type', 'spip_messages', 'id_message=' . intval($id_message))
		and autoriser('envoyermessage', $type, $id_message)
	) {
		include_spip('action/editer_objet');
		objet_modifier('message', $id_message, ['statut' => 'publie']);
	}
}
