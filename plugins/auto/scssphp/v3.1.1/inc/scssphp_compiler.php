<?php
use ScssPhp\ScssPhp\ValueConverter;
/*
 * Plugin Scss
 * Distribue sous licence MIT
 *
 */

include_spip('lib/scssphp/scss.inc');
use function \SpipLeague\Component\Kernel\app;

/**
 * @param ScssPhp\ScssPhp\Compiler $scss
 * @param string $rootSourceFile
 * @param array $args
 * @return array
 */
function scssphp_find_in_path($scss, $rootSourceFile, $args) {
	$quote = '';

	$str = $args[0];
	if ($str[0] === ScssPhp\ScssPhp\Type::T_STRING){
		$quote = $str[1];
		$str[1] = '';
	}

	$compiled_filepath = $filepath = $scss->compileValue($str);

	if ($filepath) {
		$filepath = find_in_path($filepath);
		if ($filepath) {
			if (!function_exists('timestamp')) {
				include_spip('inc/filtres');
			}
			$filepath = timestamp($filepath);
		}
	}

	if (!$filepath) {
		$exception = $scss->error("find-in-path($compiled_filepath) : $compiled_filepath non trouvé");
		erreur_squelette($exception->getMessage());
	}

	// rendre le chemin relatif au fichier scss principal compile
	if ($filepath and $rootSourceFile) {
		$sourceFile = explode('/', dirname($rootSourceFile));
		$relativePath = explode('/', $filepath);
		while (count($sourceFile)
			and reset($sourceFile) === reset($relativePath)) {
			array_shift($sourceFile);
			array_shift($relativePath);
		}
		while (count($sourceFile)) {
			array_unshift($relativePath, '..');
			array_shift($sourceFile);
		}
		$filepath = implode('/', $relativePath);
	}

  return [ScssPhp\ScssPhp\Type::T_STRING, $quote, [$filepath ? $filepath : '']];
}

/**
 * Compilation inline des scss, en appelant directement le compilateur
 *
 * @param string $style
 * @param array $contexte
 * @param array $cache_options
 * @param array $import_dirs
 * @param array $scss_vars
 * @return array
 *   [out, erreur]
 */
