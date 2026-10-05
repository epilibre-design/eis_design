<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require __DIR__ . '/AutoupdateUpdate.php';
class autoupdateRollback extends AutoupdateUpdate
{
	protected function configure() {
		$this
			->setName('autoupdate:rollback')
			->setDescription('Rollback SPIP vers une version antérieure en cas de mise à jour problématique')
			->addOption(
				'to',
				null,
				InputOption::VALUE_REQUIRED,
				'Version vers laquelle rollback SPIP'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		include_spip('inc/autoupdate');
		$version = $GLOBALS['spip_version_branche'];
		$version_to = $input->getOption('to');
		if (empty($version_to)) {
			$this->io->error("Indiquez vers quelle version rollback avec l'option --to");
			return self::FAILURE;
		}

		$urls = autoupdate_urls_patch($version, $version_to);
		if (empty($urls)) {
			$this->io->care("SPIP $version : aucun patch disponible pour rollback vers $version_to");
			return self::SUCCESS;
		}

		include_spip('autoupdate_fonctions');
		// collecter les progress des upates precedents, qu'on ne va pas afficher ici
		$this->collecterNewProgresses();
		$this->io->title("Rollback de SPIP $version vers " . $urls['to']);
		$action_autoupdate = charger_fonction('autoupdate', 'action');
		$action_autoupdate($version, $version_to);

		$this->afficherUpdateProgress();

		return self::SUCCESS;
	}
}
