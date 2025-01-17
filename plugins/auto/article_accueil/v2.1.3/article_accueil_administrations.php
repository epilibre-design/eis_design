<?php
/*
 * Plugin Article Accueil
 * (c) 2011 Cedric Morin, Joseph
 * Distribue sous licence GPL
 *
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Upgrade des tables
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function article_accueil_upgrade($nom_meta_base_version, $version_cible) {
	$maj = [];
	$maj['create'] = [
		['maj_tables', ['spip_rubriques']],
	];
	$maj['0.1'] = [
		['maj_tables', ['spip_rubriques']],
	];
	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Desinstallation
 *
 * @param string $nom_meta_base_version
 */
function article_accueil_vider_tables($nom_meta_base_version) {
	include_spip('inc/meta');
	include_spip('base/abstract_sql');
	sql_alter('TABLE spip_rubriques DROP id_article_accueil');
	effacer_meta($nom_meta_base_version);
}
