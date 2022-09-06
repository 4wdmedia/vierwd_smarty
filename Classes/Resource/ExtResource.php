<?php

namespace Vierwd\VierwdSmarty\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resource for reading files beginning with EXT:
 */
class ExtResource extends \Smarty_Resource_Custom {

	/**
	 * @param string  $name
	 * @param string  $source
	 * @param int $mtime
	 * @phpstan-return void
	 */
	protected function fetch($name, &$source, &$mtime) {
		$file = GeneralUtility::getFileAbsFileName('EXT:' . $name);
		if (file_exists($file)) {
			$mtime = filemtime($file);
			$source = file_get_contents($file);
		}
	}

	/**
	 * @param string $name
	 */
	protected function fetchTimestamp($name) {
		$file = GeneralUtility::getFileAbsFileName('EXT:' . $name);
		if (file_exists($file)) {
			return filemtime($file);
		}

		// the docComment for Smarty_Resource_Custom::fetchTimestamp is incorrect.
		// it specifies integer|boolean, but returns null. Other methods check for null instead of false.
		// @phpstan-ignore-next-line
		return null;
	}

}
