<?php
/**
 * Plugin Tickets
 * Licence GPL (c) 2008-2013
 *
 * Formulaire d'édition de tickets
 * 
 * @package SPIP\Tickets\Formulaires
 */

if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

include_spip('inc/autoriser');
include_spip('inc/actions');
include_spip('inc/editer');
include_spip('inc/filtres'); # Pour extraire_muti dans creer_ticket_public.html

/**
 * Identification unique d'un formulaire poste
 *  
 * @param int $id_ticket[optional]
 * 		Identifiant numérique du ticket ou new si nouveau
 * @param string $retour[optional]
 * 		URL de retour
 * @param string $associer_objet[optional]
 * 		Objet à lier au ticket (ex: "article|12")
 * @param string $config_fonc[optional]
 * 		Fonction de configuration du formulaire
 * @param array $row[optional]
 * @return string
 */
function formulaires_creer_ticket_public_identifier($id_ticket='new', $retour='', $associer_objet='', $config_fonc='tickets_edit_config', $row=array(), $hidden=''){
	return serialize(array(intval($id_ticket)));
}

/**
 * Fonction de chargement des valeurs du formulaire
 * 
 * @param int $id_ticket[optional]
 * 		Identifiant numérique du ticket ou new si nouveau
 * @param string $retour[optional]
 * 		URL de retour
 * @param string $associer_objet[optional]
 * 		Objet à lier au ticket (ex: "article|12")
 * @param string $config_fonc[optional]
 * 		Fonction de configuration du formulaire
 * @param array $row[optional]
 * @return array $valeurs
 * 		Un tableau des valeurs chargées au formulaire
 */
function formulaires_creer_ticket_public_charger($id_ticket='new', $retour='', $associer_objet='', $config_fonc='tickets_edit_config', $row=array(), $hidden=''){
	// mettre une valeur new pour formulaires_editer_objet_charger()
	if (!intval($id_ticket)) $id_ticket='oui'; // oui pour le traitement de l'action (new, c'est pas suffisant)

	if (!autoriser('ecrire', 'ticket', $id_ticket, '', array('associer_objet'=>$associer_objet)))
		$valeurs['editable'] = false;
	else{
		if(is_numeric($id_ticket) && !autoriser('modifier','ticket',$id_ticket))
			$valeurs['editable'] = false;
		else{
			$valeurs = formulaires_editer_objet_charger('ticket',$id_ticket,0,0,$retour,$config_fonc,$row,$hidden);
			$valeurs['editable'] = true;
		
			$valeurs['groupesmots'] = array_map('array_shift', sql_allfetsel("id_groupe", "spip_groupes_mots", "FIND_IN_SET('tickets', tables_liees)"));
			foreach ($valeurs['groupesmots'] as $id_groupe) {
				$valeurs['groupemots_'.$id_groupe] = valeur_champ_groupemots_ticket('tickets', $id_ticket.'-'.$id_groupe, 'groupemots_ticket');
			}

			// si nouveau ticket
			if (!$id_ticket OR $id_ticket=='oui'){
				$valeurs['id_assigne'] = $GLOBALS['visiteur_session']['id_auteur'];
				// Si un des champs de ce tableau est passé dans l'URL, on l'utilise dans le formulaire
				foreach(array('id_assigne','exemple') as $champ){
					if(!$valeurs[$champ] && _request($champ))
						$valeurs[$champ] = _request($champ);
				}
				// Si on passe groupemots_xx dans l'URL, on l'utilise dans le formulaire (que ce soit un tableau ou un indice)
				foreach ($valeurs['groupesmots'] as $id_groupe) {
					if (!$valeurs['groupemots_'.$id_groupe] AND _request('groupemots_'.$id_groupe)) {
						$valeurs['groupemots_'.$id_groupe] = _request('groupemots_'.$id_groupe);
					}
				}
			}
		}
	}
	$valeurs['public'] = test_espace_prive() ? '' : 'on';

	return $valeurs;
}

/**
 *
 * Fonction de vérification des valeurs
 *
 * @param int $id_ticket[optional]
 * 		Identifiant numérique du ticket ou new si nouveau
 * @param string $retour[optional]
 * 		URL de retour
 * @param string $associer_objet[optional]
 * 		Objet à lier au ticket (ex: "article|12")
 * @param string $config_fonc[optional]
 * 		Fonction de configuration du formulaire
 * @param array $row[optional]
 * @return array $erreurs
 * 		Un tableau des erreurs de validation
 */
