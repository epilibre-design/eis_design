<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Surcharge de genie_queue_dist()
 *
 * On force _JQ_MAX_JOBS_EXECUTE = 1 si majplugins_cron est dans la file d'attente
 * On évite ainsi un téléscopage entre l'exécution d'une tâche et une mise à jour de plugin
 * On vérifie au passage que la tâche n'est pas en timeout au rique de bloquer la queue du cron
  *
*/
function inc_genie($taches = []) {

	// Si un job majplugins_cron est planifié alors on force une tâche à la fois (_JQ_SCHEDULED = 1) pour éviter les conflits
	$fonction = 'majplugins_cron';
	$cond = ['fonction=' . sql_quote($fonction),'status=1',];
	$res=sql_select('*','spip_jobs',$cond);

	if ($res and sql_count($res)>0) {
		if (!defined('_JQ_MAX_JOBS_EXECUTE')) {
           define('_JQ_MAX_JOBS_EXECUTE', 1);
        }
    }

	// On surveille que la tâche majplugins_cron n'est pas en timeout ou erreur fatale au risque de bloquer toute la queue cron
	// car on ne passe plus dans la fonction queue_update_next_job_time() le temps de son exécution (qui fait ce même contrôle)
	$time = time();
	$res = sql_allfetsel(
		'*',
		'spip_jobs',
		'status=0'  . ' AND date<' . sql_quote(date('Y-m-d H:i:s', $time - 180)). 'AND fonction=' . sql_quote($fonction)
	);

	if (is_array($res)) {
		foreach ($res as $row) {
			include_spip('inc/queue');
			queue_close_job($row, $time);
			spip_log('queue_close_job car _JQ_PENDING depuis +180s : ' . print_r($row, 1), 'majplugins' . _LOG_ERREUR);
		}
	}	

	// Si un job majplugins_cron est en cours d'exécution alors aucun autre job ne sera lancé pour éviter les conflits
	$cond = ['fonction=' . sql_quote($fonction),'status=0',];
	$res=sql_select('*','spip_jobs',$cond);
	if ($res and sql_count($res)>0) {
		// Pas de lancement de la fonction queue_schedule()
		return;
   }

    // Appel de la fonction native inc_genie_dist()
	include_spip('inc/genie');
	return inc_genie_dist($taches = []);
}

