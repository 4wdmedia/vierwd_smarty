<?php

namespace Vierwd\VierwdSmarty\Cache;

use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheBackend extends NullBackend {

	/**
	 * @var string
	 */
	protected $cacheType = '';

	public function setCacheType($cacheType) {
		$this->cacheType = $cacheType;
	}

	public function flush() {
		// just remove all files
		$directory = PATH_site . 'typo3temp/Cache/vierwd_smarty/' . $this->cacheType . '/';
		GeneralUtility::flushDirectory($directory, true);
	}
}
