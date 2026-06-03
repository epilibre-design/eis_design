<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BootstrapBuildIcons extends Command {
	protected $liste_min = ['person', 'calendar', 'chevron-left', 'chevron-right', 'chat', 'tag', 'check-box', 'skip-end', 'skip-start'];

	protected function configure() {
		$this
			->setName('bootstrap:build:icons')
			->setDescription('Générer les sprites d\'icones Bootstrap à partir du repo bootstrap-icons')
			->addOption(
				'path',
				null,
				InputOption::VALUE_REQUIRED,
				'Chemin vers le dossier dans lequel est checkout le projet https://github.com/twbs/icons',
				null
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$path = $input->getOption('path');
		if (!$path) {
			if ($p = find_in_path('bootstrap2spip/img/icons')) {
				$path = realpath(_ROOT_RACINE . dirname($p));
				$this->io->care("path = $path");
			}
		}
		if (!$path) {
			$this->io->error('Indiquez un path');
			return self::FAILURE;
		}

		$path = rtrim($path, '/');
		$path_icons = $path . '/icons';
		if (!is_dir($path_icons)) {
			$this->io->error('Indiquez un path qui contient le dossier icons/ de TWBS');
			return self::FAILURE;
		}

		$files = glob($path_icons . '/*.svg');

		$sprite = '';
		$sprite_fill = '';
		$sprite_min = '';

		foreach ($files as $file) {
			$svg = file_get_contents($file);
			$svg = explode('>', $svg, 2);
			$svg[0] = str_replace('width="1em" height="1em" ', '', $svg[0]);
			$svg[0] = str_replace('width="16" height="16" ', '', $svg[0]);
			$svg[0] = str_replace(' fill="currentColor"', '', $svg[0]);
			$svg[0] = str_replace(' xmlns="http://www.w3.org/2000/svg"', '', $svg[0]);
			$svg[0] = str_replace('class="bi bi-', 'id="bi-', $svg[0]);
			$svg[0] = str_replace('<svg ', '<symbol ', $svg[0]);
			$svg = implode('>', $svg);
			$svg = str_replace('</svg>', '</symbol>', $svg);

			if (strpos($file, '-fill') !== false) {
				$sprite_fill .= "$svg\n";
			} else {
				$sprite .= "$svg\n";
			}
			if (in_array(basename($file, '.svg'), $this->liste_min)) {
				$sprite_min .= "$svg\n";
			}
		}

		$sprite_all = "<svg xmlns=\"http://www.w3.org/2000/svg\">\n$sprite\n$sprite_fill</svg>";
		$sprite = "<svg xmlns=\"http://www.w3.org/2000/svg\">\n$sprite</svg>";
		$sprite_fill = "<svg xmlns=\"http://www.w3.org/2000/svg\">\n$sprite_fill</svg>";
		$sprite_min = "<svg xmlns=\"http://www.w3.org/2000/svg\">\n$sprite_min</svg>";

		file_put_contents($path . '/bi-all-symbols.svg', $sprite_all);
		file_put_contents($path . '/bi-symbols.svg', $sprite);
		file_put_contents($path . '/bi-fill-symbols.svg', $sprite_fill);
		file_put_contents($path . '/bi-min-symbols.svg', $sprite_min);

		$this->io->care('Built terminé');
		passthru("ls -l $path/bi-*.svg");

		return self::SUCCESS;
	}
}
