<?php

namespace Vierwd\VierwdSmarty;

class Bootstrap {

	/**
	 * Back reference to the parent content object
	 * This has to be public as it is set directly from TYPO3
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	public function run($content, $configuration) {
		$piFlexformBackup = false;
		if (is_string($this->cObj->data['pi_flexform']) && preg_match('/<field index="switchableControllerActions">/', $this->cObj->data['pi_flexform'])) {
			// remove switchable controller actions
			$piFlexformBackup = $this->cObj->data['pi_flexform'];
			$this->cObj->data['pi_flexform'] = preg_replace('/<field index="switchableControllerActions">(.*?)<\\/field>/s', '', $this->cObj->data['pi_flexform']);
		}

		$result = $this->cObj->callUserFunction('TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run', $configuration, $content);

		if ($piFlexformBackup) {
			$this->cObj->data['pi_flexform'] = $piFlexformBackup;
		}

		return $result;
	}
}
