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

define('_SE_EXTENSIONS_IMG', 'jpg|png|gif|ico|bmp|svg');
define('_SE_EXTENSIONS', _SE_EXTENSIONS_IMG . '|htm|html|xml|yaml|json|php|py|sh|sql|css|scss|less|rdf|txt|nfo|log|js|as|csv');

/**
 * Determiner le dossier de travail
 *
 * @return string
 */
function skeleditor_path_editable() {
	static $path = null;
	if (!is_null($path)) {
		return $path;
	}
	// charger les plugin qui peuvent definir un squelette
	if (@is_readable(_DIR_SESSIONS . 'charger_plugins_fonctions.php')) {
		// chargement optimise precompile
		include_once(_DIR_SESSIONS . 'charger_plugins_fonctions.php');
	}

	$c = creer_chemin();
	while (count($c)) {
		$d = array_shift($c);
		if ($path and strncmp($d, $path, strlen($d)) !== 0) {
			break;
		}
		if (
			strncmp($d, _DIR_PLUGINS, strlen(_DIR_PLUGINS)) !== 0
			and strncmp($d, _DIR_PLUGINS_DIST, strlen(_DIR_PLUGINS_DIST)) !== 0
			and strncmp($d, _DIR_RACINE . 'prive/', strlen(_DIR_RACINE . 'prive/')) !== 0
			and strncmp($d, _DIR_RACINE . 'squelettes-dist', strlen(_DIR_RACINE . 'squelettes-dist')) !== 0
			and $d !== _DIR_RACINE
			and (!defined('_DIR_SITE') or $d !== _DIR_SITE)
			and sous_repertoire($d, '', false, true)
		) {
			$path = $d;
		}
	}

	// si pas de dossier skel mais qu'on peut creer squelettes/ c'est ok

	// Traiter le cas des sites mutualises
	$dir_squelettes_site = (defined('_DIR_SITE')) ? _DIR_SITE : _DIR_RACINE;

	if (!$path or defined('_DIR_SITE')) {
		$path = sous_repertoire($dir_squelettes_site, 'squelettes', false, true);
		// sous_repertoire renvoie '' en cas d'echec
		if ($path == '') {
			$path = null;
		}
	}

	return $path;
}

/**
 * Lister les fichiers editables
 * ie qu'on peut voir, acceder (ne presume pas du caractere readonly du fichier)
 *
 * @param null|string $path
 * @return array
 */
function skeleditor_files_editables($path = null) {
	if (is_null($path)) {
		$path = skeleditor_path_editable();
	}
	if (!$path) {
		return [];
	}

	$files_editable = preg_files($path, '[.](' . _SE_EXTENSIONS . ')$');

	return $files_editable;
}


/**
 * Trier la liste des fichiers venant de skeleditor_files_editables() avec repertoires en premier,
 * pour affichage
 *
 * @see skeleditor_files_editables
 *
 * @param array $files
 * @param string $root
 * @return array
 */
function skeleditor_sort_directory_first($files, $root_path = null) {
	if (is_null($root_path)) {
		$root_path = skeleditor_path_editable();
	}

	$files_root = [];
	$files_directory = [];
	foreach ($files as $file) {
		if (dirname($file) . '/' !== $root_path) {
			$files_directory[] = $file;
		} else {
			$files_root[] = $file;
		}
	}

	return array_merge($files_directory, $files_root);
}

function skeleditor_get_file_content_type_ctrl($fichier) {
	if (!$fichier) {
		$type = 'txt';
		$content = '';
		$ctrl = md5($content);
	} elseif (preg_match(',(' . _SE_EXTENSIONS_IMG . ')$,ims', $fichier)) {
		$type = 'img';
		$ctrl = md5(filemtime($fichier));
		$content = null;
	} else {
		$type = 'txt';
		lire_fichier($fichier, $content);
		$ctrl = md5($content);
	}
	return [$content, $type, $ctrl];
}


