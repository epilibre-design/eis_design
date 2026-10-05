<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AdaptiveimagesPurger extends Command
{
	protected $sources = [];

	protected function configure() {
		$this
			->setName('adaptiveimages:purger')
			->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Purger les images plus anciennes que cette date', '')
			->setDescription(
				'Purger les images adaptées dont la source n\'existe plus ou a changé, ainsi que trop anciennes si from est fourni'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$this->io->title('Purger les images adaptées inutiles');

		$from = $input->getOption('from');
		if (!$from || in_array(strtotime($from), [0, false], true)) {
			$from = null;
		} else {
			$from = strtotime($from);
			$this->io->care('Purger les images plus vieilles que : ' . date('Y-m-d H:i:s', $from));
		}

		$dir_adapt = _DIR_VAR . 'adapt-img/';
		$dir_widths = glob($dir_adapt . '*', GLOB_ONLYDIR);
		foreach ($dir_widths as $dir_width) {
			$dir_width = rtrim($dir_width, '/') . '/';
			$dir_dpis = glob($dir_width . '*', GLOB_ONLYDIR);
			asort($dir_dpis);
			foreach ($dir_dpis as $dir_dpi) {
				$dir_dpi = rtrim($dir_dpi, '/') . '/';
				$this->parcourirDossierImages($dir_dpi, $dir_dpi, $from);
			}
		}

		$this->nettoyerImagesObsoletes($from);

		if (false) {
			return self::FAILURE;
		}

		return self::SUCCESS;
	}

	protected function parcourirDossierImages($dir_dpi, $dir, $from, $recurs = true) {
		$dir = rtrim($dir, '/') . '/';
		$files = glob($dir . '*');
		foreach ($files as $file) {
			if (is_dir($file)) {
				if ($recurs) {
					$this->parcourirDossierImages($dir_dpi, $file, $from, $recurs);
				}
			} else {
				$this->collecterImagesObsoletes($dir_dpi, $file, $from);
			}
		}
	}

	protected function collecterImagesObsoletes($dir_dpi, $file, $from) {
		static $sources = [];

		$source = substr($file, strlen($dir_dpi));
		if (substr($source, -6) === '@.webp') {
			$source = substr($source, 0, -6);
		}
		$source = _DIR_RACINE . $source;
		if (!isset($this->sources[$source])) {
			if (
				!file_exists($source)
				|| $from !== null
			) {
				$this->sources[$source] = [$file];
			} else {
				$sources[$source] = true;
			}
		} elseif (is_array($this->sources[$source])) {
			$this->sources[$source][] = $file;
		}
	}

	protected function nettoyerImagesObsoletes($from) {
		include_spip('inc/filtres');
		$nb_deleted = 0;
		$ko_deleted = 0;
		foreach ($this->sources as $source => $files) {
			if (is_array($files)) {
				if (
					$from !== null
					&& strpos($source, _DIR_VAR . 'cache-') === 0
					&& file_exists($source)
					&& filemtime($source) < $from
				) {
					$this->io->care("Suppression source cache trop vieille $source");
					$nb_deleted++;
					$ko_deleted += filesize($source);
					@unlink($source);
				}
				if (!file_exists($source)) {
					$this->io->fail($source . ' (' . count($files) . ' fichiers)');
					foreach ($files as $file) {
						$nb_deleted++;
						$ko_deleted += filesize($file);
						@unlink($file);
					}
				} elseif ($from !== null) {
					foreach ($files as $file) {
						if (filemtime($file) < $from) {
							$this->io->care($file);
							$nb_deleted++;
							$ko_deleted += filesize($file);
							@unlink($file);
						}
					}
				}
			}
		}

		$this->io->care($nb_deleted . ' fichiers supprimés (' . taille_en_octets($ko_deleted) . ')');
	}
}
