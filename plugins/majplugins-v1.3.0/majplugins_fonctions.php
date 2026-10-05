<?php

use function SpipLeague\Component\Kernel\app;

/**
 * Fonctions utiles au plugin majplugins
 * 
 * 
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Traitement du Cron
 * 
 * Fonction appelée périodiquement pour maj automatique des plugins
 *
 * @return void
 */
function majplugins_traitement_cron () {

	$message_log=[];

	// Timestamp début de traitement
	$timestamp_debut = microtime(true);

	// Actualisation pour partir propre
	include_spip('inc/meta');
	lire_metas();

	// Liste des paquets locaux 
	include_spip('inc/svp_depoter_local');
	$erreurs_xml = [];
	svp_actualiser_paquets_locaux(true, $erreurs_xml);
	
	// Liste des plugins actifs
	include_spip('inc/plugin');
	actualise_plugins_actifs(); 

	//Attendre eventuellement l'invalidation du cache opcode
	majplugins_attend_invalidation_opcache();

	//Liste des plugins actifs avec une mise à jour issue du Décideur
	include_spip('inc/svp_decider');
	$decideur = new Decideur();
	$liste_plugins_actifs = $decideur->infos_courtes(['pa.actif=' . sql_quote('oui'), 'pa.attente=' . sql_quote('non'),'pa.maj_version!='. sql_quote('')]);

	// Liste des plugins configurés en maj automatique (niveau) avec les packages disponibles (id, version et maj)
 	include_spip('inc/config');
	$config_majplugins = lire_config('majplugins/plugins', array());
	$majplugins=[];
	foreach ($config_majplugins as $key => $value) {
		
		// Données issues de la configuration du plugin
		$majplugins[$key]['niveau'] = $value;
		
		// Données issues du Décideur
		include_spip('svp_fonctions');
		if (isset($liste_plugins_actifs['p'][$key]['i'])
			&& isset($liste_plugins_actifs['p'][$key]['v'])
			&& isset($liste_plugins_actifs['p'][$key]['maj'])){
			$majplugins[$key]['id'] = $liste_plugins_actifs['p'][$key]['i']; // id_plugin
			$majplugins[$key]['prefixe'] = $liste_plugins_actifs['p'][$key]['p']; // prefixe (= $key)
			$majplugins[$key]['nom'] = $liste_plugins_actifs['p'][$key]['n']; // nom 
			$majplugins[$key]['version'] = $liste_plugins_actifs['p'][$key]['v']; // version en local
			$majplugins[$key]['maj'] = $liste_plugins_actifs['p'][$key]['maj']; // version mise à jour
			$majplugins[$key]['diff'] = filtre_svp_diff_xyz($majplugins[$key]['version'],$majplugins[$key]['maj']); // niveau de mise à jour
		}
	}
	
	// Sélection des plugins à mettre à jour (up) en fonction de la configuration du plugin
	include_spip('inc/svp_actionner');
	$a_actionner=[];
	$message_sans_maj ='';
	foreach ($majplugins as $key => $majplugin) {
		if (isset($majplugin['niveau']) && isset($majplugin['diff'] )){
			if (($majplugin['diff'] == 'x' && $majplugin['niveau'] >= 3)
			||($majplugin['diff'] == 'y' && $majplugin['niveau'] >= 2)
			||($majplugin['diff'] == 'z' && $majplugin['niveau'] >= 1)){
				$a_actionner[$majplugin['id']]='up';
			}else{
				if(lire_config('majplugins/attente_maj')){
						$config_sans_maj = _T('majplugins:message_config_sans_maj', [
						'plugin' => $majplugin['nom'],
						'version' => denormaliser_version($majplugin['version']),
						'version_maj' => denormaliser_version($majplugin['maj'])]);
						$message_sans_maj .= $config_sans_maj."\n";
				}
			}
		}
	}

	// Traitement Cron uniquement s'il y a des actions de mise à jour à effectuer
	if (empty($a_actionner)){

		// Envoi des messages en l'absence de mise à jour
		if ($message_sans_maj){
			majplugins_envoyer_courriel($message_sans_maj);
		}
		return 1;
	}

	// Vider les caches pour repartir propre
	majplugins_vider_caches();

	// Vérification des actions (Décideur)
	$decideur->erreur_sur_maj_introuvable = false;
	$decideur->verifier_dependances($a_actionner);
	$message_decideur_erreurs ='';
	$erreurs['decideur_erreurs'] = [];
		if (!$decideur->ok) {
		// Log des erreurs + construction message courriel
		foreach ($decideur->err as $id => $erreurs_id) {
			foreach ($erreurs_id as $erreur) {
				$erreurs['decideur_erreurs'][] = $erreur;
				$message_log[]=['message'=> $erreur, 'fichier' => 'majplugins', 'niveau' => _LOG_ERREUR];
				$message_decideur_erreurs.= $erreur."\n";
			}
		}
	}else{
		// Log des actions et messages courriel
		$actions = [];
		// Actions demandées
		$actions['decideur_demandes'] = $decideur->presenter_actions('ask');
		$message_decideur_demandes ='';
		foreach ($actions['decideur_demandes'] as $action){
			$message_log[]=['message'=> $action, 'fichier' => 'majplugins', 'niveau' => _LOG_INFO_IMPORTANTE];
			$message_decideur_demandes.=$action."\n";
		}
		// Actions proposées
		$actions['decideur_propositions'] = $decideur->presenter_actions('changes');
		$message_decideur_propositions ='';
		foreach ($actions['decideur_propositions'] as $action){
			$message_log[]=['message'=> $action, 'fichier' => 'majplugins', 'niveau' => _LOG_INFO_IMPORTANTE];
			$message_decideur_propositions.=$action."\n";
		}

	// Construction de la liste des actions pour l'Actionneur
	$todo = [];
	foreach ($decideur->todo as $_todo) {
		$todo[$_todo['i']] = $_todo['todo'];
	}

	// Initialisation des actions (Actionneur)
	$actionneur = new Actionneur();
	$actionneur->ajouter_actions($todo);
	$actionneur->verrouiller();
	$actionneur->sauver_actions();

	// Traitement des actions une à une tant qu'il y en a
	$erreurs_actionneur = [];
	do {
		$nb_erreurs = count($actionneur->err);
		$one_action = $actionneur->one_action();
	
	// Récupération des nouvelles erreurs de l'Actionneur à chaque itération
		if (count($actionneur->err) > $nb_erreurs) {
			$erreurs_actionneur = array_merge($erreurs_actionneur, array_slice($actionneur->err, $nb_erreurs));
		}
	} while ($one_action);
	
	$erreurs['actionneur_erreurs'] = $erreurs_actionneur;
	
	// Log des actions réalisées et message courriel
	$actions['actionneur_actions'] = $actionneur->presenter_actions($fin = true);
	// Tentative (abominable) de conversion  du message html en texte: Suppression Tags et Tabulations
	$message_actionneur_actions=strip_tags($actions['actionneur_actions']);
	$message_actionneur_actions=str_replace( array("\t"),array(""),$message_actionneur_actions);
	$message_log[]=['message'=> $message_actionneur_actions, 'fichier' => 'majplugins', 'niveau' => _LOG_INFO_IMPORTANTE];
	}

	// Nettoyage des actions en fin de traitement
	$actionneur->nettoyer_actions();

	// Forcer la suppression du fichier cache_meta.php
	majplugins_supprimer_fichier(_FILE_META);

	// Rafraichissement de la liste des plugins actifs et precompilation des fichiers caches après traitement
	lire_metas();
	actualise_plugins_actifs();
	
	// Invalidation des caches SPIP
	ecrire_meta('derniere_modif', time());

	// Invalidation de l'opcache des fichiers cache après traitement
	majplugins_invalider_caches();

	// Refaire la meta_cache.php
	touch_meta(false, 'meta');

	// Timestamp Fin de traitement
	$timestamp_fin = microtime(true);
	$duree = duree($timestamp_debut,$timestamp_fin,'etendu');
	$message_duree_traitement = _T('majplugins:message_duree_traitement').$duree;
	$message_log[]=['message'=> $message_duree_traitement, 'fichier' => 'majplugins', 'niveau' => _LOG_INFO_IMPORTANTE];

	// Enregistrement des actions pour affichage dans la page de configuration
	// Actions 
	$maj = [];
	$maj=lire_config('majplugins/maj');
		// Limitation affichage aux 5 dernières mises à jour
		if (is_countable($maj) && count($maj) >4){
			$maj = array_slice($maj,count($maj)-4);
		}
	// La clé est le timestamp (à défaut d'instance du traitement Cron)
	$maj[round($timestamp_debut)]['date']= date('Y-m-d H:i:s'); // Date de traitement
	$maj[round($timestamp_debut)]['duree']=$duree; // Durée du traitement
	$maj[round($timestamp_debut)]['actions']=$message_actionneur_actions; // Actions réalisées
	ecrire_config('majplugins/maj', $maj);

	// Erreurs
	if (!empty($erreurs['decideur_erreurs']) || !empty($erreurs['actionneur_erreurs'])){
		$erreurs_stockees =lire_config('majplugins/erreurs');
			// Limitation affichage aux 5 dernières mises à jour
			if (is_countable($erreurs_stockees ) && count($erreurs_stockees ) >4){
				$erreurs_stockees = array_slice($erreurs_stockees ,count($erreurs_stockees )-4);
			}
		// Fusion des erreurs Décideur et Actionneur
		$erreurs_stockees ['fusion'] = array_merge($erreurs['decideur_erreurs'],$erreurs['actionneur_erreurs']);
		
		// La clé est le timestamp (à défaut d'instance du traitement Cron)
		$erreurs_stockees [round($timestamp_debut)]['date']= date('Y-m-d H:i:s'); // Date de traitement
		$erreurs_stockees [round($timestamp_debut)]['messages']=$erreurs_stockees ['fusion']; // Actions en erreur (tableau)
		ecrire_config('majplugins/erreurs', $erreurs_stockees );
		}

	// Crash plugins résiduels
	if (isset($GLOBALS['meta']['message_crash_plugins'])
		and $GLOBALS['meta']['message_crash_plugins']
		and autoriser('configurer', '_plugins', null, $id_auteur)
		and is_array($msg = unserialize($GLOBALS['meta']['message_crash_plugins']))
	) {
		$msg = implode(', ', array_map('joli_repertoire', array_keys($msg)));
		$message_crash_plugins = _T('plugins_erreur', ['plugins' => $msg]);
		$message_log[]=['message'=> $message_crash_plugins, 'fichier' => 'majplugins', 'niveau' => _LOG_ERREUR];
	}

	// Ecriture des logs en masse après la mise à jour des plugins pour éviter un crash du plugin
	loguer($message_log);

	// Envoi du courriel
	$message = '';

	// Mises à jour effectuées
	// Le compte rendu de l'Actionneur inclut également ses éventuelles erreurs
	if (lire_config('majplugins/notification_maj') && !empty($message_actionneur_actions)) {
		$message .= $message_actionneur_actions . "\n";
	}

	// Plugins en attente de mise à jour
	if (lire_config('majplugins/attente_maj') && !empty($message_sans_maj)) {
		$message .= $message_sans_maj . "\n";
	}

	// Erreurs et crashs
	if (lire_config('majplugins/notification_erreurs')) {

		// Erreurs du Décideur
		if (!empty($message_decideur_erreurs)) {
			$message_action = _T('svp:actions_en_erreur');
			$message .= $message_action . $message_decideur_erreurs . "\n";
		}

		// Erreurs Actionneur uniquement si le compte rendu des mises à jour
		// n'est pas déjà envoyé, sinon il y a doublon
		if (
			!lire_config('majplugins/notification_maj')
			&& !empty($erreurs['actionneur_erreurs'])
		) {
			$message_action = _T('svp:actions_en_erreur');
			$message .= $message_action
				. implode("\n", $erreurs['actionneur_erreurs'])
				. "\n";
		}

		// Erreurs XML du Dépoteur
		if (!empty($erreurs_xml)) {
			$message_action = _T('svp:erreurs_xml');
			$message .= $message_action . implode("\n", $erreurs_xml) . "\n";
		}
		
		// Crash plugins
		if (!empty($message_crash_plugins)) {
			$message .= $message_crash_plugins . "\n";
		}
	}

	// Durée du traitement
	if (!empty($message_duree_traitement)&& !empty($message)) {
		$message .= $message_duree_traitement;
	}

	// Envoi du courriel uniquement si un contenu a été sélectionné
	if ($message) {
		majplugins_envoyer_courriel($message);
	}
	
	// lancer et initialiser les nouveaux crons !
	include_spip('inc/genie');
	genie_queue_watch_dist();

	// Suppression des messages résiduels de la meta 'message_crash_plugins'
	lire_metas();
	actualise_plugins_actifs();

	return 1;
}

