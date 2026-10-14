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
 * Version cible d’un patch de mise à jour pour la version courante, sinon chaîne vide
 * @see prive/squelettes/contenu/autoupdate.html
 */
function autoupdate_version_patch(string $version): string {
	$version = $version ?: $GLOBALS['spip_version_branche'];
	include_spip('inc/autoupdate');
	$urls = autoupdate_urls_patch($version);
	return $urls['to'] ?? '';
}

/**
 * Versions cibles de rollback depuis la version installée (API `/3/{from}`).
 *
 * @return list<string> de la plus récente à la plus ancienne
 * @see prive/squelettes/navigation/autoupdate.html
 */
function autoupdate_rollback_disponibles(string $version = ''): array {
	$version = $version ?: $GLOBALS['spip_version_branche'];
	include_spip('genie/mise_a_jour');
	$infos = info_maj_cache_v(3, false, $version);
	if (empty($infos['rollback']) || !is_array($infos['rollback'])) {
		return [];
	}
	$versions = array_keys($infos['rollback']);
	usort($versions, 'version_compare');

	return array_reverse($versions);
}

/**
 * Trouver un package pour rollback vers la version précédente si possible
 * (la version immédiatement antérieure disponible — rétrocompat.)
 * @see prive/squelettes/navigation/autoupdate.html
 */
function autoupdate_rollback_disponible(string $version): string {
	$version = $version ?: $GLOBALS['spip_version_branche'];
	$disponibles = autoupdate_rollback_disponibles($version);

	return $disponibles[0] ?? '';
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

/**
 * @param string $voir_rapport hash dont l’aperçu (déjà généré) doit être affiché
 */
function autoupdate_affiche_progress_status($voir_rapport = ''): string {
	include_spip('inc/autoupdate');
	include_spip('inc/filtres');
	include_spip('inc/filtres_boites');
	include_spip('inc/filtres_alertes');

	if (!is_string($voir_rapport) || $voir_rapport === '') {
		$req = _request('voir_rapport');
		$voir_rapport = is_string($req) ? $req : '';
	}
	if ($voir_rapport !== '') {
		include_spip('action/autoupdate_report');
		if (!autoupdate_hash_rapport_valide($voir_rapport)) {
			$voir_rapport = '';
		}
	}

	$logs = autoupdate_collecter_progresses();
	if (empty($logs)) {
		return '';
	}

	$out = '';
	$i = 0;
	foreach (array_reverse($logs, true) as $hash => $infos) {
		$i++;
		$date = !empty($infos['mtime']) ? affdate_heure(date('Y-m-d H:i:s', $infos['mtime'])) : '';
		$titre = _T('autoupdate:titre_bloc_mise_a_jour', ['hash' => $hash])
			. ($date ? ' <span class="date">(' . $date . ')</span>' : '');

		$out .= boite_ouvrir($titre, 'simple');

		$show_logs = '<pre class="autoupdate-log">' . spip_htmlspecialchars($infos['log']) . "</pre>\n";
		$show_logs = '<details class="autoupdate-log-details"' . ($i === 1 ? ' open' : '') . '><summary>Log</summary>' . $show_logs . '</details>';
		$out .= $show_logs;

		$alert_class = '';
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
				$alert_class = 'success';
				break;
			case Status::PATCHED_ERRORS:
				$alert_class = 'notice';
				break;
			case Status::FAILED:
			case Status::FAILED_ROLLBACK:
				$alert_class = 'error';
				break;
			case Status::FINISHED:
			case Status::FINISHED_UPGRADED:
				$alert_class = ($has_errors || $has_warnings) ? 'notice' : 'success';
				break;
			default:
				$alert_class = 'info';
				break;
		}
		$bouton = '';

		if ($infos['status'] === Status::PROCESSING && $infos['processing'] !== '') {
			$out .= '<pre class="autoupdate-log">' . spip_htmlspecialchars($infos['processing']) . "</pre>\n";
		}
		if ($infos['status'] !== Status::UNKNOWN) {
			$rapport_envoye = ($dir && is_file($dir . 'reported.json')) ? $dir . 'reported.json' : '';
			if ($rapport_envoye !== '') {
				$url_voir = generer_url_ecrire('autoupdate_status', 'voir_rapport=' . $hash);
				$bouton = '<a class="btn btn_secondaire" href="' . $url_voir . '">'
					. _T('autoupdate:bouton_voir_rapport') . '</a>';
			} else {
				$url_apercu = generer_url_ecrire('autoupdate_status', 'voir_rapport=' . $hash, true);
				$url_action_preview = generer_action_auteur('autoupdate_report_preview', $hash, $url_apercu);
				$bouton = bouton_action(_T('autoupdate:bouton_voir_rapport'), $url_action_preview, 'btn btn_secondaire');
			}
			$url_action_report = generer_action_auteur('autoupdate_report', $hash, self());
			$bouton .= bouton_action(_T('autoupdate:bouton_rapport_debug'), $url_action_report, 'btn btn_secondaire');
			if ($rapport_envoye !== '') {
				$out .= message_alerte(_T('autoupdate:info_rapport_envoye', [
					'date' => affdate_heure(date('Y-m-d H:i:s', filemtime($rapport_envoye))),
				]), '', 'info', 'status') . "\n";
			}
			if (
				in_array($infos['status'], [Status::PATCHED_OK, Status::PATCHED_ERRORS], true)
				&& is_dir($dir = autoupdate_patch_ready_dir($hash))
			) {
				$url_action = generer_url_action('autoupdate_finish', "arg=$hash&hash=" . sha_file($dir . 'manifest-translated.json'), true, true);
				$url_action = parametre_url($url_action, 'redirect', url_absolue(self()), '&');
				$bouton .= bouton_action(_T('autoupdate:bouton_finir_mise_a_jour'), $url_action, 'btn');
			}
			$bouton = $bouton ? "<div class='float-end autoupdate-actions'>$bouton</div>" : '';
			$out .= message_alerte(
				_T('autoupdate:info_status', ['status' => $status]) . " $bouton",
				'',
				$alert_class
			) . "\n";
			if ($voir_rapport !== '' && hash_equals($voir_rapport, $hash)) {
				$out .= autoupdate_afficher_apercu_rapport($hash);
			}
		}
		if ($has_errors) {
			$out .= message_alerte(
				'<pre class="autoupdate-log">' . spip_htmlspecialchars($has_errors) . '</pre>',
				_T('autoupdate:titre_erreurs'),
				'error'
			) . "\n";
		}
		if ($has_warnings) {
			$out .= message_alerte(
				'<pre class="autoupdate-log">' . spip_htmlspecialchars($has_warnings) . '</pre>',
				_T('autoupdate:titre_warnings'),
				'notice'
			) . "\n";
		}
		$out .= $refresh . boite_fermer();
	}

	return $out;
}

