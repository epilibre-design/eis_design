<?php

/**
 * Plugin SkelEditor
 * Editeur de squelette en ligne
 * (c) depuis 2007 Collectif SPIP
 * Licence GPL-v3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/skeleditor');


/**
 * @param array $args
 * @param \Spip\Bigup\Formulaire $formulaire
 * @return \Spip\Bigup\Formulaire
 */
function inc_bigup_medias_formulaire_upload_squelette_dist($args, $formulaire) {
	$formulaire->preparer_input(
		'fichier_upload',
		[
			'multiple' => true,
			'previsualiser' => true,
			'input_class' => 'bigup',
		]
	);
	//$formulaire->inserer_js('bigup.simples.js');

	return $formulaire;
}


/**
 * Chargement du formulaire
 * 4 cas possibles :
 *  - un nouveau fichier ($fichier='')
 *  - un fichier a l'extension interdite
 *  - un fichier de squelettes/, modifiable
 *  - un fichier existant (d'un plugin par exe),
 *    qui doit etre copie dans squelettes/ a l'enregistrement
 *
 * @param string $path_base
 * @param string $fichier
 * @return array
 */
function formulaires_upload_squelette_charger_dist($path_base) {

	$valeurs = [
		'fichier_upload' => '',
		'editable' => true,
		'_path_base' => $path_base,
		'_bigup_rechercher_fichiers' => true,
		'_multiple' => ' ',
	];

	return $valeurs;
}

function formulaires_upload_squelette_verifier_dist($path_base) {
	$erreurs = [];

	$res = skeleditor_check_upload($path_base);

	if (is_string($res)) {
		$erreurs['fichier_upload'] = $res;
	} elseif (!count($res)) {
		$erreurs['fichier_upload'] = _T('skeleditor:erreur_fichier_inexistant');
	}

	return $erreurs;
}


function formulaires_upload_squelette_traiter_dist($path_base) {
	$files = skeleditor_check_upload($path_base);
	$ok = true;
	//spip_log($files, 'skeleditor' . _LOG_DEBUG);

	$erreurs = [];
	include_spip('inc/documents');
	foreach ($files as $file) {
		if (!deplacer_fichier_upload($file['tmp_name'], $path_base . $file['name'], true)) {
			$erreurs[] = 'Erreur lors du deplacement du fichier ' . $file['name'] . " dans $path_base";
			$ok = false;
		}
	}

	if ($ok) {
		$res['message_ok'] = 'ok';
		$url_redirect = parametre_url(self(), 'f', $path_base . $file['name']);
		$url_redirect = parametre_url($url_redirect, 'upload', 'non', '&');
		$res['redirect'] = $url_redirect;
	}
	else {
		$res['message_erreur'] = 'Erreur : ' . implode('<br />', $erreurs);
	}

	return $res;
}


function skeleditor_check_upload($path_base) {
	static $files;

	if (is_null($files)) {
		include_spip('inc/joindre_document');
		$files = skeleditor_trouver_http_post_files();

		spip_log($files, 'skeleditor');

		if (is_array($files)) {
			foreach ($files as $file) {
				if ($e = skeleditor_verifie_nouveau_nom($path_base, $file['name'], true)) {
					return $e;
				}
			}
		}
	}

	return $files;
}


/**
 * Récupérer et mettre en forme la liste des fichiers postes
 * que ce soit via plusieurs input file ou via un input file multiple
 *
 * @see joindre_trouver_http_post_files
 * fonction a supprimer quand on a une compat mini du core qui permet de l'avoir
 *
 * @param $name : nom de l'input qu'on veut recuperer si on ne veut pas tous les fichiers
 * @return array|string
 *   string en cas d'erreur
 */
function skeleditor_trouver_http_post_files($name = null) {
	include_spip('inc/joindre_document');

	$post = isset($_FILES) ? $_FILES : $GLOBALS['HTTP_POST_FILES'];
	$files = [];
	if (is_array($post)) {
		foreach ($post as $input_name => $file) {
			if ($name and $input_name !== $name) {
				continue;
			}
			if (is_array($file['name'])) {
				while (count($file['name'])) {
					$test = [
						'input_name' => $input_name,
						'error' => array_shift($file['error']),
						'name' => array_shift($file['name']),
						'tmp_name' => array_shift($file['tmp_name']),
						'type' => array_shift($file['type']),
					];
					//UPLOAD_ERR_NO_FILE
					if (!($test['error'] == 4)) {
						// un erreur upload
						if (is_string($err = joindre_upload_error($test['error']))) {
							return $err;
						}
						$files[] = $test;
					}
				}
			} else {
				//UPLOAD_ERR_NO_FILE
				if (!($file['error'] == 4)) {
					// un erreur upload
					if (is_string($err = joindre_upload_error($file['error']))) {
						return $err;
					}
					$file['input_name'] = $input_name;
					$files[] = $file;
				}
			}
		}
	}

	return $files;
}
