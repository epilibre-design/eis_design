<?php

declare(strict_types=1);

namespace Spip\Plugin\Autoupdate;

/**
 * Extraction structurée des informations phpinfo().
 */
class PhpInfo
{
	public function general(bool $tb = false): array {
		return $this->parsePhpinfo(INFO_GENERAL, $tb);
	}

	public function configuration(bool $tb = false): array {
		return $this->parsePhpinfo(INFO_CONFIGURATION, $tb);
	}

	public function environment(bool $tb = false): array {
		return $this->parsePhpinfo(INFO_ENVIRONMENT, $tb);
	}

	public function variable(bool $tb = false): array {
		return $this->parsePhpinfo(INFO_VARIABLES, $tb);
	}

	public function modules(bool $tb = false): array {
		$cat = 'None';
		$info_arr = [];
		foreach ($this->phpinfoLines(INFO_MODULES) as $line) {
			if ($line === '') {
				continue;
			}
			if (
				(PHP_SAPI === 'cli' && !str_contains($line, '=>') && ($title = ['', $line]))
				|| preg_match('~<h2>(.*)</h2>~', $line, $title)
			) {
				$cat = $title[1];
			}
			$entry = $this->extractNameValue($line);
			if ($entry !== null) {
				$this->storeEntry($info_arr, $entry, $tb, $cat);
			}
		}
		return $info_arr;
	}

	public function credits(bool $tb = false): array {
		return $this->parsePhpinfo(INFO_CREDITS, $tb);
	}

	public function license(): string {
		$info_lines = $this->phpinfoLines(INFO_LICENSE);
		return implode('. ', [$info_lines[28], $info_lines[30], $info_lines[32]]);
	}

	public function all(bool $tb = false): array {
		return [
			'General' => $this->general($tb),
			'Configuration' => $this->configuration($tb),
			'Environment' => $this->environment($tb),
			'Variable' => $this->variable($tb),
			'Modules' => $this->modules($tb),
			'Credits' => $this->credits($tb),
			'License' => $this->license(),
		];
	}

	public function essential(bool $tb = false): array {
		return [
			'General' => $this->general($tb),
			'Configuration' => $this->configuration($tb),
			'Environment' => $this->environment($tb),
			'Variable' => $this->variable($tb),
			'Modules' => $this->modules($tb),
		];
	}

	private function parsePhpinfo(int $type, bool $tb): array {
		$info_arr = [];
		foreach ($this->phpinfoLines($type) as $line) {
			$entry = $this->extractNameValue($line);
			if ($entry !== null) {
				$this->storeEntry($info_arr, $entry, $tb);
			}
		}
		return $info_arr;
	}

	/**
	 * @return list<string>
	 */
	private function phpinfoLines(int $type): array {
		ob_start();
		phpinfo($type);
		$info_lines = ob_get_clean();
		return explode("\n", strip_tags($info_lines, '<tr><td><h2>'));
	}

	/**
	 * Extrait un couple nom/valeur depuis une ligne phpinfo (CLI ou HTML).
	 *
	 * @return array{n: string, v: string}|null
	 */
	private function extractNameValue(string $line): ?array {
		if (PHP_SAPI === 'cli' && str_contains($line, '=>')) {
			$parts = explode('=>', $line, 2);
			$val = [$line, $parts[0], $parts[1]];
		} elseif (
			preg_match('~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~', $line, $val)
			|| preg_match('~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~', $line, $val)
		) {
			// $val rempli par preg_match
		} else {
			return null;
		}

		return [
			'n' => trim($val[1]),
			'v' => trim(str_replace(';', '; ', $val[2])),
		];
	}

	/**
	 * @param array{n: string, v: string} $entry
	 */
	private function storeEntry(array &$info_arr, array $entry, bool $tb, ?string $category = null): void {
		if ($category !== null) {
			if ($tb) {
				$info_arr[$category][] = $entry;
			} else {
				$info_arr[$category][$entry['n']] = $entry['v'];
			}
			return;
		}
		if ($tb) {
			$info_arr[] = $entry;
		} else {
			$info_arr[$entry['n']] = $entry['v'];
		}
	}
}
