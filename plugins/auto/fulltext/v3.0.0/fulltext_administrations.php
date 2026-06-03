<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction d'installation, mise a jour de la base
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function fulltext_upgrade($nom_meta_base_version, $version_cible) {
	$current_version = 0.0;
	$maj = [];
	$maj['create'] = [
		['maj_tables',['spip_documents']]
	];

	$maj['0.2.0'] = [
		['sql_alter',"TABLE spip_documents CHANGE indexe extrait VARCHAR(3) NOT NULL default 'non'"]
	];

	$maj['0.2.1'] = [
		['sql_alter',"TABLE spip_documents CHANGE indexe extrait VARCHAR(3) NOT NULL default 'non'"],
		['maj_tables',['spip_documents']],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Fonction de desinstallation
 * On supprime :
 * -* la meta d'installation
 * @param string $nom_meta_base_version
 */
function fulltext_vider_tables($nom_meta_base_version) {
	effacer_meta($nom_meta_base_version);
}
