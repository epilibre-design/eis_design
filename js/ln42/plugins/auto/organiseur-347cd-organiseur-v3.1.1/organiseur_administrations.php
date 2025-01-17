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
 * Installation/maj des tables messagerie
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function organiseur_upgrade($nom_meta_base_version, $version_cible) {
	// cas particulier :
	// si plugin pas installe mais que la table existe
	// considerer que c'est un upgrade depuis v 1.0.0
	// pour gerer l'historique des installations SPIP <=2.1
	if (!isset($GLOBALS['meta'][$nom_meta_base_version])) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		if (
			$desc = $trouver_table('spip_messages')
			and isset($desc['exist']) and $desc['exist']
		) {
			ecrire_meta($nom_meta_base_version, '1.0.0');
		}
		// si pas de table en base, on fera une simple creation de base
	}

	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_messages', 'spip_auteurs']],
	];

	$maj['1.1.0'] = [
		['sql_updateq', 'spip_messages', ['statut' => 'prepa'], "statut='redac'"],
		['maj_tables', ['spip_messages']], // champ destinataires
	];

	$maj['1.1.1'] = [
		['sql_alter', 'TABLE spip_messages CHANGE id_auteur id_auteur bigint(21) DEFAULT 0 NOT NULL'],
	];

	$maj['1.1.2'] = [
		['maj_tables', ['spip_auteurs']] // champs messagerie & imessage (parfois absents)
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Desinstallation/suppression des tables mots et groupes de mots
 *
 * @param string $nom_meta_base_version
 */
function organiseur_vider_tables($nom_meta_base_version) {
	sql_drop_table('spip_messages');
	sql_alter('TABLE spip_auteurs DROP messagerie');

	effacer_meta('messagerie_agenda');

	effacer_meta($nom_meta_base_version);
}
