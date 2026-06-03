<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class ScssphpCompile extends Command {
	protected function configure() {
		$this
			->setName('scssphp:compile')
			->setDescription('Lancer la compilation d\'un fichier scss directement ou via un fichier tache json')
			->addOption(
				'fichier',
				null,
				InputOption::VALUE_OPTIONAL,
				'Fichier .scss a compiler en utilisant le path SPIP et les plugins installés',
				null
			)
			->addOption(
				'tache',
				null,
				InputOption::VALUE_OPTIONAL,
				'Fichier json contenant les entrees [\'style\'=>$style, \'contexte\'=>$contexte, \'cache_options\'=>$cache_options, \'import_dirs\' => $import_dirs, \'scss_vars\' => $scss_vars]',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		include_spip('inc/scssphp_compiler');

		$fichier = $input->getOption('fichier');
		if ($fichier) {
			$contenu = false;
			if (!lire_fichier($fichier, $contenu)) {
				$output->writeln("Fichier $fichier introuvable");
				return Command::FAILURE;
			}

			// compiler le SCSS si besoin (ne pas generer une erreur si source vide
			if (!$contenu) {
				$out = "/* Source $fichier : vide */\n";
			} else {
				include_fichiers_fonctions();
				try {
					$out = scss_compile($contenu, array('file'=>$fichier), true);
				}
				catch (Exception $e) {
					$output->writeln("<error>".$e->getMessage()."</error>");
					return Command::FAILURE;
				}
			}

			// si erreur de compilation on renvoit un commentaire, et il y a deja eu un log
			if (!$out) {
				$out = "/* Compilation $fichier : vide */\n";
			}

		}
		else {
			$tache = $input->getOption('tache');
			if (!$tache
				or !file_exists($tache)
				or !$json = file_get_contents($tache)
				or !$json = json_decode($json, true) ) {
				$output->writeln("<error>Indiquez un fichier json valide pour `--tache={$tache}`</error>");
				return Command::FAILURE;
			}

			$args = [
				'style' => '',
				'contexte' => [],
				'cache_options' => [],
				'import_dirs' => [],
				'scss_vars' => []
			];

			$args = array_merge($args, $json);

			list($out, $error) = scssphp_inline_compile($args['style'], $args['contexte'], $args['cache_options'], $args['import_dirs'], $args['scss_vars']);
			if ($error) {
				$output->writeln($error);
				return Command::FAILURE;
			}
		}

		echo $out;

		return Command::SUCCESS;
	}
}