/**
 * Envoi des courriels
 * 
 * Reprend la fonction standard d'envoi des mail
 * Passage par le pipeline notifications_destinataires
 *
 * @param  mixed $message
 * 		Corps du message 
 * @return void
 */
function majplugins_envoyer_courriel ($message=null) {

	include_spip('inc/envoyer_mail');
	include_spip('inc/config');

	if(lire_config('majplugins/notification')){
		
	// Suppression du dernier saut de ligne inutile	
	$message = preg_replace('/\n$/', '', $message);
	
	// Construction du sujet du mail
	include_spip('inc/texte');
	$base = $GLOBALS['connexions'][0]['db'];
	$sujet = "[" . typo($GLOBALS['meta']['nom_site']). "][Majplugins] ". _T('majplugins:message_mise_a_jour_sujet');

	// Détermination de la liste des destinataires
	$mails = lire_config('majplugins/mails');
	$tous = ($mails) ? explode(',', $mails) : array();

	// Ajout du webmaster
	$tous[] = $GLOBALS['meta']['email_webmaster'];
			
	// Pasage par le pipeline "notifications_destinataires"
	$destinataires = pipeline('notifications_destinataires',
		array(
			'args'=>array('quoi'=>'majplugins','id'=>'','options'=>''),
			'data'=>$tous)
			);

	// Envoi des emails
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	foreach($destinataires as $email){
			$envoyer_mail($email, $sujet, $message, '', '');
			}
	}

	return;
}

