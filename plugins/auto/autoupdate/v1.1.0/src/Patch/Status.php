<?php

declare(strict_types=1);

namespace Spip\Plugin\Autoupdate\Patch;

/**
 * Statuts d'un patch de mise à jour automatique.
 */
final class Status
{
	public const UNKNOWN = -1;

	public const NONE = 0;

	public const PREPARING = 1;

	public const READY = 2;

	public const PROCESSING = 3;

	public const PATCHED_OK = 4;

	public const PATCHED_ERRORS = 5;

	public const FINISHED = 6;

	public const FINISHED_UPGRADED = 7;

	public const FAILED_ROLLBACK = 98;

	public const FAILED = 99;

	/**
	 * Clé de langue du statut (à passer à _T()), ou chaîne vide si inconnu.
	 */
	public static function label(int $status): string {
		switch ($status) {
			case self::UNKNOWN:
				return 'autoupdate:status_unknown';
			case self::NONE:
				return 'autoupdate:status_none';
			case self::PREPARING:
				return 'autoupdate:status_preparing';
			case self::READY:
				return 'autoupdate:status_ready';
			case self::PROCESSING:
				return 'autoupdate:status_processing';
			case self::PATCHED_OK:
				return 'autoupdate:status_patched_ok';
			case self::PATCHED_ERRORS:
				return 'autoupdate:status_patched_errors';
			case self::FINISHED:
				return 'autoupdate:status_finished';
			case self::FINISHED_UPGRADED:
				return 'autoupdate:status_finished_upgraded';
			case self::FAILED_ROLLBACK:
				return 'autoupdate:status_failed_rollback';
			case self::FAILED:
				return 'autoupdate:status_failed';
			default:
				return '';
		}
	}
}
