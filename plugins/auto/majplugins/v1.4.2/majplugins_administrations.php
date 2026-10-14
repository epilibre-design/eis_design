<?php
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Installation et mises à jour du plugin MajPlugins.
 *
 * Applique les éventuelles migrations de configuration.
 */
function majplugins_upgrade($nom_meta_base_version, $version_cible) {

	$maj = [];

	// Version initiale
	$maj['create'] = [];

	// Migration de la configuration des notifications
	$maj['1.0.0'] = [
		['majplugins_migrer_configuration'],
	];

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Désinstallation du plugin MajPlugins.
 *
 * Supprime les métas créées par le plugin.
 */
function majplugins_vider_tables($nom_meta_base_version) {

	effacer_meta('majplugins');
	effacer_meta($nom_meta_base_version);
}

/**
 * Migration de la configuration lors de la maj v1.0.0.
 *
 * Si les notifications étaient activées avant l'introduction
 * des niveaux de notification, active par défaut les notifications
 * de mises à jour effectuées.
 */
function majplugins_migrer_configuration() {

	include_spip('inc/config');

	// L'ancienne configuration activait les notifications.
	$notification = lire_config('majplugins/notification', null);

	// La nouvelle configuration n'existe pas encore.
	$notification_maj = lire_config('majplugins/notification_maj', null);

	if ($notification === 'oui' && $notification_maj === null) {
		ecrire_config('majplugins/notification_maj', 1);
	}
}