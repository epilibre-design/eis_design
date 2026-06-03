<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_menus_saisies_dist() {
	include_spip('inc/menus');
	include_spip('inc/config');

	// Récupérer les entrées existantes
	$entrees = menus_lister_disponibles();

	// Trier les entrées par rang
	uasort($entrees, function ($a, $b) {
		$a = $a['rang'];
		$b = $b['rang'];
		#return $a <=> $b; // PHP 7
		return ($a == $b) ? 0 : ($a < $b ? -1 : 1);
	});

	// Remplir la liste des cases
	$data = [];
	foreach ($entrees as $type_entree => $entree) {
		$data[$type_entree] = '<img src="' . $entree['icone'] . '" /> ' . $entree['nom'];
	}

	$saisies = [
		[
			'saisie' => 'checkbox',
			'options' => [
				'nom' => 'entrees_masquees',
				'explication' => _T('menus:configurer_entrees_masquees_explication'),
				'data' => $data,
				'conteneur_class' => 'pleine_largeur',
				'defaut' => lire_config('menus/entrees_masquees', [])
			]
		],
		// Objets
		[
			'saisie' => 'choisir_objets',
			'options' => [
				'nom' => 'objets',
				'label' => _T('menus:configurer_objets_label'),
				'explication' => _T('menus:configurer_objets_explication'),
				'defaut' => lire_config('menus/objets', []),
			]
		]
	];

	return $saisies;
}
