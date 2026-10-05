<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Tache periodique de traitement majplugins
 *
 * @param array $taches_generales
 * @return array
 */
function majplugins_taches_generales_cron($taches_generales){	
	
	if (defined('_MAJPLUGINS_CRON_FREQUENCE') && is_numeric(_MAJPLUGINS_CRON_FREQUENCE)) {
		$taches_generales['majplugins'] = _MAJPLUGINS_CRON_FREQUENCE;
	}else{
		$taches_generales['majplugins'] = 24*60*60;
	}
	return $taches_generales;
}

/**
 * Afficher le lien vers MajPlugins dans la Gestion des plugins actifs
 *
 * @param $flux
 * @return mixed
 */
function majplugins_affiche_gauche($flux) {
	$exec = (empty($flux['args']['exec']) ? '' :  $flux['args']['exec']);
	$voir = (empty(_request('voir'))) ? 'actif' :  _request('voir'); //On va chercher le paramètre 'voir' en _request car non transmis dans le pipeline
	if (
		$exec === 'admin_plugin' and $voir =='actif'
	) {
		$flux['data'] = recuperer_fond('prive/squelettes/inclure/lien_majplugins', $flux['args']).$flux['data'];
	}
	return $flux;
}

/**
 * Actualiser la globale meta et actualiser les plugins actifs en cas de crash_plugin
 *
 * Des hits simulatanés pendant la mise à jour des plugins peut provoquer un crash_plugins: charger_pipelines.php pas encore mis à jour alors que les chemins des plugin ont changé
 * Actualisation des plugios actifs qui supprimera par la même occasion message_crash_plugins de la meta 
 *
 * @param $flux
 * @return mixed
 */

function majplugins_affichage_final($texte) {
include_spip('inc/facteur');
malog('majplugins_pipelines.php/majplugins_affichage_final/debut');

include_spip('inc/meta');
// Rafraîchissement de la Globale "meta"
lire_metas();

	// Actualisation des plugins si présence d'un message Crash Plugins dans les métas
	include_spip('inc/plugin');
	if (isset($GLOBALS['meta']['message_crash_plugins'])) {
		actualise_plugins_actifs();
malog('majplugins_pipelines.php/majplugins_affichage_final/actualise_plugins_actifs');
	}
malog('majplugins_pipelines.php/majplugins_affichage_final/return');
	return $texte;
}