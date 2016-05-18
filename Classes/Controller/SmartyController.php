<?php

namespace Vierwd\VierwdSmarty\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

class SmartyController extends ActionController {

	public function renderAction() {
		$baseContentObject = $this->configurationManager->getContentObject();
		$typoScriptService = $this->objectManager->get(TypoScriptService::class);

		$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		if (!empty($configuration['dataProcessing'])) {
			if (is_string($configuration['dataProcessing']) && $configuration['dataProcessing'][0] == '<') {
				// reference to existing value
				$key = trim(substr($configuration['dataProcessing'], 1));
				$cF = GeneralUtility::makeInstance(TypoScriptParser::class);
				list($name, $dataProcessing) = $cF->getVal($key, $GLOBALS['TSFE']->tmpl->setup);
			} else {
				$dataProcessing = $typoScriptService->convertPlainArrayToTypoScriptArray($configuration['dataProcessing']);
			}

			$dataProcessing = ['dataProcessing.' => $dataProcessing];

			$contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);

			$variables = [];
			$variables['data'] = $baseContentObject->data;
			$variables['current'] = $baseContentObject->data[$baseContentObject->currentValKey];
			$variables = $contentDataProcessor->process($baseContentObject, $dataProcessing, $variables);

			$this->view->assignMultiple($variables);
		}

		if (!empty($configuration['variables'])) {
			$variables = $this->getContentObjectVariables($configuration);
			$this->view->assignMultiple($variables);
		}

		if (isset($this->settings['templateRootPaths'])) {
			$templateRootPaths = $this->settings['templateRootPaths'];
			krsort($templateRootPaths);
			$templateRootPaths = array_map(function($rootPath) {
				$rootPath = str_replace('//', '/', $rootPath);
				return GeneralUtility::getFileAbsFileName($rootPath);
			}, $templateRootPaths);
			$templateRootPaths = array_values(array_filter($templateRootPaths));
			$this->view->setTemplateRootPaths($templateRootPaths);
		}

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

		// try to render the template. maybe it is relative
		return $this->view->render($template);
	}

	/**
	 * Compile rendered content objects in variables array ready to assign to the view
	 *
	 * @param array $conf Configuration array
	 * @return array the variables to be assigned
	 * @throws \InvalidArgumentException
	 */
	protected function getContentObjectVariables(array $conf) {
		$contentObject = $this->configurationManager->getContentObject();
		$variables = array();
		$reservedVariables = array('data', 'current');
		// Accumulate the variables to be process and loop them through cObjGetSingle
		$typoScriptService = $this->objectManager->get(TypoScriptService::class);
		$variablesToProcess = $typoScriptService->convertPlainArrayToTypoScriptArray($conf['variables']);
		foreach ($variablesToProcess as $variableName => $cObjType) {
			if (is_array($cObjType)) {
				continue;
			}
			if (!in_array($variableName, $reservedVariables)) {
				$variables[$variableName] = $contentObject->cObjGetSingle($cObjType, $variablesToProcess[$variableName . '.']);
			} else {
				throw new \InvalidArgumentException(
					'Cannot use reserved name "' . $variableName . '" as variable name in Smarty ContentObject.',
					1463556016
				);
			}
		}

		return $variables;
	}
}