/**
 *    Calcul de durée   
 *    
 *    parametres type_affichage
 *    - court   : 5 jours (par defaut)
 *    - etendu  : 4 semaines 3 jours 23 heures 2 minutes
 *    - horaire : 4h39
 *    - minute  : 124 (minutes cumulees)
 *    - iso8601 : P18Y9W4DT11H9M8S   ref. http://fr.wikipedia.org/wiki/ISO_8601#Dur.C3.A9e
 *    - ical    : P18Y9W4DT11H9M8S   ref. http://tools.ietf.org/html/rfc2445#page-37 (mm chose que iso)
 *         
 *    pour sortir une valeur uniquement (i18n)    
 *    - Y       : (an)
 *    - W       : (semaine)
 *    - D       : (jour) 
 *    - H       : (heure)
 *    - M       : (minute)
 *    - S       : (s) 
 *    - MS      : (ms) 
 *                                  
*/
function duree($date_debut,$date_fin,$type_affichage='COURT') {

$type_affichage = strtoupper($type_affichage);	
$diff_seconds_solde  = $date_fin - $date_debut; 

if ($diff_seconds_solde<=0) return '';
	$diff_years    = floor($diff_seconds_solde/31536000);
	$diff_seconds_solde -= $diff_years   * 31536000;  
	$diff_weeks    = floor($diff_seconds_solde/604800);
	$diff_seconds_solde -= $diff_weeks   * 604800;
	$diff_days     = floor($diff_seconds_solde/86400);
	$diff_seconds_solde -= $diff_days    * 86400;
	$diff_hours    = floor($diff_seconds_solde/3600);
	$diff_seconds_solde -= $diff_hours   * 3600;
	$diff_minutes  = floor($diff_seconds_solde/60);
	$diff_seconds_solde -= $diff_minutes * 60;
	$diff_seconds  = floor($diff_seconds_solde);
	$diff_seconds_solde -= $diff_seconds;  
	$diff_milliseconds  = floor($diff_seconds_solde*1000);

 $str = "";
  switch ($type_affichage) {
      case "COURT" :    if ($diff_years>1) $str = $diff_years.' '._T('majplugins:date_ans');
                        else if ($diff_years>0) $str = $diff_years.' '._T('majplugins:date_un_an');
                        else if ($diff_weeks>1) $str = $diff_weeks.' '._T('majplugins:date_semaines');
                        else if ($diff_weeks>0) $str =  $diff_weeks.' '._T('majplugins:date_une_semaine');
                        else if ($diff_days>1) $str = $diff_days.' '._T('majplugins:date_jours');
                        else if ($diff_days>0) $str = $diff_days.' '._T('majplugins:date_un_jour');
                        else if ($diff_hours>1) $str = $diff_hours.' '._T('majplugins:date_heures');
                        else if ($diff_hours>0) $str = $diff_hours.' '._T('majplugins:date_une_heure');
                        else if ($diff_minutes>1) $str = $diff_minutes.' '._T('majplugins:date_minutes');
                        else if ($diff_minutes>0) $str = $diff_minutes.' '._T('majplugins:date_une_minute');          
                        else if ($diff_seconds>1) $str = $diff_seconds.' '._T('majplugins:date_secondes');
                        else if ($diff_seconds>0) $str = $diff_seconds.' '._T('majplugins:date_une_seconde');            
                        else if ($diff_milliseconds>1) $str = $diff_milliseconds.' '._T('majplugins:date_millisecondes');
                        else if ($diff_milliseconds>0) $str = $diff_milliseconds.' '._T('majplugins:date_une_milliseconde');              
                     break;
                  
      case "ETENDU" :   if ($diff_years>1) $str .= $diff_years.' '._T('majplugins:date_ans').' ';
                        else if ($diff_years>0) $str .= $diff_years.' '._T('majplugins:date_un_an').' ';
                        if ($diff_weeks>1) $str .= $diff_weeks.' '._T('majplugins:date_semaines').' ';
                        else if ($diff_weeks>0) $str .= $diff_weeks.' '._T('majplugins:date_une_semaine').' ';
                        if ($diff_days>1) $str .= $diff_days.' '._T('majplugins:date_jours').' ';
                        else if ($diff_days>0) $str .= $diff_days.' '._T('majplugins:date_un_jour').' ';
                        if ($diff_hours>1) $str .= $diff_hours.' '._T('majplugins:date_heures').' ';
                        else if ($diff_hours>0) $str .= $diff_hours.' '._T('majplugins:date_une_heure').' ';
                        if ($diff_minutes>1) $str .= $diff_minutes.' '._T('majplugins:date_minutes').' ';
                        else if ($diff_minutes>0) $str .= $diff_minutes.' '._T('majplugins:date_une_minute').' ';
                        if ($diff_seconds>1) $str .= $diff_seconds.' '._T('majplugins:date_secondes').' ';
                        else if ($diff_seconds>0) $str .= $diff_seconds.' '._T('majplugins:date_une_seconde').' ';
                         if ($diff_milliseconds>1) $str .= $diff_milliseconds.' '._T('majplugins:date_millisecondes').' ';
                        else if ($diff_milliseconds>0) $str .= $diff_milliseconds.' '._T('majplugins:date_une_milliseconde').' ';
                       break;

      case "HORAIRE":   $str = ($diff_hours+($diff_days*24)+($diff_weeks*24*7)+($diff_year*24*7*365))."h";
                        if ($diff_minutes<10) $str .= "0";
                        $str .= $diff_minutes;                                                       
                        break;
                                    
      case "MINUTE":    $str = $diff_minutes+($diff_hours*60)+($diff_days*60*24)+($diff_weeks*60*24*52)+($diff_year*60*24*365);                                    
                        break;
                        
      case "ISO8601":   $str = "P{$diff_years}Y{$diff_weeks}W{$diff_days}DT{$diff_hours}H{$diff_minutes}M{$diff_seconds}S";                                    
                        break;
                        
      case "ICAL":      $str = "P{$diff_years}Y{$diff_weeks}W{$diff_days}DT{$diff_hours}H{$diff_minutes}M{$diff_seconds}S";  // mm chose que iso                                    
                        break;
      
      case "Y":         $str = $diff_years;                                    
                        break;                       
      case "W":         $str = $diff_weeks;                                    
                        break;
      case "D":         $str = $diff_days;                                    
                        break;                  
      case "H":         $str = $diff_hours;                                    
                        break;                       
      case "M":         $str = $diff_minutes;                                    
                        break;
      case "Y":         $str = $diff_years;                                    
                        break;                       
      case "S":         $str = $diff_seconds;                                    
                        break;                     
      case "MS":         $str = $diff_milliseconds;                                    
                        break;                           
     default:          break;
      
  }   

	return $str;
}

	/**
	* Fonction de log en masse
	*
	* @param array $messages
	*/
