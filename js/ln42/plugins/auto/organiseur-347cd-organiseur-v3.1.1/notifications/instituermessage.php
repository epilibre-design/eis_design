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
 * Envoyer les notifications consecutives a l'envoi d'un message
 * (ie passage en statut=publie)
 *
 * @param string $quoi
 * @param int $id_message
 * @param array $options
 */
function notifications_instituermessage_dist($quoi, $id_message, $options = []) {

	// ne devrait jamais se produire
	if ($options['statut'] == $options['statut_ancien']) {
		spip_log('statut inchange', 'notifications');
		return;
	}

	if ($options['statut'] == 'publie') {
		include_spip('inc/messages');
		$type = sql_getfetsel('type', 'spip_messages', 'id_message=' . intval($id_message));
		$vue = "notifications/message_{$type}_publie";
		if (trouver_fond($vue)) {
			$envoyer_mail = charger_fonction('envoyer_mail', 'inc'); // pour nettoyer_titre_email
			$texte = recuperer_fond($vue, ['id_message' => $id_message]);

			// recuperer tous les emails des auteurs qui ont recu le message dans leur boite
			// si c'est une annonce generale, on envoie a tout le monde
			include_spip('inc/messages');
			$where = [
				"email!=''",
				"statut!='5poubelle'",
				sql_in('statut', messagerie_statuts_destinataires_possibles())
			];
			// pour une annonce : tous ceux qui recoivent des messages
			if ($type !== 'affich') {
				$ids = sql_allfetsel('id_auteur', 'spip_auteurs_liens', "objet='message' AND id_objet=" . intval($id_message));
				$ids = array_column($ids, 'id_auteur');
				$where[] = sql_in('id_auteur', $ids);
			}
			$emails = sql_allfetsel('email', 'spip_auteurs', $where);
			$emails = array_column($emails, 'email');

			include_spip('inc/notifications');
			notifications_envoyer_mails($emails, $texte);
		}
	}
}
