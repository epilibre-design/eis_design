<?php

declare(strict_types=1);

namespace Spip\Plugin\Autoupdate\Report;

/**
 * Assainissement du payload de rapport de debug (chemins, emails, troncature).
 *
 * Indépendant de SPIP : les répertoires / préfixes sont injectés.
 */
final class Sanitizer
{
	public const DEFAULT_LOG_MAX_BYTES = 65536;

	public const EMAIL_PLACEHOLDER = '[email]';

	public const LOG_TRUNCATED_MARKER = "[…tronqué…]\n";

	/**
	 * @var list<string>
	 */
	private $rootPrefixes;

	/**
	 * @var list<string>
	 */
	private $pathPrefixes;

	/**
	 * @var int
	 */
	private $logMaxBytes;

	/**
	 * @param list<string> $rootPrefixes  Préfixes absolus préférés pour relativiser (racine site)
	 * @param list<string> $pathPrefixes  Tous les préfixes absolus à retirer des textes
	 */
	public function __construct(array $rootPrefixes, array $pathPrefixes = [], int $logMaxBytes = self::DEFAULT_LOG_MAX_BYTES) {
		$this->rootPrefixes = self::sortPrefixesLongestFirst(self::normalizePrefixList($rootPrefixes));
		$merged = array_merge($this->rootPrefixes, self::normalizePrefixList($pathPrefixes));
		$this->pathPrefixes = self::sortPrefixesLongestFirst(array_values(array_unique($merged)));
		$this->logMaxBytes = $logMaxBytes;
	}

	/**
	 * Construit un sanitizer à partir de répertoires (forme relative ou absolue).
	 * Les chemins relatifs non résolus par realpath sont ignorés comme préfixes.
	 *
	 * @param list<string> $rootDirs
	 * @param list<string> $extraDirs
	 */
	public static function fromDirectories(
		array $rootDirs,
		array $extraDirs = [],
		int $logMaxBytes = self::DEFAULT_LOG_MAX_BYTES
	): self {
		return new self(
			self::absolutePrefixesFromDirs($rootDirs),
			self::absolutePrefixesFromDirs(array_merge($rootDirs, $extraDirs)),
			$logMaxBytes
		);
	}

	public static function isAbsolutePath(string $path): bool {
		$path = str_replace('\\', '/', $path);

		return $path !== '' && ($path[0] === '/' || (bool) preg_match(',^[A-Za-z]:/,', $path));
	}

	/**
	 * Chemin relatif à la racine injectée, sinon aux autres préfixes, sinon inchangé.
	 */
	public function relativizePath(string $path): string {
		$path = str_replace('\\', '/', $path);
		foreach ($this->rootPrefixes as $prefix) {
			if (strpos($path, $prefix) === 0) {
				return ltrim(substr($path, strlen($prefix)), '/');
			}
		}
		foreach ($this->pathPrefixes as $prefix) {
			if (strpos($path, $prefix) === 0) {
				return ltrim(substr($path, strlen($prefix)), '/');
			}
		}

		return $path;
	}

	/**
	 * Remplace les adresses email par un placeholder.
	 */
	public function redactEmails(string $text): string {
		return preg_replace(
			'/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i',
			self::EMAIL_PLACEHOLDER,
			$text
		) ?? $text;
	}

	/**
	 * Retire les préfixes de chemins absolus du texte (racine d’abord puis les autres).
	 */
	public function stripAbsolutePrefixes(string $text): string {
		foreach (array_merge($this->rootPrefixes, $this->pathPrefixes) as $prefix) {
			$text = str_replace($prefix, '', $text);
		}

		return $text;
	}

	/**
	 * Emails + chemins absolus retirés.
	 */
	public function sanitizeText(string $text): string {
		return $this->stripAbsolutePrefixes($this->redactEmails($text));
	}

	/**
	 * Découpe un texte en lignes (sans entrée vide finale due au \\n terminal).
	 *
	 * @return list<string>
	 */
	public function linesFromText(string $text): array {
		if ($text === '') {
			return [];
		}
		$text = str_replace(["\r\n", "\r"], "\n", $text);
		$lines = explode("\n", $text);
		if ($lines !== [] && end($lines) === '') {
			array_pop($lines);
		}

		return array_values($lines);
	}

	/**
	 * Texte assaini, sous forme de tableau de lignes (lisible en JSON).
	 *
	 * @return list<string>
	 */
	public function sanitizeLines(string $text): array {
		return $this->linesFromText($this->sanitizeText($text));
	}

	/**
	 * Garde la fin du journal (coupée sur une ligne si possible).
	 */
	public function truncateLogTail(string $log, ?int $maxBytes = null): string {
		$max = $maxBytes ?? $this->logMaxBytes;
		if ($max <= 0 || strlen($log) <= $max) {
			return $log;
		}
		$log = substr($log, -$max);
		$coupe = strpos($log, "\n");
		if ($coupe !== false && $coupe < 200) {
			$log = substr($log, $coupe + 1);
		}

		return self::LOG_TRUNCATED_MARKER . $log;
	}

	/**
	 * Journal tronqué puis assaini.
	 */
	public function extractLog(string $log, ?int $maxBytes = null): string {
		return $this->sanitizeText($this->truncateLogTail($log, $maxBytes));
	}

	/**
	 * Journal tronqué puis assaini, en tableau de lignes.
	 *
	 * @return list<string>
	 */
	public function extractLogLines(string $log, ?int $maxBytes = null): array {
		return $this->linesFromText($this->extractLog($log, $maxBytes));
	}

	/**
	 * @param list<string> $dirs
	 * @return list<string>
	 */
	public static function absolutePrefixesFromDirs(array $dirs): array {
		$prefixes = [];
		foreach ($dirs as $dir) {
			if (!is_string($dir) || $dir === '' || $dir === './') {
				continue;
			}
			$normalized = rtrim(str_replace('\\', '/', $dir), '/') . '/';
			if (self::isAbsolutePath($normalized)) {
				$prefixes[] = $normalized;
			}
			$real = @realpath($dir);
			if (is_string($real) && $real !== '') {
				$prefixes[] = rtrim(str_replace('\\', '/', $real), '/') . '/';
			}
		}

		return self::sortPrefixesLongestFirst(array_values(array_unique($prefixes)));
	}

	/**
	 * @param list<string> $prefixes
	 * @return list<string>
	 */
	private static function normalizePrefixList(array $prefixes): array {
		$out = [];
		foreach ($prefixes as $prefix) {
			if (!is_string($prefix) || $prefix === '') {
				continue;
			}
			$normalized = str_replace('\\', '/', $prefix);
			if (substr($normalized, -1) !== '/') {
				$normalized .= '/';
			}
			if (self::isAbsolutePath($normalized)) {
				$out[] = $normalized;
			}
		}

		return $out;
	}

	/**
	 * @param list<string> $prefixes
	 * @return list<string>
	 */
	private static function sortPrefixesLongestFirst(array $prefixes): array {
		usort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

		return $prefixes;
	}
}
