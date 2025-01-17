<?php

/**
 * Callback Intertitres hierarchiques.
 *
 * @plugin     Intertitres hierarchiques
 *
 * @copyright  2016
 * @author     Mist. GraphX
 *
 * @licence    GNU/GPL
 *
 * @package    SPIP\Porte_plume_intertitres\wheels
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Callback de la wheel intertitre qui calcule le HTML de chaque intertitre.
 *
 * @param array $t Tableau issu du passage de la regexp de la wheel. Son format est le suivant :
 *                 0 - la chaine complète
 *                 1 - le groupe ouvrant
 *                 2 - le type|level - ou string/contenu si intertitre spip
 *                 3 - le contenu
 *                 4 - le groupe fermant
 *                 5 - les attributs additionnels, class, id, propriété ou indicateur de raz de la numérotation (facultatif).
 *
 * @return string Chaine HTML en sortie de la wheel
 */
function intertitres(array $t): string {
	// Initialisation des variables de base : ne pas utiliser de static car on peut mettre des intertitres
	// dans d'autres champs que le texte.
	static $base_level = 0;
	static $type = '';
	static $last_node = [];
	static $intertitres = [];

	// On calcule la signature de l'intertitre à partir de la chaine du titre et du node précédent de façon à
	// contextualiser la position de l'intertitre courant si le texte decelui-ci se répète dans la page.
	$hash = md5(serialize($last_node) . $t[0]);
	if (isset($intertitres[$hash])) {
		// Eviter de retraiter deux fois les intertitres en incrémentant les numéros éventuels
		// Cela peut arriver avec le plugin Sommaire Automatique qui va générer parfois plusieurs appels à propre().
		$html = $intertitres[$hash];
	} else {
		// Extraction des attributs additionnels du titre : classes, id, propriétés additionnelles et indicateur de raz
		// - on effectue ce traitement à ce stade car l'attribut {raz} permet de remettre à zéro les compteurs de numérotation
		$attributs = extract_attributes($t[5] ?? '');
		if ($attributs['raz']) {
			// On remet tous les compteurs à zéro
			$base_level = 0;
			$type = '';
			$last_node = [];
		}

		// Ajuster le niveau de l'item
		if (!$base_level) {
			// Récupérer le niveau de base d'après la global
			preg_match('/[h](\d)/s', $GLOBALS['debut_intertitre'], $matches);
			$base_level = $matches[1];
		}
		$level = $t[2]
			? $base_level + strlen($t[2]) - 1
			: $base_level;

		// Déterminer le type de classe des titres : h ou r
		if (!$type) {
			$type = substr($t[2], 0, 1) === '#' ? 'r' : 'h';
		}

		// Calcul de la classe minimale de la balise hx
		$css = $type . $level;

		// Extraction du texte du titre
		$title = $t[3];

		// Numérotation du texte si demandé
		if ($type === 'r') {
			// Il faut numéroter les titres en considérant qu'ils sont détectés dans l'ordre.
			$imbric_level = $t[2] ? strlen($t[2]) - 1 : 0;
			if (isset($last_node[$imbric_level])) {
				// On rajoute un nouveau noeud
				$node = array_slice($last_node, 0, $imbric_level + 1);
				$node[$imbric_level] = isset($node[$imbric_level])
					? $node[$imbric_level] + 1
					: 1;
			} else {
				// On rajoute d'un nouveau niveau
				$node = $last_node;
				$node[$imbric_level] = 1;
			}
			$title = format_title($title, $node);
			$last_node = $node;
		}

		// Traitements des attributs hors raz (class, id, propriété)
		$id = '';
		$properties = '';
		if ($attributs['css']) {
			$css .= $attributs['css'];
		}
		if ($attributs['id']) {
			$id = "id=\"{$attributs['id']}\"";
		}
		if ($attributs['properties']) {
			$properties = ' ' . $attributs['properties'];
		}

		// Calcul du HTML compilé pour le titre : on ne dépasse pas h6
		if ($level < 7) {
			$html = "<h{$level} {$id} class=\"{$css}\"{$properties}>" . $title . "</h{$level}>";
		} else {
			$html = "<div {$id} class=\"{$css}\"{$properties}>" . $title . '</div>';
		}

		// On consigne l'intertitre maintenant
		$intertitres[$hash] = $html;
	}

	return $html;
}

/**
 * Formate le titre si celui-ci doit être numéroté.
 *
 * @param string $title Le texte du titre sans numérotation
 * @param array  $node  Tableau définissant la hiérarchie du titre
 *
 * @return string Titre numéroté
 */
function format_title(string $title, array $node): string {
	$number = '';
	foreach ($node as $value) {
		$number .= $number ? ".{$value}" : $value;
	}
	$title = "{$number} - {$title}";

	return $title;
}

/**
 * Détermine la liste des attributs additionnels à ajouter à la balise hx.
 * Pour permettre d'utiliser des numéros dans plusieurs champs d'un objet, on ajoute un attribut `raz`
 * indiquant à la wheel qu'il faut remettre les compteurs à zéro.
 *
 * @param string $contenu Chaine extraite du titre regroupant tous les attributs saisis
 *
 * @return array<string, mixed> Tableau des attributs contenant toujours les index `css`, `id`, `properties`, `raz`.
 */
function extract_attributes(string $contenu): array {
	// initialisation des attributs compilés : tous les index sont initalisés
	$attributs = [
		'raz'        => false,
		'css'        => '',
		'id'         => '',
		'properties' => '',
	];

	if ($contenu) {
		// Chaque attribut est séparé par un espace
		$contenu = preg_split('/[\s]+/', $contenu);
		foreach ($contenu as $attribut) {
			if ($attribut === 'raz') {
				// Indicateur de renumérotation
				$attributs['raz'] = true;
			} elseif ($class = get_css($attribut)) {
				// Classes CSS : plusieurs possibles
				$attributs['css'] .= ' ' . $class;
			} elseif ($id = get_id($attribut)) {
				// Id : un seul possible
				$attributs['id'] = $id;
			} elseif ($propertie = get_propertie($attribut)) {
				// Propriétés : plusieurs possibles
				$attributs['properties'] .= $propertie;
			}
		}
	}

	return $attributs;
}

/**
 * Renvoie les classes additionnelles de la balise hx ou la chaine vide sinon.
 *
 * @param string $attribut Chaine d'un attribut du titre
 *
 * @return string La classe CSS extraite ou la chaine vide sinon.
 */
function get_css(string $attribut): string {
	return preg_match('/\.([_a-zA-Z0-9-]+)/s', $attribut, $class_name)
		? $class_name[1]
		: '';
}

/**
 * Renvoie l'id de la balise hx ou vide sinon.
 *
 * @param string $attribut Chaine d'un attribut du titre
 *
 * @return string La valeur de l'id extraite ou la chaine vide sinon.
 */
function get_id(string $attribut): string {
	return preg_match('/#([_a-zA-Z0-9-]+)/s', $attribut, $id_name)
		? $id_name[1]
		: '';
}

/**
 * Renvoie les propriétés additionnelles de la balise hx ou vide sinon.
 *
 * @param string $attribut Chaine d'un attribut du titre
 *
 * @return string La propriété extraite ou la chaine vide sinon.
 */
function get_propertie(string $attribut): string {
	return preg_match('/([_a-z-]+=[\"|\'].*?[\"|\'])/s', $attribut, $propertie)
		? $propertie[1]
		: '';
}
