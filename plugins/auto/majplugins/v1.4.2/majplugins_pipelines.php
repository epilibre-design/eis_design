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
 * Afficher le drapeau d'alerte en cas de mise à jour de plugins
 *
 * @param $flux
 * @return mixed
 */
function majplugins_affiche_droite($flux) {
	if (autoriser('configurer', '_plugins'))
	{
		$flux['data'] = recuperer_fond('prive/squelettes/inclure/drapeau_majplugins', $flux['args']).$flux['data'];
	}
	return $flux;
}
