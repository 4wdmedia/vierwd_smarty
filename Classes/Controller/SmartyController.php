<?php

namespace Vierwd\VierwdSmarty\Controller;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

class SmartyController extends ActionController {

	public function renderAction() {
		$baseContentObject = $this->configurationManager->getContentObject();
		$typoScriptService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');

		// first check, if the template was given using the settings
		// 10 < plugin.tx_vierwdsmarty
		// 10.settings.template = fileadmin/templates/fce.tpl
		$settings = $typoScriptService->convertPlainArrayToTypoScriptArray($this->settings);
		$template = $settings['template'];

		if (isset($settings['template.'])) {
			$template = $baseContentObject->stdWrap($template, $settings['template.']);
		}

		if (isset($this->settings['typoscript'])) {
			foreach ($this->settings['typoscript'] as $key => $extbaseArray) {
				// convert back to normal TypoScript array
				$typoscriptArray = $typoScriptService->convertPlainArrayToTypoScriptArray($extbaseArray);

				$contentObject = GeneralUtility::makeInstance('tslib_cObj');
				$contentObject->start($baseContentObject->data);

				$content = $contentObject->cObjGetSingle($extbaseArray['_typoScriptNodeValue'], $typoscriptArray);
				$this->settings['typoscript'][$key] = $content;
			}
		}

		$this->view->assign('settings', $this->settings);

		if (!$template) {
			// template was not passed as setting, check the register
			$template = $GLOBALS['TSFE']->register['template'];
		}

		if (!$template) {
			return '';
		}

		$file = GeneralUtility::getFileAbsFileName($template);
		if ($file && file_exists($file)) {
			return $this->view->render($file);
		}

		// try to render the string directly
		return $this->view->render('string:' . $template);
	}
}
?>