function formulaires_creer_ticket_public_verifier($id_ticket='new', $retour='', $associer_objet='', $config_fonc='tickets_edit_config', $row=array(), $hidden=''){

	$erreurs = formulaires_editer_objet_verifier('ticket',$id_ticket,array('titre','texte'));
	
	/**
	 * Utilisation des fonctions de nospam pour filtrer un peu
	 */
	if (include_spip('inc/nospam')) {
		include_spip('inc/texte');
		include_spip('inc/nospam');
		$texte = _request('texte');
		$caracteres = nospam_compter_caracteres_utiles($texte);
		// moins de 10 caracteres sans les liens = spam !
		if ($caracteres < 10)
			$erreurs['texte'] = _T('forum:forum_attention_dix_caracteres');

		// on analyse le titre
		$infos_titre = nospam_analyser_spams(_request('titre'));
		// si un lien dans le titre = spam !
		if ($infos_titre['nombre_liens'] > 0)
			$erreurs['titre'] = _T('nospam:erreur_spam');
		// on analyse le texte
		$infos_texte = nospam_analyser_spams($texte);

		if ($infos_texte['nombre_liens'] > 0) {
			// plus de 3 liens = spam, sauf si todo est là et donc 6 liens
			$max_liens = defined('_DIR_PLUGIN_TODO') ? 6 : 3;
			// si un lien a un titre de moins de 3 caracteres = spam, sauf si todo est là
			$max_caracteres_liens = defined('_DIR_PLUGIN_TODO') ? 1 : 3;
			if ($infos_texte['caracteres_texte_lien_min'] < $max_caracteres_liens)
				$erreurs['texte'] = _T('nospam:erreur_spam');
			if ($infos_texte['nombre_liens'] > $max_liens && !isset($GLOBALS['visiteur_session']['id_auteur']))
				$erreurs['texte'] = _T('nospam:erreur_spam');
		}

		if ($erreurs) {
			echo '{ "alert": "success", "message": "' . $erreurs['texte'] . '" }';
			exit();
		}

	}

	return $erreurs;
}

function tickets_edit_config(){
	return array();
}

/**
 * Fonction de traitement du formulaire
 *
 * @param int $id_ticket[optional]
 * 		Identifiant numérique du ticket ou new si nouveau
 * @param string $retour[optional]
 * 		URL de retour
 * @param string $config_fonc[optional]
 * 		Fonction de configuration du formulaire
 * @param array $row[optional]
 * @return array $message
 * 		Un tableau des éléments de retour du formulaire CVT
 */
function formulaires_creer_ticket_public_traiter($id_ticket='new',$retour='', $associer_objet='', $config_fonc='tickets_edit_config', $row=array(), $hidden=''){


	$err = '';
	
	$id_auteur = $GLOBALS['visiteur_session']['id_auteur'];
	$id_ticket = ticket_inserer($id_auteur);

	// Enregistre l'envoi dans la BD
	if ($id_ticket > 0) $err = ticket_modifier($id_ticket,null);


	if ($err) {
		die('{ "alert": "error", "message": "' . addslashes($err) . '" }');
		exit();
	}
	else {
		die('{ "alert": "success", "message": "' . _T('tickets:ticket_enregistre') . '" }');
		exit();

	}

	return $message;
}

/**
 * Création d'un nouveau ticket
 *
 * Lorsqu'on cree un ticket anonyme,
 * on stocke l'adresse ip ; cela peut servir pour filtrer des spam
 *
 * @param int $id_auteur
 * 	Identifiant numérique de l'auteur qui crée le ticket
 * @return int $id_ticket
 * 	Identifiant numérique du nouveau ticlet
 */
function ticket_inserer($id_auteur=null) {
	$ip = $id_auteur ? '' : $GLOBALS['ip'];

	$champs = array(
		'statut' =>  'redac',
		'date' => date('Y-m-d H:i:s'),
		'date_modif' => date('Y-m-d H:i:s'),
		'ip' => $ip,
		'id_auteur' => $id_auteur,
		'id_assigne' => 0
		);

	// Envoyer aux plugins
	$champs = pipeline('pre_insertion',
		array(
			'args' => array(
				'table' => 'spip_tickets',
			),
			'data' => $champs
		)
	);

	$id_ticket = sql_insertq("spip_tickets", $champs);

	pipeline('post_insertion',
		array(
			'args' => array(
				'table' => 'spip_tickets',
				'id_objet' => $id_ticket
			),
			'data' => $champs
		)
	);

	return $id_ticket;
}

/**
 * Mettre a jour un ticket
 *
 *
 * @param int $id_ticket
 * 	L'identifiant numérique du ticket à modifier
 * @param array|null $set
 * 	Un array des valeurs par défaut si appel direct de la fonction
 * @return string $err
 * 	L'erreur retournée sinon ''
 */
function ticket_modifier($id_ticket, $set=null) {
	$err = '';

	include_spip('inc/modifier');
	include_spip('inc/filtres');
	$c = collecter_requests(
		// white list
		objet_info('ticket','champs_editables'),
		// black list
		array('date','statut','id_auteur'),
		// donnees eventuellement fournies
		$set
	);

	$invalideur = "id='ticket/$id_ticket'";
	$indexation = true;

	if ($err = objet_modifier_champs('ticket', $id_ticket,
		array(
			'data' => $set,
			'nonvide' => array('titre' => _T('ticket:nouveau_ticket')." "._T('info_numero_abbreviation').$id_ticket),
			'invalideur' => $invalideur,
			'indexation' => $indexation,
			'date_modif' => 'date_modif' // champ a mettre a date('Y-m-d H:i:s') s'il y a modif
		),
		$c)) {
		return $err;
	}

	// Invalider les caches
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_ticket/$id_ticket'");

	return $err;
}
