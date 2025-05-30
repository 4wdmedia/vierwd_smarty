<?php

declare(strict_types=1);

namespace Vierwd\VierwdSmarty\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class ExtensionTestCase extends FunctionalTestCase {

	public function __construct(string $name) {
		parent::__construct($name);

		// // Shared core extensions to load
		// $this->coreExtensionsToLoad = [
		// 	...array_values($this->coreExtensionsToLoad),
		// 	'install',
		// ];

		// Shared extension to load
		$this->testExtensionsToLoad = [
			...array_values($this->testExtensionsToLoad),
			'vierwd/typo3-smarty',
		];
	}

}