function loguer($message_log=[]){

	foreach ($message_log as $log) {
	$message = $log['message'] ?? null;
	$fichier = $log['fichier'] ?? 'majplugins';
	$niveau = $log['niveau'] ?? '_LOG_INFO';
	if (!empty($message)) {
		spip_log($message, $fichier.'.'.$niveau);
		}
	}
	return;
}

/**
 * Action de purge du cache
 *
 * Nettoyage de tous les caches 
 *
 * @return void
 */
function majplugins_vider_caches(){
	include_spip('inc/invalideur');

	// Suppression des fichiers et dossiers de cache
	majplugins_supprimer_fichier(_CACHE_RUBRIQUES);
	majplugins_supprimer_fichier(_CACHE_CHEMIN);
	majplugins_supprimer_fichier(_FILE_META);
	majplugins_supprimer_fichier(_DIR_TMP . 'plugin_xml_cache.gz');
	majplugins_supprimer_fichier(_CACHE_PIPELINES);
	majplugins_supprimer_fichier(_CACHE_PLUGINS_PATH);
	majplugins_supprimer_fichier(_CACHE_PLUGINS_FCT);
	majplugins_supprimer_fichier(_CACHE_PLUGINS_OPT);
	purger_repertoire(_DIR_CACHE, ['subdir' => true]);
	purger_repertoire(_DIR_AIDE);
	purger_repertoire(_DIR_VAR . 'cache-css');
	purger_repertoire(_DIR_VAR . 'cache-js');
	effacer_meta('cache_bot_invalide');

	// Suppression $GLOBALS['path_files'] pour forcer son recalcul à l'appel de find_in_path()
	unset ($GLOBALS['path_files']);
	
}

