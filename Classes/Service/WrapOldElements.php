<?php

namespace Vierwd\VierwdSmarty\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class WrapOldElements {
	/**
	 * @var TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	public function wrap($content, $params = []) {
		$doNotWrap = $params['doNotWrap'] ? GeneralUtility::trimExplode(',', $params['doNotWrap']) : [];
		if (in_array($this->cObj->data['CType'], $doNotWrap)) {
			return $content;
		}

		$renderConfig = [
			'templateName' => 'WrapOld',
		];

		\Smarty::$_smarty_vars['capture']['content'] = $content;
		return $this->cObj->cObjGetSingle('< lib.fluidContent', $renderConfig);
	}
}
