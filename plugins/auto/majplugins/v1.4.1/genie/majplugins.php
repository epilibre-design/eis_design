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
function genie_majplugins_dist($t) {
		
	// Traitement majplugins_cron retardé de 30s avec une priorité haute pour être lancé ensuite unitairement sans pénaliser les autres tâches
	include_spip('queue,inc');
	queue_add_job(
    'majplugins_cron',          	// fonction
    'Mise à jour auto des plugins', // titre
    [],                       		// pas d’arguments
    'genie/',   					// fichier à inclure
    false,                   	 	// pas de doublon
    time() + 30,          	   		// lancer dans 30s
    + 5                     	   	// priorité haute
);
	return 1;
}