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
				$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
				$contentObject->start($baseContentObject->data);

				if (is_array($extbaseArray)) {
					// convert back to normal TypoScript array
					$typoscriptArray = $typoScriptService->convertPlainArrayToTypoScriptArray($extbaseArray);

					$content = $contentObject->cObjGetSingle($extbaseArray['_typoScriptNodeValue'], $typoscriptArray);
				} else if (is_string($extbaseArray) && $extbaseArray[0] == '<') {
					$content = $contentObject->cObjGetSingle($extbaseArray, array());
				} else if (is_string($extbaseArray)) {
					$content = $extbaseArray;
				} else {
					throw new \Exception('Unkown type for ' . $key);
				}
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
