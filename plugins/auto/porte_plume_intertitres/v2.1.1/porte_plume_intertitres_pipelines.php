<?php

/**
 * Utilisations de pipelines par Intertitres hierarchiques.
 *
 * @plugin     Intertitres hierarchiques
 *
 * @copyright  2016
 * @author     Mist. GraphX
 *
 * @licence    GNU/GPL
 *
 * @package    SPIP\Porte_plume_intertitres\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline ieconfig pour l'import/export de la configuration.
 *
 * @param array<string, mixed> $table Tableau des metas concernées par IEConfig
 *
 * @return array<string, mixed> Tableau des metas mis à jour avec la meta de Intertitres hiérarchisés
 */
function porte_plume_intertitres_ieconfig_metas(array $table): array {
	$table['porte_plume_intertitres']['titre'] = _T('porte_plume_intertitres:porte_plume_intertitres');
	$table['porte_plume_intertitres']['icone'] = 'prive/themes/spip/images/porte_plume_intertitres-16.svg';
	$table['porte_plume_intertitres']['metas_serialize'] = 'porte_plume_intertitres';

	return $table;
}

/**
 * Ajouter la barre d'outils propre au plugin.
 *
 * @param array $barres Liste des configurations des barres d'outils
 *
 * @return array Liste mise à jour avec les outils propres à Intertitres hiérarchisés
 */
function porte_plume_intertitres_porte_plume_barre_pre_charger(array $barres): array {
	static $base_level = false;

	if (!$base_level) {
		$base_level = get_heading_base_level();
	}

	// Les références et titre on 5 niveau de hierarchie
	// on pars du niveau de départ de la globale et on incrément
	$barre = &$barres['edition'];
	$barre->set('header1', [
		'dropMenu' => [
			[
				'id'            => 'intertitre2',
				'name'          => _T('porte_plume_intertitres:barre_intertitre2'),
				'className'     => 'outil_intertitre' . ($base_level + 1),
				'openWith'      => "\n{{{** ",
				'closeWith'     => "}}}\n",
				'display'       => true,
				'selectionType' => 'line',
			],
			[
				'id'            => 'intertitre3',
				'name'          => _T('porte_plume_intertitres:barre_intertitre3'),
				'className'     => 'outil_intertitre' . ($base_level + 2),
				'openWith'      => "\n{{{*** ",
				'closeWith'     => "}}}\n",
				'display'       => true,
				'selectionType' => 'line',
			],
			[
				'id'            => 'intertitre4',
				'name'          => _T('porte_plume_intertitres:barre_intertitre4'),
				'className'     => 'outil_intertitre' . ($base_level + 3),
				'openWith'      => "\n{{{**** ",
				'closeWith'     => "}}}\n",
				'display'       => true,
				'selectionType' => 'line',
			],
			[
				'id'            => 'intertitre5',
				'name'          => _T('porte_plume_intertitres:barre_intertitre5'),
				'className'     => 'outil_intertitre' . ($base_level + 4),
				'openWith'      => "\n{{{***** ",
				'closeWith'     => "}}}\n",
				'display'       => true,
				'selectionType' => 'line',
			]
		]
	]);

	$barre->ajouterApres('header1', [
		'id'        => 'sepgrp1',
		'separator' => '---------------',
		'display'   => true,
	]);

	include_spip('inc/config');
	if (lire_config('porte_plume_intertitres/afficher_references', 0)) {
		$barre->ajouterApres('sepgrp1', [
			'id'        => 'ref',
			'name'      => _T('barre_intertitre'),
			'className' => 'outil_ref',
			'dropMenu'  => [
				[
					'id'            => 'ref1',
					'name'          => _T('barre_intertitre'),
					'className'     => 'outil_ref1',
					'openWith'      => "\n{{{# ",
					'closeWith'     => "}}}\n",
					'display'       => true,
					'selectionType' => 'line',
				],
				[
					'id'            => 'ref2',
					'name'          => _T('porte_plume_intertitres:barre_intertitre2'),
					'className'     => 'outil_ref2',
					'openWith'      => "\n{{{## ",
					'closeWith'     => "}}}\n",
					'display'       => true,
					'selectionType' => 'line',
				],
				[
					'id'            => 'ref3',
					'name'          => _T('porte_plume_intertitres:barre_intertitre3'),
					'className'     => 'outil_ref3',
					'openWith'      => "\n{{{### ",
					'closeWith'     => "}}}\n",
					'display'       => true,
					'selectionType' => 'line',
				],
				[
					'id'            => 'ref4',
					'name'          => _T('porte_plume_intertitres:barre_intertitre4'),
					'className'     => 'outil_ref4',
					'openWith'      => "\n{{{#### ",
					'closeWith'     => "}}}\n",
					'display'       => true,
					'selectionType' => 'line',
				]
			]
		]);

		$barre->ajouterApres('ref', [
			'id'        => 'sepgrp2',
			'separator' => '---------------',
			'display'   => true,
		]);
	}

	return $barres;
}

/**
 * Définir les liens vers les icones affichés dans le porte-plume.
 *
 * @param array $flux Tableau des liens existants
 *
 * @return array Tableau des liens mis à jour
 */
function porte_plume_intertitres_porte_plume_lien_classe_vers_icone(array $flux): array {
	// Récupérer le niveau de tag debut_intertitre
	$base_level = get_heading_base_level();
	$terminaison = '-xx.svg';
	$icones = [
		'outil_header1'     => ['intertitre_' . $base_level . $terminaison, '0'],
		'outil_intertitre1' => ['intertitre_1' . $terminaison, '0'],
		'outil_intertitre2' => ['intertitre_2' . $terminaison, '0'],
		'outil_intertitre3' => ['intertitre_3' . $terminaison, '0'],
		'outil_intertitre4' => ['intertitre_4' . $terminaison, '0'],
		'outil_intertitre5' => ['intertitre_5' . $terminaison, '0'],
		'outil_intertitre6' => ['intertitre_6' . $terminaison, '0'],
		'outil_intertitre7' => ['intertitre_7' . $terminaison, '0'],
		'outil_ref'         => ['ref' . $terminaison, '0'],
		'outil_ref1'        => ['ref1' . $terminaison, '0'],
		'outil_ref2'        => ['ref2' . $terminaison, '0'],
		'outil_ref3'        => ['ref3' . $terminaison, '0'],
		'outil_ref4'        => ['ref4' . $terminaison, '0']
	];

	return array_merge($flux, $icones);
}

/**
 * Extraire le niveau de base des balise H appliquée dans les pages du site.
 *
 * @return string
 */
function get_heading_base_level() {
	// Récupérer le niveau de base d'après la global
	return preg_match('/[h](\d)/s', $GLOBALS['debut_intertitre'], $level)
		? $level[1]
		: '2';
}
