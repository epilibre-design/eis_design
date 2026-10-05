<?php

use Spip\Plugin\Autoupdate\Patch\Status;

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Trouver un patch de mise à jour pour la version courante si possible
 * @see prive/squelettes/inclure/mise_a_jour.html
 */
function autoupdate_patch_disponible(string $version): bool {
	$version = $version ?: $GLOBALS['spip_version_branche'];
	include_spip('inc/autoupdate');
	if (autoupdate_urls_patch($version)) {
		return true;
	}
	return false;
}

/**
 * Trouver un package pour rollback vers la version précédente si possible
 * (la version précédente disponible en tout cas)
 * @see prive/squelettes/navigation/autoupdate.html
 */
function autoupdate_rollback_disponible(string $version): string {
	$version = $version ?: $GLOBALS['spip_version_branche'];
	include_spip('genie/mise_a_jour');
	$infos = info_maj_cache_v(3);
	if (empty($infos)) {
		return '';
	}
	if (!empty($infos['versions'][$version]['packages']['rollback'])) {
		$versions_rollback = array_keys($infos['versions'][$version]['packages']['rollback']);
		sort($versions_rollback);
		return end($versions_rollback);
	}
	return '';
}

/**
 * Résumé compact des mises à jour récentes (sans logs complets)
 * @see prive/squelettes/contenu/autoupdate.html
 */
function autoupdate_affiche_resume_progress(): string {
	include_spip('inc/autoupdate');
	include_spip('inc/filtres');

	$logs = autoupdate_collecter_progresses();
	if (empty($logs)) {
		return '';
	}

	$out = "<ul class='spip'>\n";
	foreach (array_reverse($logs, true) as $hash => $infos) {
		$date = !empty($infos['mtime']) ? affdate_heure(date('Y-m-d H:i:s', $infos['mtime'])) : '';
		$status = _T(Status::label($infos['status']));
		$hash_court = substr($hash, 0, 12);
		$out .= '<li>'
			. ($date ? "<span class='date'>$date</span> — " : '')
			. '<tt>' . spip_htmlspecialchars($hash_court) . '…</tt>'
			. ' : ' . spip_htmlspecialchars($status)
			. "</li>\n";
	}
	$out .= "</ul>\n";

	return $out;
}

function autoupdate_affiche_progress_status(): string {
	include_spip('inc/autoupdate');
	include_spip('inc/filtres');
	include_spip('inc/filtres_boites');

	$logs = autoupdate_collecter_progresses();
	if (empty($logs)) {
		return '';
	}

	$out = '';
	foreach (array_reverse($logs, true) as $hash => $infos) {
		$date = !empty($infos['mtime']) ? affdate_heure(date('Y-m-d H:i:s', $infos['mtime'])) : '';
		$titre = _T('autoupdate:titre_bloc_mise_a_jour', ['hash' => $hash])
			. ($date ? ' <span class="date">(' . $date . ')</span>' : '');

		$out .= boite_ouvrir($titre, 'simple');
		$out .= '<pre class="autoupdate-log">' . spip_htmlspecialchars($infos['log']) . "</pre>\n";

		$class = 'msg-alert ';
		$status = _T(Status::label($infos['status']));
		$has_errors = '';
		$has_warnings = '';
		$dir = '';
		if (is_dir($dir = autoupdate_patch_ready_dir($hash)) || is_dir($dir = autoupdate_patch_trash_dir($hash))) {
			if (file_exists($f = $dir . 'errors.txt')) {
				$has_errors = file_get_contents($f);
			}
			if (file_exists($f = $dir . 'warnings.txt')) {
				$has_warnings = file_get_contents($f);
			}
		}
		$refresh = '';
		switch ($infos['status']) {
			case Status::PROCESSING:
				$refresh = '<meta http-equiv="refresh" content="10">';
				break;
			case Status::PATCHED_OK:
				$refresh = '<meta http-equiv="refresh" content="10">';
				$class .= 'success';
				break;
			case Status::PATCHED_ERRORS:
				$class .= 'notice';
				break;
			case Status::FAILED:
			case Status::FAILED_ROLLBACK:
				$class .= 'error';
				break;
			case Status::FINISHED:
			case Status::FINISHED_UPGRADED:
				$class .= ($has_errors ? 'notice' : ($has_warnings ? 'notice' : 'success'));
				break;
			default:
				$class .= 'info';
				break;
		}
		$bouton = '';

		if ($infos['status'] === Status::PROCESSING && $infos['processing'] !== '') {
			$out .= '<pre class="autoupdate-log">' . spip_htmlspecialchars($infos['processing']) . "</pre>\n";
		}
		if ($infos['status'] !== Status::UNKNOWN) {
			$url_action_report = generer_action_auteur('autoupdate_report', $hash, self());
			$bouton = bouton_action(_T('autoupdate:bouton_rapport_debug'), $url_action_report, 'btn btn_secondaire');
			if ($dir && file_exists($f = $dir . 'reported.json')) {
				$out .= '<p><tt>' . _T('autoupdate:info_rapport_envoye', [
					'date' => affdate_heure(date('Y-m-d H:i:s', filemtime($f))),
				]) . "</tt></p>\n";
			}
			if (
				in_array($infos['status'], [Status::PATCHED_OK, Status::PATCHED_ERRORS], true)
				&& is_dir($dir = autoupdate_patch_ready_dir($hash))
			) {
				$url_action = generer_url_action('autoupdate_finish', "arg=$hash&hash=" . sha_file($dir . 'manifest-translated.json'), true, true);
				$url_action = parametre_url($url_action, 'redirect', url_absolue(self()), '&');
				$bouton .= bouton_action(_T('autoupdate:bouton_finir_mise_a_jour'), $url_action, 'btn');
			}
			$bouton = $bouton ? "<div class='float-end'>$bouton</div>" : '';
			$out .= "<div class='$class clearfix'>" . _T('autoupdate:info_status', ['status' => $status]) . " $bouton</div>\n";
		}
		if ($has_errors) {
			$out .= "<div class='msg-alert error'><h4>" . _T('autoupdate:titre_erreurs') . '</h4>'
				. '<pre class="autoupdate-log">' . spip_htmlspecialchars($has_errors) . '</pre></div>' . "\n";
		}
		if ($has_warnings) {
			$out .= "<div class='msg-alert notice'><h4>" . _T('autoupdate:titre_warnings') . '</h4>'
				. '<pre class="autoupdate-log">' . spip_htmlspecialchars($has_warnings) . '</pre></div>' . "\n";
		}
		$out .= $refresh . boite_fermer();
	}

	return $out;
}

function autoupdate_collecter_progresses(): ?array {
	$logs = [];
	$files = glob(_DIR_TMP . 'autoupdate/progress-*.txt');
	if (!empty($files)) {
		$progress_files = [];
		foreach ($files as $file) {
			$t = filemtime($file);
			if ($t > time() - 86400) {
				$progress_files[$file] = filemtime($file);
			}
		}
		asort($progress_files);

		foreach ($progress_files as $file => $mtime) {
			$log = file_get_contents($file);
			$hash = substr(basename($file, '.txt'), 9);
			$dir = autoupdate_patch_ready_dir($hash);
			$status = autoupdate_patch_status($dir, true);
			$processing = '';
			if (file_exists($f = $dir . 'processing.txt')) {
				$processing = @file_get_contents($f);
			}
			$logs[$hash] = [
				'log' => $log,
				'processing' => $processing,
				'status' => $status,
				'mtime' => $mtime,
			];
		}

	}
	return $logs;
}
