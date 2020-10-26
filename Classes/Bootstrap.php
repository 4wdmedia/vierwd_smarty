<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty;

use TYPO3\CMS\Extbase\Core\Bootstrap as ExtbaseBootstrap;

class Bootstrap {

	/**
	 * Back reference to the parent content object
	 * This has to be public as it is set directly from TYPO3
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	public function run(string $content, array $configuration): string {
		$piFlexformBackup = false;
		if (is_string($this->cObj->data['pi_flexform']) && preg_match('/<field index="switchableControllerActions">/', $this->cObj->data['pi_flexform'])) {
			// remove switchable controller actions
			$piFlexformBackup = $this->cObj->data['pi_flexform'];
			$this->cObj->data['pi_flexform_backup'] = $piFlexformBackup;
			$this->cObj->data['pi_flexform'] = preg_replace('/<field index="switchableControllerActions">(.*?)<\\/field>/s', '', $this->cObj->data['pi_flexform']);
		}

		$result = $this->cObj->callUserFunction(ExtbaseBootstrap::class . '->run', $configuration, $content);

		if ($piFlexformBackup) {
			$this->cObj->data['pi_flexform'] = $piFlexformBackup;
		}

		return $result;
	}
}
