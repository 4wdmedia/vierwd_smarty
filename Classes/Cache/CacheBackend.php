<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Cache;

use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheBackend extends NullBackend {

	/** @var string */
	protected $cacheType = '';

	public function setCacheType(string $cacheType): void {
		$this->cacheType = $cacheType;
	}

	public function flush(): void {
		// just remove all files
		$directory = Environment::getVarPath() . '/cache/vierwd_smarty/' . $this->cacheType . '/';
		GeneralUtility::flushDirectory($directory, true);
	}
}