function skeleditor_verifie_nouveau_nom($path_base, $filename, $img = false) {
	$erreur = '';
	if (strpos($filename, '../') !== false) {
		$erreur = _T('skeleditor:erreur_sansgene'); // fichier existe deja
	} elseif (file_exists($path_base . $filename)) {
		$erreur = _T('skeleditor:erreur_overwrite');
	} // fichier existe deja
	else {
		if (
			!preg_match(',[.](' . _SE_EXTENSIONS . ')$,i', $filename)
			or (!$img and preg_match(',[.](' . _SE_EXTENSIONS_IMG . ')$,i', $filename))
		) {
			$erreur = _T('skeleditor:erreur_type_interdit');
		} else {
			list($chemin, $echec) = skeleditor_cree_chemin($path_base, $filename);
			if (!$chemin) {
				if (!is_dir($echec)) {
					$erreur = _T('skeleditor:erreur_creation_sous_dossier', ['dir' => joli_repertoire("$echec")]);
				} else {
					$erreur = _T('skeleditor:erreur_ecrire_dans_sous_dossier', ['dir' => joli_repertoire("$echec")]);
				}
			}
		}
	}
	return $erreur;
}

function skeleditor_cree_chemin($path_base, $file) {
	$chemin = $path_base;
	$sous = explode('/', $file);
	$filename = array_pop($sous); // inutilise ici

	$chemin_ok = '';
	while ($chemin and count($sous) and $s = array_shift($sous)) {
		$chemin_ok = $chemin;
		$chemin = sous_repertoire($chemin, $s, false, true);
	}

	return [$chemin, $chemin ? '' : "$chemin_ok$s"];
}

/**
 * retrouver le chemin dont il vient pour extraire la racine
 * permet aussi de s'assurer que le fichier qu'on copie provient uniquement
 * du path, et pas d'autre part sur le serveur
 *
 * @param string $source
 * @return string
 */
function skeleditor_nom_copie($source) {
	$file = '';
	// cas particulier des squelettes obtenus par scaffolding
	if (strncmp($source, _DIR_CACHE . 'scaffold/', $l = strlen(_DIR_CACHE . 'scaffold/')) == 0) {
		return substr($source, $l);
	}
	$spip_path = creer_chemin();
	$spip_path = array_diff($spip_path, [_DIR_RACINE]);
	$spip_path[] = _DIR_RACINE;

	foreach ($spip_path as $dir) {
		if (strncmp($source, $dir, strlen($dir)) == 0) {
			$possible = substr($source, strlen($dir));
			if (!$file or strlen($possible) < strlen($file)) {
				$file = $possible;
			}
		}
	}
	return $file;
}

/**
 * Commenter l'entete d'un fichier copie
 *
 * @param string $source
 * @param string $content
 * @return string
 */
function skeleditor_commente_copie($source, $content) {
	/* preparer un commenaite */
	$comment = _T('skeleditor:copy_comment', ['date' => date('Y-m-d H:i:s'), 'nom' => $GLOBALS['visiteur_session']['nom'], 'source' => joli_repertoire($source)]);
	$infos = pathinfo($source);
	if (in_array($infos['extension'], ['php', 'php3', 'php4', 'php5', 'php6', 'css', 'js', 'as'])) {
		$comment = "/*\n$comment\n*/\n";
	} elseif ($infos['extension'] == _EXTENSION_SQUELETTES and preg_match(',(<BOUCLE|<INCLU[RD]E|#ENV|#ID_|#REM|#INCLU[RD]E|#TITRE|#[F-Z][A-Z]*),Ums', $content)) {
		$comment = "[(#REM)\n$comment\n]\n";
	} elseif (in_array($infos['extension'], ['htm', 'html', 'xml', 'svg', 'rdf'])) {
		$comment = "<!--\n$comment\n-->\n";
	} else {
		$comment = '';
	}
	return $comment . $content;
}
