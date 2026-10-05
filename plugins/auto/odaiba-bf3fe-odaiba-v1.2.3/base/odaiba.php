<?php
if (!defined("_ECRIRE_INC_VERSION")) {
	return;
}

function odaiba_declarer_champs_extras($champs = array()) {

	// Table spip_rubriques
	$champs['spip_rubriques']['odaiba_type_rubrique'] = array(
		'saisie' => 'radio',
		'options' => array(
			'nom' => 'odaiba_type_rubrique',
			'label' => _T('odaiba:odaiba_type_rubrique'),
			'sql' => "varchar(30) NOT NULL DEFAULT ''",
			'defaut' => 'tri_date',
			'data' => array(
				'tri_date' => _T('odaiba:odaiba_type_rubrique_tri_date'),
 				'tri_num' => _T('odaiba:odaiba_type_rubrique_tri_num'),
			),
			'restrictions'=>array('voir' => array('auteur' => ''),	//Tout le monde peut voir
							'modifier' => array('auteur' => 'webmestre')),	//Seuls les webmestres peuvent modifier
		),
	);

	return $champs;
}
