<?php

namespace Vierwd\VierwdSmarty\View;

use Exception;
use Smarty;
use Smarty_Internal_Template;
use Throwable;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\View\AbstractView;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

use Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject;
use Vierwd\VierwdSmarty\Controller\SmartyController;
use Vierwd\VierwdSmarty\Resource\ExtResource;

/**
 * @param mixed $str
 */
function clean($str): string {
	if (is_scalar($str)) {
		$str = (string)preg_replace('/&(?!#(?:[0-9]+|x[0-9A-F]+);?)/si', '&amp;', (string)$str);
		// replace html-characters
		$str = str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], $str);

		return $str;
	} else if ($str === null) {
		return '';
	} else {
		throw new Exception('$str needs to be scalar value');
	}
}

class SmartyView extends AbstractView {

	/**
	 * @var Smarty
	 */
	public $Smarty = null;

	/**
	 * @var bool
	 */
	public $hasTopLevelViewHelper = false;

	/**
	 * Pattern to be resolved for "@templateRoot" in the other patterns.
	 *
	 * @var string
	 */
	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	/**
	 * Path(s) to the template root. If NULL, then $this->templateRootPathPattern will be used.
	 *
	 * @var ?array
	 */
	protected $templateRootPaths = null;

	/**
	 * @var ?\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject = null;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $configurationManager = null;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $objectManager = null;

	/**
	 * Set the root path(s) to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param array $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 * @return void
	 * @api
	 */
	public function setTemplateRootPaths(array $templateRootPaths) {
		$this->templateRootPaths = $templateRootPaths;
	}

	/**
	 * Resolves the template root to be used inside other paths.
	 *
	 * @return array Path(s) to template root directory
	 */
	public function getTemplateRootPaths() {
		if ($this->templateRootPaths !== null) {
			return $this->templateRootPaths;
		}
		/** @var \TYPO3\CMS\Extbase\Mvc\Request $actionRequest */
		$actionRequest = $this->controllerContext->getRequest();
		return [str_replace(['@packageResourcesPath', '//'], [ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', '/'], $this->templateRootPathPattern)];
	}

	/**
	 * get template root paths and resolve all relative paths and paths containing EXT:
	 *
	 * @return array
	 */
	public function resolveTemplateRootPaths() {
		$templateRootPaths = $this->getTemplateRootPaths();
		ksort($templateRootPaths);
		$templateRootPaths = array_reverse($templateRootPaths, true);
		$templateRootPaths = array_map(function($path) {
			return GeneralUtility::getFileAbsFileName(GeneralUtility::fixWindowsFilePath($path));
		}, $templateRootPaths);
		return array_filter($templateRootPaths);
	}

	public function setContentObject(?ContentObjectRenderer $contentObject): void {
		$this->contentObject = $contentObject;
	}

	public function getControllerContext(): ControllerContext {
		return $this->controllerContext;
	}

	/**
	 * @return bool
	 */
	public function canRender(ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);
		if ($controllerContext->getRequest()->getControllerObjectName() == SmartyController::class) {
			return true;
		}
		// setting in TypoScript: plugin.tx_vierwdPLUGIN.format = tpl
		// this will force the SmartyView for all views. Otherwise this method will check if
		// there is a template with the same name as the action. (Which will not exist, if the action
		// calls $view->render() with another name)
		if ($controllerContext->getRequest()->getFormat() == 'tpl') {
			return true;
		}
		try {
			$this->getViewFileName($controllerContext);
			return true;
		} catch (Throwable $e) {
			return false;
		}
	}

	protected function getViewFileName(ControllerContext $controllerContext): string {
		// try to get the view name based upon the controller/action
		$controller = $controllerContext->getRequest()->getControllerName();
		$action     = $controllerContext->getRequest()->getControllerActionName();

		$file = $controller . '/' . ucfirst($action) . '.tpl';

		$paths = $this->getTemplateRootPaths();
		ksort($paths);
		$paths = array_reverse($paths, true);

		foreach ($paths as $rootPath) {
			$fileName = GeneralUtility::fixWindowsFilePath($rootPath . '/' . $file);
			$fileName = GeneralUtility::getFileAbsFileName($fileName);
			if (file_exists($fileName)) {
				return $fileName;
			}
		}

		// no view found
		throw new Exception('Template not found for ' . $controller . '->' . $action);
	}

	protected function createSmarty(): void {
		if ($this->Smarty !== null) {
			return;
		}

		$this->Smarty = new Smarty();

		if ($GLOBALS['TSFE'] && !$GLOBALS['TSFE']->headerNoCache()) {
			$this->Smarty->setCacheLifetime(120);
			$this->Smarty->setCompileCheck(0);
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs']) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'] as $pluginDir) {
				$this->Smarty->addPluginsDir($pluginDir);
			}
		}

		// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
		$this->Smarty->registerPlugin('function', 'translate',     [$this, 'smarty_translate']);
		$this->Smarty->registerPlugin('function', 'uri_resource',  [$this, 'smarty_uri_resource']);
		$this->Smarty->registerPlugin('function', 'uri_action',    [$this, 'smarty_uri_action']);
		$this->Smarty->registerPlugin('function', 'typolink',      [$this, 'smarty_helper_typolink']);
		$this->Smarty->registerPlugin('modifier', 'typolink',      [$this, 'smarty_helper_typolink_url']);
		$this->Smarty->registerPlugin('function', 'flashMessages', [$this, 'smarty_flashMessages']);
		$this->Smarty->registerPlugin('function', 'svg',           [$this, 'smarty_svg']);

		$this->Smarty->registerPlugin('block', 'link_action', [$this, 'smarty_link_action']);

		$templateProcessor = new TemplatePreprocessor();
		$this->Smarty->registerFilter('pre', $templateProcessor);
		$this->Smarty->registerFilter('variable', 'Vierwd\\VierwdSmarty\\View\\clean');

		// fluid
		$this->Smarty->registerPlugin('block', 'fluid', [$this, 'smarty_fluid']);

		// Typoscript filters
		$this->Smarty->registerPlugin('block', 'typoscript', [$this, 'smarty_typoscript']);

		// custom functions
		$this->Smarty->registerPlugin('function', 'email',       [$this, 'smarty_email']);

		$this->Smarty->registerPlugin('modifier', 'nl2p', [$this, 'smarty_nl2p']);
		// phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma

		// Resource type
		$this->Smarty->registerResource('EXT', new ExtResource());
	}

