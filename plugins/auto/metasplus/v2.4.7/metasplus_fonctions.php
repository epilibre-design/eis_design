<?php
/**
 * Fonctions utiles au plugin Métas+
 *
 * @plugin     Métas+
 * @copyright  2016-2018
 * @author     Tetue, Erational, Tcharlss
 * @licence    GNU/GPL
 * @package    SPIP\Metas+\Fonctions
 */

/**
 * Retrouver le contexte d'après l'URL : type de page, objet éventuel
 *
 * @Note
 * Il n'est pas recommandé d'utiliser $GLOBALS['contexte],
 * donc on utilise la fonction qui décode l'URL
 * et retourne un tableau linéaire avec les bonnes infos :
 * [0]            => page (le fond)
 * [1][id_patate] => identifiant si page d'un objet
 * [1][erreur]    => erreur éventuelle (404)
 *
 * @param string $url
 * @return array
 *   Tableau associatif :
 *   array{
 *     type-page: string  (type de page),
 *     erreur:    bool    (true si page ou décodage en erreur),
 *     objet:     string  (type d'objet éventuel),
 *     id_objet:  int     (numéro d'objet éventuel),
 *     id_patate: int     (numéro d'objet éventuel)
 *   }
 */
function metasplus_identifier_contexte($url) {

	// Decoder_url nous donne quelques infos sur la page actuelle
	// type-page n'est rempli que pour les objets éditoriaux
	$decoder_url = charger_fonction('decoder_url', 'urls');
	$decodage    = $decoder_url($url);
	$type_page   = (!empty($decodage[0]) ? $decodage[0] : '');
	$erreur      = (isset($decodage[1]['erreur']) ? true : false);

	// On retourne au minimum le type de page et l'erreur
	$contexte = array(
		'type-page' => $type_page,
		'erreur'    => $erreur,
	);

	// On complète les infos pour repérer la page d'accueil, les pages autonomes,
	// et donner des choses complémentaires sur l'éventuel objet.
	if (!$erreur) {

		// S'agit-il d'une page autonome ? (?page=truc)
		$query_page    = (defined('_SPIP_PAGE') ? _SPIP_PAGE : 'page');
		$page_autonome = _request($query_page);

		// Soit c'est la page d'un objet
		if ($type_page) {
			include_spip('base/objets');
			$cle_objet = id_table_objet($type_page);
			$id_objet = (isset($decodage[1][$cle_objet]) ? intval($decodage[1][$cle_objet]) : 0);
			if ($id_objet) {
				$contexte['objet']    = $type_page;
				$contexte['id_objet'] = $id_objet;
				$contexte[$cle_objet] = $id_objet; // ça peut servir
			}

		// Soit c'est une page autonome ?page=truc
		} elseif ($page_autonome) {
			$contexte['type-page'] = $page_autonome;

		// Soit c'est la page d'accueil
		} else {
			$contexte['type-page'] = 'sommaire';

			// Exception pour le plugin multidomaines : on est dans une rubrique
			// TODO : faire un pipeline
			if (test_plugin_actif('multidomaines')) {
				$multidomaines = lire_config('multidomaines');
				foreach ($multidomaines as $id_rubrique => $domaine) {
					if ($domaine['url'] && rtrim($url, '/') == $domaine['url']) {
						$contexte['type-page']  = 'rubrique';
						$contexte['erreur']     = false;
						$contexte['objet']      = 'rubrique';
						$contexte['id_objet']   = $id_rubrique;
						$contexte['id_article'] = $id_rubrique;
						$contexte['url']        = $url;
					}
				}
			}
		}
	}

	return $contexte;
}


/**
 * Sélectionner le squelette des métadonnées pour un type de page
 *
 * On cherche dans l'ordre :
 *
 * - 1) inclure/metaplus/<type-page>-<composition>.html
 * - 2) inclure/metaplus/<type-page>.html
 * - 3) inclure/metaplus/dist.html
 *
 * @param array $contexte
 *     Contexte de la page, avec le type de page, le type d'objet etc.
 * @return string
 *     Le fond
 */
function metasplus_selectionner_fond($contexte) {

	include_spip('inc/utils');

	$fond             = '';
	$type_page        = (!empty($contexte['type-page']) ? $contexte['type-page'] : '');
	$objet            = (!empty($contexte['objet']) ? $contexte['objet'] : '');
	$id_objet         = (!empty($contexte['id_objet']) ? intval($contexte['id_objet']) : '');
	$composition      = '';
	$racine           = 'inclure/metasplus/';
	$fond_defaut      = $racine . 'dist';
	$fond_page        = $racine . $type_page;
	$fond_composition = '';

	if (
		test_plugin_actif('compositions')
		and $objet
		and $id_objet
		and include_spip('compositions_fonctions')
		and $composition = compositions_determiner($objet, $id_objet)
	) {
		$fond_composition = $fond_page . '-' . $composition;
	}

	// En priorité, le fond de la composition du type de page
	if ($composition and find_in_path($fond_composition.'.html')) {
		$fond = $fond_composition;
	// Sinon le fond du type de page
	} elseif (find_in_path($fond_page.'.html')) {
		$fond = $fond_page;
	// Sinon le fond générique
	} elseif (find_in_path($fond_defaut.'.html')) {
		$fond = $fond_defaut;
	}

	return $fond;
}

/**
 * @param array $args
 * @param \Spip\Bigup\Formulaire $formulaire
 * @return \Spip\Bigup\Formulaire
 */
function inc_bigup_medias_formulaire_configurer_metasplus_dist($args, $formulaire) {
	$formulaire->preparer_input_class(
		'bigup', // 'file' pour rendre automatique.
		['previsualiser' => true]
	);
	return $formulaire;
}
