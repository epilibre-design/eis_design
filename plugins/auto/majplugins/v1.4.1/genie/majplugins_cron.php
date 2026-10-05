<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Gestion du génie majplugins_cron
 *
 * @param  mixed $t
 *		Timestamp de la dernière exécution de cette tâche
 * @return void
 *		Positif : la tâche a été effectuée
 */
function genie_majplugins_cron_dist() {
	
	// On identifie le traitement comme provenant du Cron 
	define('_MAJPLUGINS_CRON', true);
	
	// Lancement de la mise à jour automatique
	include_spip('majplugins_fonctions');
	majplugins_traitement_cron();

	return 1;
}