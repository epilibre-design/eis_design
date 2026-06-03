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

include_spip('prive/squelettes/contenu/skeleditor_fonctions');
/**
 * Afficher l'arborescence du dossier squelette
 *
 * @param string $path_base
 * @param string $current_file
 * @return string
 */
function skeleditor_afficher_arborescence($path_base, $current_file) {

	include_spip('inc/skeleditor');
	$icons = [];
	foreach (['file', 'image'] as $quoi) {
		$icons[$quoi] = chemin_image("se-{$quoi}-16.svg");
	}
	$icons['cadenas'] = chemin_image('cadenas-12.svg');
	$balise_img = chercher_filtre('balise_img');
	$file_list = skeleditor_files_editables($path_base);
	$file_list = skeleditor_sort_directory_first($file_list, $path_base);
	$current_file = substr($current_file, strlen($path_base));
	$output = "<div id='file-browser'><ul class='item-files'>\n";
	$init_dir = $current_dir = '';
	foreach ($file_list as $file) {
		$dir = substr(dirname($file), strlen($path_base));
		$file = substr($file, strlen($path_base));
		if ($dir != $current_dir) {
			$output .= skeleditor_tree_open_close_dir($current_dir, $dir, $current_file);
		}

		$class = '';
		$icon = 'file';
		if (preg_match(',(' . _SE_EXTENSIONS_IMG . ')$,', $file)) {
			$icon = 'image';
		}

		$class .= " $icon";
		$readonly = false;
		if (!is_writable($path_base . $dir) or !is_writable($path_base . $file)) {
			$readonly = true;
			$class .= ' readonly';
		}

		$class .= ($file == $current_file ? ' on' : '');
		$cadenas = ($readonly ? '&nbsp;' . (inserer_attribut($balise_img($icons['cadenas'], '16x16'), 'title', attribut_html(_T('texte_inc_meta_2')))) : '');
		$url = generer_url_ecrire('skeleditor', 'f=' . urlencode($f = $path_base . $file));
		if ($icon === 'image') {
			$url .= '&amp;nomediabox';
		}

		$output .=
			"<li class='item-file$class'>"
			. "<a href='$url' onclick=\"jQuery('#contenu > :first').ajaxReload({history:true,args:{f:'$f'}});jQuery('.item-file.on').removeClass('on');jQuery(this).parent().addClass('on');return false;\">"
			. $balise_img($icons[$icon], $icon, 'picto ' . $icon)
			. basename($file)
			. $cadenas
			. '</a></li>';
	}

	$output .= skeleditor_tree_open_close_dir($current_dir, $init_dir, $current_file);
	$output .= "</ul></div>\n";
	return $output;
}


/**
 * Gestion de l'ouverture/fermeture des repertoires dans le browser de fichiers
 * @param string $current
 * @param string $target
 * @param string $current_file
 * @return string
 */
function skeleditor_tree_open_close_dir(&$current, $target, $current_file) {

	static $icon_folder;
	if (empty($icon_folder)) {
		$balise_img = chercher_filtre('balise_img');
		$icon_folder = chemin_image('se-folder-16.svg');
		$icon_folder = $balise_img($icon_folder, 'directory', 'picto directory');
	}

	if ($current == $target) {
		return '';
	}
	$tcur = explode('/', $current);
	$ttarg = explode('/', $target);
	$tcom = [];
	$output = '';
// la partie commune
	while (reset($tcur) == reset($ttarg)) {
		$tcom[] = array_shift($tcur);
		array_shift($ttarg);
	}
	// fermer les repertoires courant jusqu'au point de fork
	while ($close = array_pop($tcur)) {
		$output .= '</ul></li>';
	}

	$chemin = implode('/', $tcom) . '/';
// ouvrir les repertoires jusqu'a la cible
	while ($open = array_shift($ttarg)) {
		$chemin .= $open . '/';
		$closed = ((strncmp($current_file, ltrim($chemin, '/'), strlen(ltrim($chemin, '/'))) == 0) ? '' : ' closed');
		$class = 'folder' . ($closed ? '' : ' open');
		$output .=
			"<li class='item-file directory'>"
			. "<button class='btn btn_link $class' onclick='jQuery(this).toggleClass(\"open\");'><b class='caret'></b>$icon_folder $open</button>"
			. "<ul class='item-files'>\n";
	}
	$current = $target;
	return $output;
}
