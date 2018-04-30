<?php

namespace Vierwd\VierwdSmarty\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

class ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $entityNotFoundMessage = 'The requested entity could not be found.';

	/**
	 * this is needed to use the smarty view
	 */
	protected $namespacesViewObjectNamePattern = 'Vierwd\\VierwdSmarty\\View\\SmartyView';

	/**
	 * initialize the view.
	 * Just call the parent. And assign the configurationManager.
	 * Afterwards you can register some custom template functions/modifiers.
	 *
	 * @see http://www.smarty.net/docs/en/api.register.plugin.tpl
	 */
	protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		parent::initializeView($view);

		if ($view instanceof \Vierwd\VierwdSmarty\View\SmartyView) {
			$view->setContentObject($this->configurationManager->getContentObject());
		}

		// set template root paths, if available
		if (isset($this->settings['templateRootPaths'])) {
			$this->view->setTemplateRootPaths($this->settings['templateRootPaths']);
		}

		// $view->Smarty->registerPlugin('function', 'categorylink', [$this, 'smarty_categorylink']);

		$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (!empty($configuration['dataProcessing'])) {
			if (is_string($configuration['dataProcessing']) && $configuration['dataProcessing'][0] == '<') {
				// reference to existing value
				$key = trim(substr($configuration['dataProcessing'], 1));
				$cF = GeneralUtility::makeInstance(TypoScriptParser::class);
				list($name, $dataProcessing) = $cF->getVal($key, $GLOBALS['TSFE']->tmpl->setup);
			} else {
				$typoScriptService = $this->objectManager->get(TypoScriptService::class);
				$dataProcessing = $typoScriptService->convertPlainArrayToTypoScriptArray($configuration['dataProcessing']);
			}

			$dataProcessing = ['dataProcessing.' => $dataProcessing];

			$contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);

			$variables = [];
			$baseContentObject = $this->configurationManager->getContentObject();
			$variables = $contentDataProcessor->process($baseContentObject, $dataProcessing, $variables);

			$this->view->assignMultiple($variables);
		}

		if (!empty($configuration['variables'])) {
			$variables = $this->getContentObjectVariables($configuration);
			$this->view->assignMultiple($variables);
		}
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
	 * @return void
	 * @throws PropertyException if property mapping failed
	 * @override \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
	 * @see http://nerdcenter.de/extbase-fehlerbehandlung/
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		try {
			parent::processRequest($request, $response);
		} catch(PropertyException $exception) {
			// If the property mapper did throw a \TYPO3\CMS\Extbase\Property\Exception, because it was unable to find the requested entity, call the page-not-found handler.
			$previousException = $exception->getPrevious();
			if ($previousException instanceof PropertyTargetNotFoundException || $previousException instanceof PropertyInvalidSourceException) {
				$GLOBALS['TSFE']->pageNotFoundAndExit($this->entityNotFoundMessage);
			}
			throw $exception;
		}
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
		$variables = [];
		$reservedVariables = ['data', 'current'];
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