/**
 * Supprime un fichier
 *
 * Supprime un fichier et invalide opcache 
 *
 * @return void
 */
function majplugins_supprimer_fichier($fichier = null) {
    // Vérifie qu'on a bien un chemin valide
    if (!$fichier or !is_string($fichier)) {
        return false;
    }

    // Si le fichier n'existe pas, inutile d'aller plus loin
    if (!@file_exists($fichier)) {
        return false;
    }

    // Initialiser le marqueur global
    $compile = $GLOBALS['fichier_php_compile_recent'] ?? 0;

    // Récupérer le filemtime AVANT suppression
    $mtime = @filemtime($fichier);

    // Supprimer le fichier
    if (@spip_unlink($fichier)) {
        // Mettre à jour la référence de compilation si besoin
        if ($mtime) {
            $GLOBALS['fichier_php_compile_recent'] = max($compile, $mtime);
        }

        // Invalider l’opcode cache sur ce fichier (si OPcache/APC actif)
        if ($real = realpath($fichier)) {
            spip_clear_opcode_cache($real);
        }
       return true; // succès
    }

    return false; // échec de suppression
}

/**
 * Invalider les caches php
 *
 * Invalider tous les fichiers cache php 
 *
 * @return void
 */
function majplugins_invalider_caches(){
	majplugins_invalider_opcache(_CACHE_RUBRIQUES);
	majplugins_invalider_opcache(_CACHE_CHEMIN);
	majplugins_invalider_opcache(_FILE_META);
	majplugins_invalider_opcache(_DIR_TMP . 'plugin_xml_cache.gz');
	majplugins_invalider_opcache(_CACHE_PIPELINES);
	majplugins_invalider_opcache(_CACHE_PLUGINS_PATH);
	majplugins_invalider_opcache(_CACHE_PLUGINS_FCT);
	majplugins_invalider_opcache(_CACHE_PLUGINS_OPT);
}

/**
 * Invalider opcache
 *
 * Invalide l'opcache d'un fichier php
 *
 * @return void
 */
function majplugins_invalider_opcache($fichier=null){
	
	if (@file_exists($fichier)) {
		spip_clear_opcode_cache(realpath($fichier));
	}
}
/**
 * Attendre le temps d'invalidation de l'opcache par sécurité (2 secondes par défaut)
 *
 * Il faut attendre pour être sûr qu'on va bien
 * bénéficier de la recompilation du fichier par l'opcache.
 *
 * @note
 *     Reprend la fonction inc/spip_attend_invalidation_opcode_cache
 */
function majplugins_attend_invalidation_opcache() {
	if (!defined('_MAJPLUGINS_OPCACHE_TIME')){
			define('_MAJPLUGINS_OPCACHE_TIME',2);
	}
	$duree = (int) _MAJPLUGINS_OPCACHE_TIME;
	if ($duree>0) {
		sleep($duree);
		}
	}