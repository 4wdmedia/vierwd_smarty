<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\Controller;

use InvalidArgumentException;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController as ExtbaseActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Property\Exception as PropertyException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException as PropertyInvalidSourceException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException as PropertyTargetNotFoundException;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3Fluid\Fluid\View\ViewInterface;

use Vierwd\VierwdSmarty\View\SmartyView;

class ActionController extends ExtbaseActionController {

	protected string $entityNotFoundMessage = 'The requested entity could not be found.';

	/**
	 * this is needed to use the smarty view
	 *
	 * @var string
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 */
	protected $defaultViewObjectName = SmartyView::class;

	/**
	 * initialize the view.
	 * Set ContentObject, run dataProcessing and set variables
	 * Afterwards you can register some custom template functions/modifiers.
	 *
	 * @see http://www.smarty.net/docs/en/api.register.plugin.tpl
	 */
	protected function resolveView(): ViewInterface {
		$view = parent::resolveView();

		$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		$baseContentObject = $this->request->getAttribute('currentContentObject');

		if ($view instanceof SmartyView) {
			$view->setRequest($this->request);
			$view->initializeView();

			$view->setContentObject($baseContentObject);

			// Set template paths here, to allow smarty template path before website extension path.
			// TYPO3 always forces the path of the current extension as last fallback path.
			if (isset($configuration['view'], $configuration['view']['templateRootPaths'])) {
				$view->setTemplateRootPaths($configuration['view']['templateRootPaths']);
			} else if (isset($configuration['settings'], $configuration['settings']['templateRootPaths'])) {
				// set template root paths, if used with settings (old way)
				// The "proper" way is to set "plugin.tx_myextension.view.templateRootPaths"
				$view->setTemplateRootPaths($configuration['settings']['templateRootPaths']);
			}
		}

		if (!empty($configuration['dataProcessing'])) {
			if (is_string($configuration['dataProcessing']) && $configuration['dataProcessing'][0] == '<') {
				// reference to existing value
				$key = trim(substr($configuration['dataProcessing'], 1));
				$dataProcessing = ArrayUtility::getValueByPath($GLOBALS['TSFE']->tmpl->setup, $key, '.');
			} else {
				$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
				$dataProcessing = $typoScriptService->convertPlainArrayToTypoScriptArray($configuration['dataProcessing']);
			}

			$dataProcessing = ['dataProcessing.' => $dataProcessing];

			$contentDataProcessor = GeneralUtility::makeInstance(ContentDataProcessor::class);

			$variables = [];
			if ($baseContentObject) {
				$variables = $contentDataProcessor->process($baseContentObject, $dataProcessing, $variables);
			}

			$view->assignMultiple($variables);
		}

		if (!empty($configuration['variables'])) {
			$variables = $this->getContentObjectVariables($baseContentObject, $configuration);
			$view->assignMultiple($variables);
		}

		return $view;
	}

	/**
	 * @override \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
	 * @see http://nerdcenter.de/extbase-fehlerbehandlung/
	 */
	public function processRequest(RequestInterface $request): ResponseInterface {
		try {
			return parent::processRequest($request);
		} catch(PropertyException $exception) {
			// If the property mapper did throw a \TYPO3\CMS\Extbase\Property\Exception, because it was unable to find the requested entity, call the page-not-found handler.
			$previousException = $exception->getPrevious();
			if ($previousException instanceof PropertyTargetNotFoundException || $previousException instanceof PropertyInvalidSourceException) {
				$response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($GLOBALS['TYPO3_REQUEST'], $this->entityNotFoundMessage);
				throw new ImmediateResponseException($response);
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
	protected function getContentObjectVariables(?ContentObjectRenderer $contentObject, array $conf): array {
		if (!$contentObject) {
			return [];
		}

		$variables = [];
		$reservedVariables = ['data', 'current'];
		// Accumulate the variables to be process and loop them through cObjGetSingle
		$typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
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
