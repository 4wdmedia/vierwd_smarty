<?php

namespace Vierwd\VierwdSmarty\Controller;

use InvalidArgumentException;

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController as ExtbaseActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException as PropertyInvalidSourceException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException as PropertyTargetNotFoundException;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;

use Vierwd\VierwdSmarty\View\SmartyView;

class ActionController extends ExtbaseActionController {

	/** @var string */
	protected $entityNotFoundMessage = 'The requested entity could not be found.';

	/**
	 * this is needed to use the smarty view
	 *
	 * @var string
	 */
	protected $defaultViewObjectName = SmartyView::class;

	/**
	 * initialize the view.
	 * Set ContentObject, run dataProcessing and set variables
	 * Afterwards you can register some custom template functions/modifiers.
	 *
	 * @see http://www.smarty.net/docs/en/api.register.plugin.tpl
	 * @phpstan-return void
	 */
	protected function initializeView(ViewInterface $view) {
		$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		if ($view instanceof SmartyView) {
			$view->setContentObject($this->configurationManager->getContentObject());

			// set template root paths, if used with settings (old way)
			// The "proper" way is to set "plugin.tx_myextension.view.templateRootPaths", then it's automatically handled by extbase
			if (isset($this->settings['templateRootPaths'])) {
				$templateRootPaths = array_unique(array_merge($view->getTemplateRootPaths(), $this->settings['templateRootPaths']));
				$view->setTemplateRootPaths($templateRootPaths);
			}
		}

		parent::initializeView($view);

		if (!empty($configuration['dataProcessing'])) {
			if (is_string($configuration['dataProcessing']) && $configuration['dataProcessing'][0] == '<') {
				// reference to existing value
				$key = trim(substr($configuration['dataProcessing'], 1));
				$cF = GeneralUtility::makeInstance(TypoScriptParser::class);
				[, $dataProcessing] = $cF->getVal($key, $GLOBALS['TSFE']->tmpl->setup);
			} else {
				$typoScriptService = $this->objectManager->get(TypoScriptService::class);
				$dataProcessing = $typoScriptService->convertPlainArrayToTypoScriptArray($configuration['dataProcessing']);
			}

			$dataProcessing = ['dataProcessing.' => $dataProcessing];

			$contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);

			$variables = [];
			$baseContentObject = $this->configurationManager->getContentObject();
			if ($baseContentObject) {
				$variables = $contentDataProcessor->process($baseContentObject, $dataProcessing, $variables);
			}

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
	 * @override \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
	 * @see http://nerdcenter.de/extbase-fehlerbehandlung/
	 */
	public function processRequest(RequestInterface $request, ResponseInterface $response) {
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
	 */
	protected function getContentObjectVariables(array $conf) {
		$contentObject = $this->configurationManager->getContentObject();
		if (!$contentObject) {
			return [];
		}

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
				throw new InvalidArgumentException(
					'Cannot use reserved name "' . $variableName . '" as variable name in Smarty ContentObject.',
					1463556016
				);
			}
		}

		return $variables;
	}
}
