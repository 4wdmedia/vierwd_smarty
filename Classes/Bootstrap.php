<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty;

use TYPO3\CMS\Extbase\Core\Bootstrap as ExtbaseBootstrap;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class Bootstrap {

	/**
	 * Back reference to the parent content object
	 */
	protected ?ContentObjectRenderer $cObj = null;

	public function setContentObjectRenderer(ContentObjectRenderer $cObj): void {
		$this->cObj = $cObj;
	}

	public function run(string $content, array $configuration): string {
		if ($this->cObj === null) {
			throw new \Exception('cObj must be set before running bootstrap', 1644590996);
		}
		$piFlexformBackup = false;
		if (isset($this->cObj->data['pi_flexform']) && is_string($this->cObj->data['pi_flexform']) && preg_match('/<field index="switchableControllerActions">/', $this->cObj->data['pi_flexform'])) {
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
