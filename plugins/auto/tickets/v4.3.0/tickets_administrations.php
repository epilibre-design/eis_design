<?php

/**
 * Plugin Tickets
 * Licence GPL (c) 2008-2012
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/meta');
include_spip('inc/migration_200');

function tickets_upgrade($nom_meta_base_version, $version_cible) {
	$current_version = '0.0';

	// On traite le cas de la premiere version de Tickets sans version_base
	if ((!isset($GLOBALS['meta'][$nom_meta_base_version])) && tickets_existe()) {
		$current_version = '0.1';
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables',['spip_tickets','spip_tickets_liens']]
	];

	$maj['0.2'] = ['maj_tables',['spip_tickets']];
	$maj['0.6'] = [
		['sql_alter',"TABLE spip_tickets MODIFY jalon varchar(30) DEFAULT '' NOT NULL"],
		['sql_alter',"TABLE spip_tickets MODIFY version varchar(30) DEFAULT '' NOT NULL"]
	];
	$maj['0.7'] = ['maj_tables',['spip_tickets']];
	$maj['1.1'] = [
		['maj_tables',['spip_tickets']],
		['migrer_commentaires_tickets_vers_forums',''],
		['sql_drop_table','spip_tickets_forum']
	];
	$maj['1.2'] = [
		['maj_tables',['spip_tickets']]
	];
	$maj['1.3'] = [
		['sql_alter','TABLE spip_tickets DROP tracker'],
		['sql_alter',"TABLE spip_tickets CHANGE type tracker integer DEFAULT '0' NOT NULL"]
	];
	$maj['1.4'] = [
		['maj_tables',['spip_tickets']]
	];
	$maj['1.4.1'] = [
		['sql_alter',"TABLE spip_tickets CHANGE version version varchar(255) DEFAULT '' NOT NULL"]
	];
	/**
	 * On ne prend plus en compte le statut "redac"
	 */
	$maj['1.5.0'] = [
		['tickets_supprimer_redac','']
	];

	/**
	* Interface graphique pour la notification des tickets
	*/
	$maj['1.6.0'] = [
		['ticket_install_config_notifications']
	];
	$maj['1.7.0'] = [
		['maj_tables',['spip_tickets_liens']]
	];
	$maj['1.8.0'] = [
		['effacer_config','tickets/general/lier_mots']
	];

	$maj['2.0.0'] = [
		['migrer_champs_vers_mots_cles']
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function tickets_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_tickets');
	sql_drop_table('spip_tickets_liens');
	effacer_meta($nom_meta_base_version);
}

function tickets_existe() {
	$desc = sql_showtable('spip_tickets', true);
	if (!isset($desc['field']) || !$desc['field']) {
		return false;
	} else { return true;
	}
}

function ticket_install_config_notifications() {
	ecrire_config('tickets/general/notif_destinataires', '0minirezo');
}

function tickets_supprimer_redac() {
	sql_updateq('spip_tickets', ['statut' => 'ouvert'], ['statut' => 'redac']);
}

function migrer_commentaires_tickets_vers_forums() {
	$res = sql_select('*', 'spip_tickets_forum');
	if ($res) {
		$correspondances = [];
		while ($r = sql_fetch($res)) {
			$titre = sql_getfetsel('titre', 'spip_tickets', 'id_ticket=' . sql_quote($r['id_ticket']));
			$auteur = sql_fetsel(['nom','email'], 'spip_auteurs', 'id_auteur=' . sql_quote($r['id_auteur']));
			$correspondances[] = [
				'id_objet'	=> $r['id_ticket'],
				'objet'		=> 'ticket',
				'id_parent'	=> 0,
				'id_thread'	=> 0, // prendra id_forum cree
				'date_heure'	=> $r['date'],
				'titre'	=> $titre,
				'texte'	=> $r['texte'],
				'auteur'	=> ($auteur ? $auteur['nom'] : ''),
				'email_auteur'	=> ($auteur ? $auteur['email'] : ''),
				'statut'	=> 'publie', // publie = public, prive = prive... dilemme ?
				'ip'	=> $r['ip'],
				'id_auteur'	=> $r['id_auteur'],
			];
		}

		if (count($correspondances)) {
			sql_insertq_multi('spip_forum', $correspondances);
			sql_update(
				'spip_forum',
				['id_thread' => 'id_forum'],
				['id_thread=0', 'objet=' . sql_quote('ticket')]
			);
		}
	}
}
