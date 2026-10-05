<?php

use Spip\Cli\Console\Command;
use Spip\Plugin\Autoupdate\Patch\Status;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoupdateUpdate extends Command
{
	protected $deja_log = [];

	protected function configure() {
		$this
			->setName('autoupdate:update')
			->setDescription('Mettre à jour SPIP si nécessaire')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		include_spip('inc/autoupdate');
		$version = $GLOBALS['spip_version_branche'];

		$urls = autoupdate_urls_patch($version);
		if (empty($urls)) {
			$this->io->care("SPIP $version : aucune mise à jour disponible");
			return self::SUCCESS;
		}

		include_spip('autoupdate_fonctions');
		// collecter les progress des upates precedents, qu'on ne va pas afficher ici
		$this->collecterNewProgresses();
		$this->io->title("Mise à jour de SPIP $version vers " . $urls['to']);
		$action_autoupdate = charger_fonction('autoupdate', 'action');
		$action_autoupdate($version);

		$this->afficherUpdateProgress();

		return self::SUCCESS;
	}

	protected function collecterNewProgresses() {
		$new_progresses = [];
		$infos_logs = autoupdate_collecter_progresses();
		foreach ($infos_logs as $hash => $infos_log) {
			$status = $infos_log['status'];
			$log = $infos_log['log'];
			$processing = $infos_log['processing'];
			$crc32 = crc32($log . $processing);
			if (empty($this->deja_log[$hash])) {
				$new_progresses[$hash] = $infos_log;
				$this->deja_log[$hash] = [
					'crc32' => $crc32,
					'logs' => explode("\n", $log),
					'processing' => explode("\n", $processing),
					'status' => $status,
				];
			} elseif ($crc32 !== $this->deja_log[$hash]['crc32']) {
				$new_progresses[$hash] = $infos_log;
				$difflog = array_diff(explode("\n", $log), $this->deja_log[$hash]['logs']);
				$new_progresses[$hash]['log'] = implode("\n", $difflog);
				$diffprocessing = array_diff(explode("\n", $processing), $this->deja_log[$hash]['processing']);
				$new_progresses[$hash]['log'] = implode("\n", $difflog);
				$new_progresses[$hash]['processing'] = implode("\n", $diffprocessing);
				$this->deja_log[$hash] = [
					'crc32' => $crc32,
					'logs' => explode("\n", $log),
					'processing' => explode("\n", $processing),
					'status' => $status,
				];
			}
		}
		return $new_progresses;
	}

	protected function afficherUpdateProgress() {

		$last_changed = 0;
		$last_section = null;
		do {
			$changed = false;
			$infos_logs = $this->collecterNewProgresses();
			if (!empty($infos_logs)) {
				$all_finished = true;
				$changed = true;
				$last_changed = time();
				foreach ($infos_logs as $hash => $infos_log) {
					$status = $infos_log['status'];
					if ($last_section !== $hash) {
						$this->io->section("Mise à jour de SPIP $hash");
					}
					if (!empty($infos_log['log'])) {
						$this->io->text($infos_log['log']);
					}
					if ($status === Status::PROCESSING && !empty($infos_log['processing'])) {
						$this->io->text($infos_log['processing']);
						$all_finished = false;
					} elseif (!$this->afficherStatus($status)) {
						$all_finished = false;
					}
					$last_section = $hash;
				}
				if ($all_finished) {
					break;
				}
			}
		} while ($changed === true || $last_changed > time() - 10);
	}

	protected function afficherStatus($status): bool {
		$aff_status = _T(Status::label($status));
		switch ($status) {
			case Status::FINISHED:
			case Status::FINISHED_UPGRADED:
				$this->io->check("Status : $aff_status");
				return true;
			case Status::PATCHED_OK:
				$this->io->check("Status : $aff_status");
				return false;
			case Status::PATCHED_ERRORS:
				$this->io->care("Status : $aff_status");
				return false;
			case Status::FAILED:
			case Status::FAILED_ROLLBACK:
				$this->io->fail("Status : $aff_status");
				return true;
			default:
				$this->io->text("Status : $aff_status");
				return false;
		}
	}
}