function scssphp_inline_compile($style, $contexte, $cache_options, $import_dirs, $scss_vars = []) {
	$log_pre = "scss_compile ";
	if (empty($_SERVER['HTTP_HOST'])) {
		$log_pre .= "[spip-cli] ";
	}

	spip_timer('scss_compile');

	// le compilateur ScssPhp\ScssPhp\Compiler compile le contenu
	$scss = new ScssPhp\ScssPhp\Compiler($cache_options);
	$scss->setOutputStyle(ScssPhp\ScssPhp\OutputStyle::EXPANDED);

	// lui transmettre le path qu'il utilise pour les @import
	$scss->setImportPaths($import_dirs);

	// ajouter la fonction find-in-path()
	$rootSourceFile = $contexte['file'] ?? '';
	$scss->registerFunction(
		'find-in-path',
		function ($args) use ($scss, $rootSourceFile) { return scssphp_find_in_path($scss, $rootSourceFile, $args); },
		['filepath']
	);

	// pouvoir importer des @import 'css/truc' sur fichier 'css/truc.scss.html'
	$scss->addImportPath(function($path) {
		// SPIP 3.0+
		if (function_exists('produire_fond_statique')) {
			if ($f = find_in_path($path . '.scss.html')) {
				$f = produire_fond_statique($path . '.scss', array('format' => 'scss'));
				$f = supprimer_timestamp($f);
				return $f;
			}
		}
		return null;
	});

	if ($scss_vars) {
		$vars = array_map(function($value) {
			if (in_array($value, [null, true, false, ''], true)) {
				return ValueConverter::fromPhp($value);
			}
			return ValueConverter::parseValue($value);
		}, $scss_vars);
		$scss->addVariables($vars);
	}

	// Inline source maps
	// https://scssphp.github.io/scssphp/docs/#source-maps
	if (defined('_SCSS_SOURCE_MAP') && constant('_SCSS_SOURCE_MAP') === true) {
		// A partir de SPIP 4.4, la constante _ROOT_CWD est dépréciée (et supprimée en 5.0)
		$cwd = _SPIP_VERSION_ID >= 40400
			? app()->getCwd()
			: _ROOT_CWD;
		$cwd = rtrim($cwd, '/') . '/';
		$scss->setSourceMap(ScssPhp\ScssPhp\Compiler::SOURCE_MAP_INLINE);
		$scss->setSourceMapOptions([
			// This value is prepended to the individual entries in the 'source' field.
			'sourceRoot' => '',
			// an optional name of the generated code that this source map is associated with.
			'sourceMapFilename' => null,
			// url of the map
			'sourceMapURL' => null,
			// absolute path to a file to write the map to
			'sourceMapWriteTo' => null,
			// output source contents?
			'outputSourceFiles' => false,
			// base path for filename normalization
			'sourceMapRootpath' => '/',
			// base path for filename normalization
			// difference between file & url locations, removed from ALL source files in .map
			'sourceMapBasepath' => $cwd
	  ]);
	}

	$files = [];
	try {
		$result = $scss->compileString($style, $contexte['file'] ?? null);

		$out = $result->getCss();
		$files = $result->getIncludedFiles();

		spip_log($log_pre. ' compile '. ($contexte['file'] ?? substr($style,0,100)) . ' :: '.spip_timer('scss_compile'), 'scssphp');
	} catch (Exception $ex) {
		// en cas d'erreur, on retourne du vide...
		spip_log($log_pre. ' SCSSPHP Compiler fatal error:'.$ex->getMessage(), 'scssphp'._LOG_ERREUR);

		$display_file = '';
		if (isset($contexte['file'])) {
			$display_file = $contexte['file'];
			if (strpos($ex->getMessage(), '.scss') !== false) {
				$display_file = basename($display_file);
			}
			$display_file= " fichier $display_file";
		}

		$erreur = 'SCSS : Echec compilation'
			. $display_file
			. '<br />' . $ex->getMessage();

		return ['', $erreur];
	}

	// si on a rien parse parce que fichier en cache, indiquer au moins le fichier source concerne dans l'en-tete
	if (!$files and !empty($contexte['file'])) {
		$files = [$contexte['file'] => true];
	}

	if ($files and count($files)){
		$files = array_keys($files);
		$l = strlen(_DIR_RACINE);
		$lr = strlen(_ROOT_RACINE);
		foreach($files as $k=>$file){
			if ($l and strncmp($file,_DIR_RACINE,$l)==0){
				$files[$k] = substr($file,$l);
			}
			if ($lr and strncmp($file,_ROOT_RACINE,$lr)==0){
				$files[$k] = substr($file,$lr);
			}
		}
		$out = "/*\n#@".implode("\n#@",$files)."\n*"."/\n" . $out;
	}


	return [$out, null];
}


/**
 * Compilation cli des scss, en appelant directement spip cli via un exec
 * marche uniquement en local, mais utile sur les poste de dev dont xdebug est actif
 * car la compilation scssphp prend une plombe en xdebug et en passant en cli on la fait echapper
 *
 * @param string $style
 * @param array $contexte
 * @param array $cache_options
 * @param array $import_dirs
 * @param array $scss_vars
 * @return array
 *   [out, erreur]
 */
function scssphp_cli_compile($style, $contexte, $cache_options, $import_dirs, $scss_vars = []) {
	$spip_path_bin = "/usr/local/bin/spip";
	if (defined('_SCSSPHP_SPIP_CLI_BIN')) {
		$spip_path_bin = _SCSSPHP_SPIP_CLI_BIN;
	}

	$dir_taches = sous_repertoire(_DIR_TMP, 'scssphp');

	$tmp_file_name = $dir_taches . md5(creer_uniqid() . getmypid()) . ".json";

	$args = [
		'style' => $style,
		'contexte' => $contexte,
		'cache_options' => $cache_options,
		'import_dirs' => $import_dirs,
		'scss_vars' => $scss_vars,
	];

	file_put_contents($tmp_file_name, json_encode($args));

	$arg_tache = $tmp_file_name;
	$command = "";

	$command .= "$spip_path_bin scssphp:compile --tache=$arg_tache";
	$output = [];
	$return_var = 0;
	exec($command, $output, $return_var);
	$output = implode("\n", $output);

	// erreur !
	if ($return_var) {
		$output = trim($output);
		if ($return_var == 127) {
			$output .= "\nErreur #$return_var $spip_path_bin non trouvé : definissez le bon path pour spip-cli dans la constante _SCSSPHP_SPIP_CLI_BIN\n";
		}
		elseif (!$output) {
			$output = "Erreur #$return_var a l'execution de $spip_path_bin";
		}
		spip_log('scssphp_cli_compile compile Erreur #'.$return_var.' commande: ' . $command, 'scssphp' . _LOG_ERREUR);
		return ['', $output];
	}

	@unlink($tmp_file_name);

	return [$output, null];
}
