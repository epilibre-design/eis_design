<?php
/**
 * Fichier gérant l'installation et désinstallation du plugin Odaiba
 *
 * @plugin     Odaiba
 * @copyright  2020
 * @author     erational
 * @licence    GNU/GPL
 * @package    SPIP\Odaiba\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/meta');
include_spip('inc/cextras');
include_spip('base/odaiba');


/**
 * Fonction d'installation et de mise à jour du plugin Odaiba.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function odaiba_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	cextras_api_upgrade(odaiba_declarer_champs_extras(), $maj['create']);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Fonction de désinstallation du plugin Odaiba
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function odaiba_vider_tables($nom_meta_base_version) {
	cextras_api_vider_tables(odaiba_declarer_champs_extras());
	effacer_config('odaiba');
	effacer_meta($nom_meta_base_version);
}

