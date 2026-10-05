<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 *  Chargement du formulaire de configuration du plugin MajPlugins
 *
 * @return void
 */
function formulaires_configurer_majplugins_charger_dist() {
    $valeurs = [];

	// actualiser la liste des paquets locaux systematiquement
	include_spip('inc/svp_depoter_local');
	// sans forcer tout le recalcul en base, mais en récupérant les erreurs XML
	$valeurs['_erreurs_xml'] = [];
	svp_actualiser_paquets_locaux(false, $valeurs['_erreurs_xml']);
	
	$valeurs['notification'] = lire_config('majplugins/notification');
	$valeurs['attente_maj'] = lire_config('majplugins/attente_maj');
	$valeurs['notification_maj'] = lire_config('majplugins/notification_maj');
	$valeurs['notification_erreurs'] = lire_config('majplugins/notification_erreurs');
	$valeurs['mails'] = lire_config('majplugins/mails');
	return $valeurs;
}
/**
 * Vérification du formulaire de configuration du plugin MajPlugins
 *
 * Pas de vérification spécifique
 * 
 * @return void
 */
function formulaires_configurer_majplugins_verifier_dist() {
   
	$erreurs = [];
	// Au moins un plugin doit être coché pour une action groupée (normalement inutile avec le javascript)
	if ( _request('appliquer') && !is_array(_request('ids_plugin'))) {
		$erreurs['message_erreur'] = _T('svp:message_erreur_aucun_plugin_selectionne');
	}
	
	return $erreurs;
}

/**
 * Traitement du formulaire de configuration du plugin MajPlugins
 *
 * Enregistrement dans les métas des plugins à mettre à jour automatiquement
 *
 * @return void
 */
function formulaires_configurer_majplugins_traiter_dist() {
    include_spip('inc/plugin');

// actions globales
	if ( _request('appliquer')) {
		$action_globale = _request('action_globale');
		$ids_plugin = _request('ids_plugin');
		if (!is_array($ids_plugin)) {
			echo('message_erreur= '._T('svp:message_erreur_aucun_plugin_selectionne'));
			$erreurs['message_erreur'] = _T('svp:message_erreur_aucun_plugin_selectionne');
		} else {
			foreach ($ids_plugin as $id_plugin) {
				ecrire_config("majplugins/plugins/$id_plugin", $action_globale);
			}
		}
	// action unitaire
	} else {
		$plugins_actifs = liste_plugin_actifs();
		$maj_auto = [];

		foreach ($plugins_actifs as $prefixe => $infos) {

			$choix = _request("$prefixe");
			if (in_array($choix, ['0','1', '2', '3'])&& $prefixe != 'MAJPLUGINS') {
				$maj_auto[$prefixe] = $choix;
			}
		}

		ecrire_config('majplugins/plugins', $maj_auto);
		ecrire_config('majplugins/notification', _request('notification'));
		ecrire_config('majplugins/attente_maj', _request('attente_maj'));
		ecrire_config('majplugins/notification_maj', _request('notification_maj'));
		ecrire_config('majplugins/notification_erreurs', _request('notification_erreurs'));
		ecrire_config('majplugins/mails', _request('mails'));
	}

    return ['message_ok' => 'Configuration enregistrée.', 'editable' => true];
}

/**
 * Utilisation du plugin Saisies pour la partie Notifications
 * Déclaration des champs
 *
 */

function formulaires_configurer_majplugins_saisies_dist() {
	return [
		[
			'saisie' => 'radio',
			'options' => [
				'nom' => 'notification',
				'label' => '<:majplugins:legende_notification:>',
				'data' => [
					'oui' => '<:majplugins:label_notification:>',
					'non' => '<:majplugins:label_aucune_notification:>',
				],
				'defaut' => 'non',
			],
		],
		[
			'saisie' => 'case',
			'options' => [
				'nom' => 'notification_maj',
				'label' => '<:majplugins:legende_notification_maj:>',
				'explication' => '<:majplugins:explication_notification_maj:>',
				'label_case' => '<:majplugins:texte_notification_maj:>',
				'valeur_oui' => '1',
				'afficher_si' => '@notification@ == "oui"',
			],
		],
		[
			'saisie' => 'case',
			'options' => [
				'nom' => 'notification_erreurs',
				'label' => '<:majplugins:legende_notification_erreurs:>',
				'explication' => '<:majplugins:explication_notification_erreurs:>',
				'label_case' => '<:majplugins:texte_notification_erreurs:>',
				'valeur_oui' => '1',
				'afficher_si' => '@notification@ == "oui" && @notification_maj@ != "1"',
			],
		],
		[
			'saisie' => 'case',
			'options' => [
				'nom' => 'attente_maj',
				'label' => '<:majplugins:legende_attente_maj:>',
				'explication' => '<:majplugins:explication_attente_maj:>',
				'label_case' => '<:majplugins:texte_attente_maj:>',
				'valeur_oui' => '1',
				'afficher_si' => '@notification@ == "oui"',
			],
		],
		[
			'saisie' => 'input',
			'options' => [
				'nom' => 'mails',
				'label' => '<:majplugins:legende_mails:>',
				'explication' => '<:majplugins:explication_mails:>',
				'afficher_si' => '@notification@ == "oui"',
			],
		],
	];
}