/**
 * Message flash après tentative d’envoi du rapport (paramètre d’URL `autoupdate_report`).
 *
 * @param mixed $resultat
 */
function autoupdate_message_resultat_rapport($resultat): string {
	if (!is_string($resultat) || $resultat === '') {
		return '';
	}
	include_spip('inc/filtres_alertes');
	switch ($resultat) {
		case 'ok':
			return message_alerte(_T('autoupdate:info_rapport_envoi_ok'), '', 'success', 'status') . "\n";
		case 'deja':
			return message_alerte(_T('autoupdate:info_rapport_deja_envoye'), '', 'info', 'status') . "\n";
		case 'erreur':
			return message_alerte(_T('autoupdate:erreur_rapport_envoi'), '', 'error') . "\n";
		case 'impossible':
			return message_alerte(_T('autoupdate:erreur_rapport_generation'), '', 'error') . "\n";
		default:
			return '';
	}
}

/**
 * Affiche le rapport déjà envoyé, ou à défaut l’aperçu préparé sans envoi.
 */
function autoupdate_afficher_apercu_rapport(string $hash): string {
	include_spip('inc/filtres');
	include_spip('inc/filtres_alertes');
	include_spip('action/autoupdate_report');

	$envoye = autoupdate_fichier_rapport_envoye($hash);
	$fichier = $envoye ?? autoupdate_fichier_apercu_rapport($hash);
	if ($fichier === null || !is_file($fichier)) {
		return message_alerte(_T('autoupdate:erreur_apercu_rapport'), '', 'error') . "\n";
	}
	$json = file_get_contents($fichier);
	if ($json === false || $json === '') {
		return message_alerte(_T('autoupdate:erreur_apercu_rapport'), '', 'error') . "\n";
	}
	if ($envoye !== null) {
		$json = autoupdate_json_rapport_lisible($json);
	}

	$date = affdate_heure(date('Y-m-d H:i:s', filemtime($fichier)));
	if ($envoye !== null) {
		$titre = _T('autoupdate:titre_rapport_envoye');
		$legende = _T('autoupdate:info_rapport_contenu_envoye', ['date' => $date]);
	} else {
		$titre = _T('autoupdate:titre_apercu_rapport');
		$legende = _T('autoupdate:info_apercu_rapport', ['date' => $date]);
	}

	return '<details class="autoupdate-log-details autoupdate-report-preview" open>'
		. '<summary>' . $titre . '</summary>'
		. '<p>' . $legende . '</p>'
		. '<pre class="autoupdate-log">' . spip_htmlspecialchars($json) . "</pre>\n"
		. "</details>\n";
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
