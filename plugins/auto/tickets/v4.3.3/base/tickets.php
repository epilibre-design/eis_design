<?php

/**
 * Plugin Tickets
 * Licence GPL (c) 2008-2012
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function tickets_declarer_tables_interfaces($interface) {
	// 'spip_' dans l'index de $tables_principales
	$interface['table_des_tables']['tickets'] = 'tickets';
	$interface['tables_jointures']['spip_tickets'][] = 'documents_liens';

	$interface['tables_jointures']['spip_tickets'][] = 'forums';

	return $interface;
}

function tickets_declarer_tables_objets_sql($tables) {
	$tables['spip_tickets'] = [
		'page' => 'ticket',
		'url_edit' => 'ticket_edit',
		'editable' => 'oui',
		'texte_retour' => 'tickets:icone_retour_ticket',
		'texte_objet' => 'tickets:ticket',
		'texte_objets' => 'tickets:tickets',
		'texte_ajouter' => 'tickets:titre_ajouter_un_ticket',
		'texte_creer_associer' => 'tickets:creer_et_associer_un_ticket',
		'texte_modifier' => 'tickets:icone_modifier_ticket',
		'texte_creer' => 'tickets:nouveau_ticket',
		'icone_objet' => 'ticket',
		'info_aucun_objet' => 'tickets:info_ticket_aucun',
		'info_1_objet' => 'tickets:info_ticket_1',
		'info_nb_objets' => 'tickets:info_ticket_nb',
		'titre' => "titre, '' AS lang",
		'principale' => 'oui',
		'champs_editables' => ['titre', 'texte', 'id_assigne', 'exemple', 'sticked'],
		'field' => [
			'id_ticket' => 'bigint(21) NOT NULL',
			'titre' => 'text NOT NULL',
			'texte' => "longtext DEFAULT '' NOT NULL",
			'date' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'date_modif' => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'statut' => "varchar(10) DEFAULT '0' NOT NULL",
			'id_auteur' => 'bigint(21) NOT NULL',
			'ip' => "varchar(16) DEFAULT '' NOT NULL",
			'id_assigne' => 'bigint(21) NOT NULL',
			'exemple' => "varchar(255) DEFAULT '' NOT NULL",
			'sticked' => "varchar(3) DEFAULT '' NOT NULL",
			'maj' => 'TIMESTAMP',
		],
		'key' => [
			'PRIMARY KEY' => 'id_ticket',
			'KEY date_modif' => 'date_modif',
			'KEY id_auteur' => 'id_auteur',
			'KEY id_assigne' => 'id_assigne',
			'KEY statut' => 'statut, date',
		],
		'rechercher_champs' => [
			'titre' => 8,
			'texte' => 8,
		],
		'rechercher_jointures' => [
			'auteur' => ['nom' => 10],
		],
		'statut' => [
			[
				'champ' => 'statut',
				'publie' => 'ouvert,resolu,ferme',
				'previsu' => 'ouvert,resolu,ferme',
				'exception' => ['statut', 'tout'],
			],
		],
		'statut_images' => [
			'ouvert' => 'puce-proposer-8.svg',
			'resolu' => 'puce-publier-8.svg',
			'ferme' => 'puce-fermer-8.svg',
			'poubelle' => 'puce-supprimer-8.svg'
		],
		'statut_textes_instituer' => [
			'ouvert' => _T('tickets:statut_ouvert'),
			'resolu' => _T('tickets:statut_resolu'),
			'ferme' => _T('tickets:statut_ferme'),
			'poubelle' => _T('tickets:statut_poubelle'),
		],
		'texte_changer_statut' => 'tickets:texte_ticket_statut',
		'champs_versionnes' => ['titre', 'texte', 'sticked'],
	];
	return $tables;
}

function tickets_declarer_tables_auxiliaires($tables) {
	$tables['spip_tickets_liens'] = [
		'field' => [
			'id_ticket' => "bigint(21) DEFAULT '0' NOT NULL",
			'id_objet' => "bigint(21) DEFAULT '0' NOT NULL",
			'objet' => "VARCHAR (25) DEFAULT '' NOT NULL",
		],
		'key' => [
			'PRIMARY KEY' => 'id_ticket,id_objet,objet',
			'KEY id_ticket' => 'id_ticket',
		],
	];
	return $tables;
}
