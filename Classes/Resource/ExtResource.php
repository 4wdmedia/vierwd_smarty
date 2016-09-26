<?php

namespace Vierwd\VierwdSmarty\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resource for reading files beginning with EXT:
 */
class ExtResource extends \Smarty_Resource_Custom {

	protected function fetch($name, &$source, &$mtime) {
		$file = GeneralUtility::getFileAbsFileName('EXT:' . $name);
		if (file_exists($file)) {
			$mtime = filemtime($file);
			$source = file_get_contents($file);
		}
	}

	protected function fetchTimestamp($name) {
		$file = GeneralUtility::getFileAbsFileName('EXT:' . $name);
		if (file_exists($file)) {
			return filemtime($file);
		}

		return null;
	}
}