	/**
	 * @phpstan-return void
	 */
	public function initializeView() {
		if ($this->contentObject === null) {
			// initialize a new ContentObject
			$this->contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
			$this->contentObject->start([], '_NO_TABLE');
		}
		$this->createSmarty();
		if (!$this->hasTopLevelViewHelper) {
			$this->Smarty->clearAllAssign();
		}

		$extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();

		// setup compile and caching dirs
		$extCacheDir = Environment::getVarPath() . '/cache/vierwd_smarty';
		$this->Smarty->setCompileDir($extCacheDir . '/templates_c/' . $extensionKey . '/');
		$this->Smarty->setCacheDir($extCacheDir . '/cache/' . $extensionKey . '/');

		if (!is_dir($this->Smarty->getCacheDir())) {
			GeneralUtility::mkdir_deep($this->Smarty->getCacheDir());
		}
		if (!is_dir($this->Smarty->getCompileDir())) {
			GeneralUtility::mkdir_deep($this->Smarty->getCompileDir());
		}
	}

	/**
	 * translate function for smarty templates.
	 * copy from fluid viewhelper
	 */
	public function smarty_translate(array $params, Smarty_Internal_Template $smarty): string {
		$request = $this->controllerContext->getRequest();

		$key = $params['key'] ?? null;
		$default = $params['default'] ?? null;
		$htmlEscape = $params['htmlEscape'] ?? true;
		$arguments = $params['arguments'] ?? null;
		$extensionName = $params['extensionName'] ?? $request->getControllerExtensionName();

		$value = LocalizationUtility::translate($key, $extensionName, $arguments);
		if ($value === null) {
			$value = $default;
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

	public function smarty_uri_resource(array $params, Smarty_Internal_Template $smarty): string {
		$path = $params['path'] ?? null;
		$extensionName = $params['extensionName'] ?? null;
		$absolute = $params['absolute'] ?? false;

		if ($extensionName === null) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		$uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = GeneralUtility::getFileAbsFileName($uri);
		$uri = PathUtility::stripPathSitePrefix($uri);

		if (TYPO3_MODE === 'BE' && $absolute === false && $uri !== false) {
			$uri = '../' . $uri;
		}

		if ($absolute === true) {
			$uri = $this->controllerContext->getRequest()->getBaseURI() . $uri;
		}

		return $uri;
	}

	public function smarty_uri_action(array $params, Smarty_Internal_Template $smarty): string {
		$action = $params['action'] ?? null;
		$arguments = $params['arguments'] ?? [];
		$controller = $params['controller'] ?? null;
		$extensionName = $params['extensionName'] ?? null;
		$pluginName = $params['pluginName'] ?? null;
		$pageUid = $params['pageUid'] ?? 0;
		$pageType = $params['pageType'] ?? 0;
		$noCache = $params['noCache'] ?? false;
		$section = $params['section'] ?? '';
		$format = $params['format'] ?? '';
		$linkAccessRestrictedPages = $params['linkAccessRestrictedPages'] ?? false;
		$additionalParams = $params['additionalParams'] ?? [];
		$absolute = $params['absolute'] ?? false;
		$addQueryString = $params['addQueryString'] ?? false;
		$argumentsToBeExcludedFromQueryString = $params['argumentsToBeExcludedFromQueryString'] ?? [];

		$uriBuilder = $this->controllerContext->getUriBuilder()->reset();
		if ($pageUid) {
			$uriBuilder->setTargetPageUid($pageUid);
		}
		$uri = $uriBuilder
			->setTargetPageType($pageType)
			->setNoCache($noCache)
			->setSection($section)
			->setFormat($format)
			->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
			->setArguments($additionalParams)
			->setCreateAbsoluteUri($absolute)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->uriFor($action, $arguments, $controller, $extensionName, $pluginName);

		return $uri;
	}
	/**
	 * link action for smarty templates.
	 * modified from fluids LinkActionViewHelper
	 */
	public function smarty_link_action(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$defaultUrlParams = [
			'action' => null,
			'arguments' => [],
			'controller' => null,
			'extensionName' => null,
			'pluginName' => null,
			'pageUid' => null,
			'pageType' => 0,
			'noCache' => false,
			'section' => '',
			'format' => '',
			'linkAccessRestrictedPages' => false,
			'additionalParams' => [],
			'absolute' => false,
			'addQueryString' => false,
			'argumentsToBeExcludedFromQueryString' => [],
		];

		$attributes = array_diff_key($params, $defaultUrlParams);

		if (!isset($attributes['href'])) {
			$action = $params['action'] ?? $defaultUrlParams['action'];
			$arguments = $params['arguments'] ?? $defaultUrlParams['arguments'];
			$controller = $params['controller'] ?? $defaultUrlParams['controller'];
			$extensionName = $params['extensionName'] ?? $defaultUrlParams['extensionName'];
			$pluginName = $params['pluginName'] ?? $defaultUrlParams['pluginName'];
			$pageUid = $params['pageUid'] ?? $defaultUrlParams['pageUid'];
			$pageType = $params['pageType'] ?? $defaultUrlParams['pageType'];
			$noCache = $params['noCache'] ?? $defaultUrlParams['noCache'];
			$section = $params['section'] ?? $defaultUrlParams['section'];
			$format = $params['format'] ?? $defaultUrlParams['format'];
			$linkAccessRestrictedPages = $params['linkAccessRestrictedPages'] ?? $defaultUrlParams['linkAccessRestrictedPages'];
			$additionalParams = $params['additionalParams'] ?? $defaultUrlParams['additionalParams'];
			$absolute = $params['absolute'] ?? $defaultUrlParams['absolute'];
			$addQueryString = $params['addQueryString'] ?? $defaultUrlParams['addQueryString'];
			$argumentsToBeExcludedFromQueryString = $params['argumentsToBeExcludedFromQueryString'] ?? $defaultUrlParams['argumentsToBeExcludedFromQueryString'];

			$uriBuilder = $this->controllerContext->getUriBuilder()->reset();
			if ($pageUid) {
				$uriBuilder->setTargetPageUid($pageUid);
			}
			$uri = $uriBuilder
				->setTargetPageType($pageType)
				->setNoCache($noCache)
				->setSection($section)
				->setFormat($format)
				->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
				->setArguments($additionalParams)
				->setCreateAbsoluteUri($absolute)
				->setAddQueryString($addQueryString)
				->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
				->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
			$attributes['href'] = $uri;
		}

		return '<a ' . GeneralUtility::implodeAttributes($attributes, false, true) . '>' . $content . '</a>';
	}

	public function smarty_helper_typolink(array $params, Smarty_Internal_Template $smarty): string {
		$pageUid = $this->getParam($params, 'pageUid', null);
		$additionalParams = $this->getParam($params, 'additionalParams', []);
		$pageType = $this->getParam($params, 'pageType', 0);
		$noCache = $this->getParam($params, 'noCache');
		$linkAccessRestrictedPages = $this->getParam($params, 'linkAccessRestrictedPages');
		$absolute = $this->getParam($params, 'absolute');
		$section = $this->getParam($params, 'section');
		$addQueryString = $this->getParam($params, 'addQueryString');
		$argumentsToBeExcludedFromQueryString = $this->getParam($params, 'argumentsToBeExcludedFromQueryString', []);

		$uriBuilder = $this->controllerContext->getUriBuilder()->reset();
		if ($pageUid) {
			$uriBuilder->setTargetPageUid($pageUid);
		}
		$uri = $uriBuilder
			->setTargetPageType($pageType)
			->setNoCache($noCache)
			->setSection($section)
			->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
			->setArguments($additionalParams)
			->setCreateAbsoluteUri($absolute)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->build();

		return $uri;
	}

	public function smarty_helper_typolink_url(string $parameter): string {
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		return $cObj->getTypoLink_URL($parameter);
	}

	public function smarty_flashMessages(array $params, Smarty_Internal_Template $smarty): string {
		$renderMode = $params['renderMode'] ?? 'ul';
		$class = $params['class'] ?? 'typo3-messages';
		$queueIdentifier = $params['queueIdentifier'] ?? null;

		$flashMessages = $this->controllerContext->getFlashMessageQueue($queueIdentifier)->getAllMessagesAndFlush();

		if (count($flashMessages) === 0) {
			return '';
		}

		if ($renderMode != 'div') {
			$renderMode = 'ul';
		}

		$content = '<' . $renderMode;
		if ($class) {
			$content .= ' class="' . htmlspecialchars($class) . '"';
		}
		$content .= '>';

		foreach ($flashMessages as $singleFlashMessage) {
			if ($renderMode == 'ul') {
				$content .= '<li>' . htmlspecialchars($singleFlashMessage->getMessage()) . '</li>';
			} else {
				$content .= htmlspecialchars($singleFlashMessage->getMessage());
			}
		}

		$content .= '</' . $renderMode . '>';

		return $content;
	}

	public function smarty_svg(array $params, Smarty_Internal_Template $smarty): string {
		if (class_exists(ScalableVectorGraphicsContentObject::class)) {
			$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
			$svgObject = GeneralUtility::makeInstance(ScalableVectorGraphicsContentObject::class, $cObj);
			return $svgObject->render($params);
		}

		return '';
	}


	/**
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getParam(array $params, string $key, $default = false) {
		return !empty($params[$key]) ? $params[$key] : $default;
	}

	public function smarty_fluid(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data + $smarty->getTemplateVars();

		$fluidView = $this->objectManager->get(StandaloneView::class);
		$fluidView->setControllerContext($this->controllerContext);
		$fluidView->assignMultiple($data);
		$fluidView->setTemplateSource($content);

		$configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($configuration['view']) {
			$layoutRootPaths = [];
			if ($configuration['view']['layoutRootPaths']) {
				$layoutRootPaths = $configuration['view']['layoutRootPaths'];
			} else if ($configuration['view']['layoutRootPath']) {
				$layoutRootPaths = [$configuration['view']['layoutRootPath']];
			}
			$layoutRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $layoutRootPaths);
			$fluidView->setLayoutRootPaths($layoutRootPaths);

			// Partials
			$partialRootPaths = [];
			if ($configuration['view']['partialRootPaths']) {
				$partialRootPaths = $configuration['view']['partialRootPaths'];
			} else if ($configuration['view']['partialRootPath']) {
				$partialRootPaths = [$configuration['view']['partialRootPath']];
			}
			$partialRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $partialRootPaths);
			$fluidView->setPartialRootPaths($partialRootPaths);
		}

		return $fluidView->render();
	}

	public function smarty_typoscript(array $params, ?string $content, Smarty_Internal_Template $smarty, bool &$repeat): string {
		if (!isset($content)) {
			return '';
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data;

		$table = isset($data['table']) ? $data['table'] : '_NO_TABLE';
		unset($data['table']);

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		if ($this->contentObject) {
			$cObj->setParent($this->contentObject->data, $this->contentObject->currentRecord);
			$cObj->currentRecordNumber = $this->contentObject->currentRecordNumber;
			$cObj->currentRecordTotal = $this->contentObject->currentRecordTotal;
			$cObj->parentRecordNumber = $this->contentObject->parentRecordNumber;
		}
		if ($table != '_NO_TABLE') {
			$data['_MIGRATED'] = false;
		}
		$cObj->start($data, $table);

		// $cObj->setCurrentVal($dataValues[$key][$valueKey]);

		$tsparserObj = GeneralUtility::makeInstance(TypoScriptParser::class);

		if (is_array($GLOBALS['TSFE']->tmpl->setup)) {
			foreach ($GLOBALS['TSFE']->tmpl->setup as $tsObjectKey => $tsObjectValue) {
				// do not copy int-keys
				if ($tsObjectKey !== intval($tsObjectKey) && $tsObjectKey !== intval($tsObjectKey) . '.') {
					$tsparserObj->setup[$tsObjectKey] = $tsObjectValue;
				}
			}
		}

		$conditionMatcher = GeneralUtility::makeInstance(ConditionMatcher::class);
		$tsparserObj->parse($content, $conditionMatcher);

		// save current typoscript setup and change to modified setup
		$oldSetup = $GLOBALS['TSFE']->tmpl->setup;
		$GLOBALS['TSFE']->tmpl->setup = $tsparserObj->setup;

		$oldTplVars = $this->Smarty->tpl_vars;
		$this->Smarty->tpl_vars = [];

		$content = $cObj->cObjGet($tsparserObj->setup, 'COA');

		$this->Smarty->tpl_vars = $oldTplVars;

		// reset typoscript
		$GLOBALS['TSFE']->tmpl->setup = $oldSetup;

		if ($params['assign']) {
			$smarty->assign($params['assign'], $content);
			return '';
		} else {
			return $content;
		}
	}

	public function smarty_nl2p(string $content): string {
		$content = trim($content);
		if (!$content) {
			return '';
		}

		return '<p>' . nl2br((string)preg_replace('/\n{2,}/', '</p><p>', str_replace("\r", '', $content))) . '</p>';
	}

	public function smarty_email(array $params, Smarty_Internal_Template $smarty): string {
		$address = $this->getParam($params, 'address');
		$label = $this->getParam($params, 'label', $address);
		$parameter = $this->getParam($params, 'parameter', $address . ' - mail');
		$ATagParams = $this->getParam($params, 'ATagParams');

		$conf = [
			'parameter' => $parameter,
			'ATagParams' => $ATagParams,
		];

		$cObj = $this->contentObject ?? GeneralUtility::makeInstance(ContentObjectRenderer::class);

		return $cObj->typoLink($label, $conf);
	}

	/**
	 * @phpstan-return string
	 */
	public function render(string $view = '') {
		$this->Smarty->setTemplateDir($this->resolveTemplateRootPaths());

		if ($this->contentObject && !$this->contentObject->data && $this->variables['data']) {
			$this->contentObject->data = $this->variables['data'];
		}

		$this->Smarty->assign($this->variables);
		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$pluginName = $this->controllerContext->getRequest()->getPluginName();

		if ($this->configurationManager !== null) {
			$typoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			$this->Smarty->assign('frameworkSettings', $typoScript);
		}

		if ($this->objectManager !== null) {
			$formPrefix = $this->objectManager->get(ExtensionService::class)->getPluginNamespace($extensionName, $pluginName);
			$this->Smarty->assign('formPrefix', $formPrefix);
		}

		$extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();
		if ($extensionKey) {
			$extPath = ExtensionManagementUtility::extPath($extensionKey);
		} else {
			$extPath = '';
		}

		$request = $GLOBALS['TYPO3_REQUEST'] ?? null;
		$siteLanguage = $request ? $request->getAttribute('language') : null;

		$templateVars = [
			'cObj' => $this->contentObject,
			'data' => $this->contentObject ? $this->contentObject->data : [],
			'extensionPath' => $extPath,
			'extensionName' => $extensionName,
			'pluginName' => $pluginName,
			'controllerName' => $this->controllerContext->getRequest()->getControllerName(),
			'actionName' => $this->controllerContext->getRequest()->getControllerActionName(),
			'controllerContext' => $this->controllerContext,
			'request' => $this->controllerContext->getRequest(),
			'context' => GeneralUtility::makeInstance(Context::class),
			'siteLanguage' => $siteLanguage,
			'typo3Request' => $request,
			'typolinkService' => GeneralUtility::makeInstance(TypoLinkCodecService::class),
			'imageService' => GeneralUtility::makeInstance(ImageService::class),
			// 'settings' => $typoScript['settings'],
			'TSFE' => $GLOBALS['TSFE'],
		];

		$this->Smarty->assign($templateVars);
		$settings = $this->Smarty->getTemplateVars('settings');
		$userVars = $settings['typoscript'];
		if ($userVars) {
			$overwrite = array_intersect_key($templateVars, $userVars);
			if ($overwrite) {
				throw new Exception('Overwriting smarty template vars with own variables: ' . implode(',', array_keys($overwrite)));
			}
			$this->Smarty->assign($userVars);
		}

		if ($extensionName == 'VierwdSmarty' && file_exists($view)) {
			// make sure the directory of the file is in the template dirs
			$fileDir = realpath(dirname($view));
			$dirs = (array)$this->Smarty->getTemplateDir();
			$found = false;
			foreach ($dirs as $dir) {
				if (realpath($dir) === $fileDir) {
					$found = true;
					break;
				}
			}
			if (!$found && $fileDir !== false) {
				$this->Smarty->addTemplateDir($fileDir);
			}
		}

		if (!$view) {
			// try to get the view name based upon the controller/action
			$view = $this->getViewFileName($this->controllerContext);
		}

		if (!$this->Smarty->templateExists($view)) {
			return $this->Smarty->fetch('string:' . $view);
		}

		// test for correct case-sensitivity
		if (isset($_SERVER['4WD_CONFIG']) && substr($view, 0, 7) != 'string:') {
			if (!file_exists($view)) {
				// try to get the file
				$dirs = (array)$this->Smarty->getTemplateDir();
				foreach ($dirs as $dir) {
					if (file_exists($dir . $view)) {
						$view = $dir . $view;
					}
				}
			}

			if (!glob($view . '*')) {
				$controller = $this->controllerContext->getRequest()->getControllerName();
				$action     = $this->controllerContext->getRequest()->getControllerActionName();

				throw new Exception('Template not found for ' . $controller . '->' . $action . "\nMaybe incorrect case of filename?");
			}
		}

		// check if the view was modified AFTER the extension was installed
		if (isset($_SERVER['4WD_CONFIG']) && strpos($view, 'vierwd_smarty/Resources/Private/Templates') !== false) {
			$viewModifiedTime = filemtime($view);
			$emConf = GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/ext_emconf.php');
			$extensionInstallTime = filemtime($emConf);
			if ($viewModifiedTime > $extensionInstallTime + 3600) {
				$viewFileName = str_replace(Environment::getProjectPath(), '', $view);
				while (ob_get_level() > 0) {
					ob_end_clean();
				}

				if (!headers_sent()) {
					header('Content-Type: text/html; charset=utf-8');
					header('Content-Encoding: identity');
					header_remove('Content-Length');
				}

				echo '<h1>Invalid Smarty Template change detected</h1>';
				echo '<p>A view within the Smarty template directory was changed instead of creating an override template within the website extension.<br>';
				echo 'View: <b>' . htmlspecialchars($viewFileName) . '</b><br>You cannot commit this file.</p>';
				echo '<p>To fix this error create a template within the website extension.</p>';
				echo '<p>To ignore this message, update the filemtime of the ext_emconf of the Smarty extension:</p>';
				echo '<pre>touch ' . htmlspecialchars($view) . '</pre>';
				exit;
			}
		}

		return $this->Smarty->fetch($view);
	}
}
