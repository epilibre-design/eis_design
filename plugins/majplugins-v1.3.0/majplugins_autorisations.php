<?php

/**
 * Définit les autorisations du plugin MajPlugins
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Autoriser l'ajout d'un plugin pour le traitement Cron
 *
 * Surcharge de la fonction autoriser_plugins_ajouter_dist du plugin SVP
 * 
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool true s'il a le droit, false sinon
 */
function autoriser_plugins_ajouter($faire, $type, $id, $qui, $opt) {
	if (!defined('_AUTORISER_TELECHARGER_PLUGINS')) {
		define('_AUTORISER_TELECHARGER_PLUGINS', true);
	}
	 
	 // Si on est dans le contexte d'une tâche CRON, on autoriser l'ajout d'un plugin
    if (defined('_MAJPLUGINS_CRON') && _MAJPLUGINS_CRON && _AUTORISER_TELECHARGER_PLUGINS) {
        return true;
    }

    // Comportement normal (on appelle l'autorisation par défaut)
	return _AUTORISER_TELECHARGER_PLUGINS and autoriser('webmestre');
}

/**
 * Fonction d'appel pour le pipeline
 * @pipeline autoriser 
 */

function majplugins_autoriser(){}

/**
 * Autorisation du plugin MajPlugins
 *
 */


function autoriser_majplugins_configurer_dist($faire, $type, $id, $qui, $opt) {
	
	$autoriser= autoriser($faire, $type, $id, $qui, $opt);

	return $autoriser;
}
