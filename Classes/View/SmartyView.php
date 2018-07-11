<?php

namespace Vierwd\VierwdSmarty\View;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Vierwd\VierwdSmarty\Resource\ExtResource;

function clean($str) {
	if (is_scalar($str)) {
		$str = preg_replace('/&(?!#(?:[0-9]+|x[0-9A-F]+);?)/si', '&amp;', $str);
		// replace html-characters
		$str = str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], $str);

		return $str;
	} else if ($str === null) {
		return '';
	} else {
		throw new \Exception('$str needs to be scalar value');
	}
}

function strip($template) {
	static $replacements = [
		'{typoscript' => '{/strip}{typoscript',
		'{/typoscript}' => '{/typoscript}{strip}',
		'{pre}' => '{/strip}',
		'{/pre}' => '{strip}',
	];

	// Smarty v3.1.32 changed handling of strip and comments.
	// Whitespace after comments is not stripped.
	// @see https://github.com/smarty-php/smarty/issues/436
	// We do not want this behaviour. That's why we strip all comments
	$template = preg_replace('-\{\*.*?\*\}-', '', $template);

	$search = array_keys($replacements);
	$replace = array_values($replacements);

	$template = str_replace($search, $replace, $template);
	$template = '{strip}' . $template . '{/strip}';
	return $template;
}

/**
 * phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
 */
class SmartyView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView {

	public $Smarty;

	public $hasTopLevelViewHelper = false;

	/**
	 * captured sections
	 *
	 * @var array
	 */
	static public $sections = [];

	/**
	 * Pattern to be resolved for "@templateRoot" in the other patterns.
	 *
	 * @var string
	 */
	protected $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	/**
	 * Path(s) to the template root. If NULL, then $this->templateRootPathPattern will be used.
	 *
	 * @var array
	 */
	protected $templateRootPaths = null;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @TYPO3\CMS\Extbase\Annotation\Inject
	 */
	protected $objectManager;

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
		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		return [str_replace(['@packageResourcesPath', '//'], [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', '/'], $this->templateRootPathPattern)];
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

	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 */
	public function setContentObject(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);
		if ($controllerContext->getRequest()->getControllerObjectName() == \Vierwd\VierwdSmarty\Controller\SmartyController::class) {
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
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function getViewFileName(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
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
		throw new \Exception('Template not found for '.$controller.'->'.$action);
	}

	protected function createSmarty() {
		if ($this->Smarty) {
			return;
		}

		if (!class_exists('Smarty', true) && file_exists($smartyClassFile = GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/Resources/Private/Smarty/libs/Smarty.class.php'))) {
			// Smarty was not autoloadable, might occur when installed via TER
			require_once $smartyClassFile;
		}

		$this->Smarty = new \Smarty;

		if ($GLOBALS['TSFE'] && !$GLOBALS['TSFE']->headerNoCache()) {
			$this->Smarty->setCacheLifetime(120);
			$this->Smarty->setCompileCheck(false);
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
		$this->Smarty->registerPlugin('block', 'fsection',    [$this, 'smarty_fsection']);

		$this->Smarty->registerFilter('pre', 'Vierwd\\VierwdSmarty\\View\\strip');
		$this->Smarty->registerFilter('variable', 'Vierwd\\VierwdSmarty\\View\\clean');

		// fluid
		$this->Smarty->registerPlugin('block', 'fluid', [$this, 'smarty_fluid']);

		// Typoscript filters
		$this->Smarty->registerPlugin('block', 'typoscript', [$this, 'smarty_typoscript']);

		// custom functions
		$this->Smarty->registerPlugin('function', 'email',       [$this, 'smarty_email']);
		$this->Smarty->registerPlugin('function', 'pagebrowser', [$this, 'smarty_pagebrowser']);

		$this->Smarty->registerPlugin('modifier', 'nl2p', [$this, 'smarty_nl2p']);
		// phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma

		// Resource type
		$this->Smarty->registerResource('EXT', new ExtResource);
	}

	public function initializeView() {
		$this->createSmarty();
		if (!$this->hasTopLevelViewHelper) {
			$this->Smarty->clearAllAssign();
		}

		$extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();

		// setup compile and caching dirs
		$extCacheDir = GeneralUtility::getFileAbsFileName('typo3temp/Cache/vierwd_smarty/');
		$this->Smarty->compile_dir = $extCacheDir . '/templates_c/' . $extensionKey . '/';
		$this->Smarty->cache_dir   = $extCacheDir . '/cache/' . $extensionKey . '/';

		if (!is_dir($this->Smarty->cache_dir)) {
			GeneralUtility::mkdir_deep($this->Smarty->cache_dir, '');
		}
		if (!is_dir($this->Smarty->compile_dir)) {
			GeneralUtility::mkdir_deep($this->Smarty->compile_dir, '');
		}
	}

	/**
	 * translate function for smarty templates.
	 * copy from fluid viewhelper
	 */
	public function smarty_translate($params, $smarty) {
		$request = $this->controllerContext->getRequest();
		$params = $params + [
			'default' => null,
			'htmlEscape' => true,
			'arguments' => null,
			'extensionName' => $request->getControllerExtensionName(),
		];
		extract($params);

		$value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, $extensionName, $arguments);
		if ($value === null) {
			$value = $default;
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

	public function smarty_uri_resource($params, $smarty) {
		$params = $params + [
			'path' => null,
			'extensionName' => null,
			'absolute' => false,
		];
		extract($params);

		if ($extensionName === null) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		$uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = GeneralUtility::getFileAbsFileName($uri);
		$uri = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($uri);

		if (TYPO3_MODE === 'BE' && $absolute === false && $uri !== false) {
			$uri = '../' . $uri;
		}

		if ($absolute === true) {
			$uri = $this->controllerContext->getRequest()->getBaseURI() . $uri;
		}

		return $uri;
	}

	public function smarty_uri_action($params, $smarty) {
		$params = $params + [
			'action' => null,
			'arguments' => [],
			'controller' => null,
			'extensionName' => null,
			'pluginName' => null,
			'pageUid' => null,
			'pageType' => 0,
			'noCache' => false,
			'noCacheHash' => false,
			'section' => '',
			'format' => '',
			'linkAccessRestrictedPages' => false,
			'additionalParams' => [],
			'absolute' => false,
			'addQueryString' => false,
			'argumentsToBeExcludedFromQueryString' => [],
		];
		extract($params);

		$uriBuilder = $this->controllerContext->getUriBuilder();
		$uri = $uriBuilder
			->reset()
			->setTargetPageUid($pageUid)
			->setTargetPageType($pageType)
			->setNoCache($noCache)
			->setUseCacheHash(!$noCacheHash)
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
	public function smarty_link_action($params, $content, $smarty, &$repeat) {
		if (!isset($content)) {
			return;
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
			'noCacheHash' => false,
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
			$params = $params + $defaultUrlParams;
			extract($params);

			$uriBuilder = $this->controllerContext->getUriBuilder();
			$uri = $uriBuilder
				->reset()
				->setTargetPageUid($pageUid)
				->setTargetPageType($pageType)
				->setNoCache($noCache)
				->setUseCacheHash(!$noCacheHash)
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

		foreach ($attributes as $name => $value) {
			$attr .= ' '.$name.'="'.htmlspecialchars($value).'"';
		}
		return '<a'.$attr.'>' . $content . '</a>';
	}

	/**
	 * @param integer $page target PID
	 * @param array $additionalParams query parameters to be attached to the resulting URI
	 * @param integer $pageType type of the target page. See typolink.parameter
	 * @param boolean $noCache set this to disable caching for the target page. You should not need this.
	 * @param boolean $noCacheHash set this to supress the cHash query parameter created by TypoLink. You should not need this.
	 * @param string $section the anchor to be added to the URI
	 * @param boolean $linkAccessRestrictedPages If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.
	 * @param boolean $absolute If set, the URI of the rendered link is absolute
	 * @param boolean $addQueryString If set, the current query parameters will be kept in the URI
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = true
	 * @return string Rendered page URI
	 */
	public function smarty_helper_typolink($params, $smarty) {
		$pageUid = $this->getParam($params, 'pageUid', null);
		$additionalParams = $this->getParam($params, 'additionalParams', []);
		$pageType = $this->getParam($params, 'pageType', 0);
		$noCache = $this->getParam($params, 'noCache');
		$noCacheHash = $this->getParam($params, 'noCacheHash');
		$linkAccessRestrictedPages = $this->getParam($params, 'linkAccessRestrictedPages');
		$absolute = $this->getParam($params, 'absolute');
		$section = $this->getParam($params, 'section');
		$addQueryString = $this->getParam($params, 'addQueryString');
		$argumentsToBeExcludedFromQueryString = $this->getParam($params, 'argumentsToBeExcludedFromQueryString', []);

		$uriBuilder = $this->controllerContext->getUriBuilder();
		$uri = $uriBuilder
			->setTargetPageUid($pageUid)
			->setTargetPageType($pageType)
			->setNoCache($noCache)
			->setUseCacheHash(!$noCacheHash)
			->setSection($section)
			->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
			->setArguments($additionalParams)
			->setCreateAbsoluteUri($absolute)
			->setAddQueryString($addQueryString)
			->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
			->build();

		return $uri;
	}

	public function smarty_helper_typolink_url($parameter) {
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		return $cObj->getTypoLink_URL($parameter);
	}

	public function smarty_flashMessages($params, $smarty) {
		$params = $params + [
			'renderMode' => 'ul',
			'class' => 'typo3-messages',
		];
		extract($params);

		if (method_exists($this->controllerContext, 'getFlashMessageQueue')) {
			$flashMessages = $this->controllerContext->getFlashMessageQueue()->getAllMessagesAndFlush();
		} else {
			$flashMessages = $this->controllerContext->getFlashMessageContainer()->getAllMessagesAndFlush();
		}

		if ($flashMessages === null || count($flashMessages) === 0) {
			return '';
		}

		if ($renderMode != 'div') {
			$renderMode = 'ul';
		}

		$content = '<' . $renderMode;
		if ($class) {
			$content .= ' class="' . htmlspecialchars($class) .'"';
		}
		$content .= '>';

		foreach ($flashMessages as $singleFlashMessage) {
			if ($renderMode == 'ul') {
				$content .= '<li>' . htmlspecialchars($singleFlashMessage->getMessage()) . '</li>';
			} else {
				$content .= $singleFlashMessage->render();
			}
		}

		$content .= '</' . $renderMode . '>';

		return $content;
	}

	public function smarty_svg($params, $smarty) {
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$svgObject = GeneralUtility::makeInstance(\Vierwd\VierwdBase\Frontend\ContentObject\ScalableVectorGraphicsContentObject::class, $cObj);
		return $svgObject->render($params);
	}


	protected function getParam($params, $key, $default = false) {
		return !empty($params[$key]) ? $params[$key] : $default;
	}

	public function smarty_fluid($params, $content, $smarty, &$repeat) {
		if (!isset($content)) {
			return;
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data + $smarty->getTemplateVars();

		$fluidView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$fluidView->setControllerContext($this->controllerContext);
		$fluidView->assignMultiple($data);
		$fluidView->setTemplateSource($content);

		$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
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

	public function smarty_fsection($params, $content, $smarty, &$repeat) {
		if (!isset($params['name'])) {
			trigger_error('missing name for fsection');
			return '';
		}

		if (!isset($content)) {
			return '';
		}

		$name = $params['name'];
		self::$sections[$name] = $content;

		return '';
	}

	public function smarty_typoscript($params, $content, $smarty, &$repeat) {
		if (!isset($content)) {
			return;
		}

		$data = isset($params['data']) ? $params['data'] : [];
		unset($params['data']);
		$data = $params + $data;

		$table = isset($data['table']) ? $data['table'] : '_NO_TABLE';
		unset($data['table']);

		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->setParent($this->contentObject->data, $this->contentObject->currentRecord);
		$cObj->currentRecordNumber = $this->contentObject->currentRecordNumber;
		$cObj->currentRecordTotal = $this->contentObject->currentRecordTotal;
		$cObj->parentRecordNumber = $this->contentObject->parentRecordNumber;
		if ($table != '_NO_TABLE') {
			$data['_MIGRATED'] = false;
		}
		$cObj->start($data, $table);

		// $cObj->setCurrentVal($dataValues[$key][$valueKey]);

		$tsparserObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);

		if (is_array($GLOBALS['TSFE']->tmpl->setup)) {
			foreach ($GLOBALS['TSFE']->tmpl->setup as $tsObjectKey => $tsObjectValue) {
				// do not copy int-keys
				if ($tsObjectKey !== intval($tsObjectKey) && $tsObjectKey !== intval($tsObjectKey) . '.') {
					$tsparserObj->setup[$tsObjectKey] = $tsObjectValue;
				}
			}
		}

		$tsparserObj->parse($content);

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

	public function smarty_nl2p($content) {
		$content = trim($content);
		if (!$content) {
			return '';
		}

		return '<p>' . nl2br(preg_replace('/\n{2,}/', '</p><p>', str_replace("\r", '', $content))) . '</p>';
	}

	public function smarty_email($params, $smarty) {
		$address = $this->getParam($params, 'address');
		$label = $this->getParam($params, 'label', $address);
		$parameter = $this->getParam($params, 'parameter', $address . ' - mail');
		$ATagParams = $this->getParam($params, 'ATagParams');

		$conf = [
			'parameter' => $parameter,
			'ATagParams' => $ATagParams,
		];

		return $this->contentObject->typoLink($label, $conf);
	}

	public function smarty_pagebrowser($params, $smarty) {
		$numberOfPages = $params['numberOfPages'];

		// Get default configuration
		$conf = (array)$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagebrowse_pi1.'];

		// Modify this configuration
		$prefix = $this->Smarty->getTemplateVars('formPrefix');
		$params = $this->controllerContext->getRequest()->getArguments();
		// for search
		unset($params['search']['pointer']);
		unset($params['page']);
		$conf = [
			'pageParameterName' => $prefix . '|page',
			'numberOfPages' => $numberOfPages,
			'extraQueryString' => '&' . http_build_query([$prefix => $params]),
		] + $conf;

		// Get page browser
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$cObj->start([], '');
		return $cObj->cObjGetSingle('USER', $conf);
	}

	public function render($view = '') {
		$this->Smarty->setTemplateDir($this->resolveTemplateRootPaths());

		if (!$this->contentObject->data && $this->variables['data']) {
			$this->contentObject->data = $this->variables['data'];
		}

		$this->Smarty->assign($this->variables);
		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$pluginName = $this->controllerContext->getRequest()->getPluginName();

		if ($this->configurationManager) {
			$typoScript = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			$this->Smarty->assign('frameworkSettings', $typoScript);
		}

		if ($this->objectManager) {
			$formPrefix = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Service\\ExtensionService')->getPluginNamespace($extensionName, $pluginName);
			$this->Smarty->assign('formPrefix', $formPrefix);
		}

		$extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();
		if ($extensionKey) {
			$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey);
		} else {
			$extPath = '';
		}

		$templateVars = [
			'cObj' => $this->contentObject,
			'data' => $this->contentObject->data,
			'extensionPath' => $extPath,
			'extensionName' => $extensionName,
			'pluginName' => $pluginName,
			'controllerName' => $this->controllerContext->getRequest()->getControllerName(),
			'actionName' => $this->controllerContext->getRequest()->getControllerActionName(),
			'context' => $this->controllerContext,
			'request' => $this->controllerContext->getRequest(),
			//'settings' => $typoScript['settings'],
			'TSFE' => $GLOBALS['TSFE'],
		];
		if (class_exists('TYPO3\\CMS\\Frontend\\Service\\TypoLinkCodecService')) {
			// TYPO3 >= 7
			$templateVars['typolinkService'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Service\\TypoLinkCodecService');
		}
		$this->Smarty->assign($templateVars);
		$settings = $this->Smarty->getTemplateVars('settings');
		$userVars = $settings['typoscript'];
		if ($userVars) {
			$overwrite = array_intersect_key($templateVars, $userVars);
			if ($overwrite) {
				throw new \Exception('Overwriting smarty template vars with own variables: ' . implode(',', array_keys($overwrite)));
			}
			$this->Smarty->assign($userVars);
		}

		if ($extensionName == 'VierwdSmarty' && file_exists($view)) {
			// make sure the directory of the file is in the template dirs
			$fileDir = realpath(dirname($view));
			$dirs = (array)$this->Smarty->getTemplateDir();
			$found = false;
			foreach ($dirs as $dir) {
				if (realpath($dir) == $fileDir) {
					$found = true;
					break;
				}
			}
			if (!$found) {
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

			if (!glob($view.'*')) {
				$controller = $this->controllerContext->getRequest()->getControllerName();
				$action     = $this->controllerContext->getRequest()->getControllerActionName();

				throw new \Exception('Template not found for '.$controller.'->'.$action."\nMaybe incorrect case of filename?");
			}
		}

		// check if the view was modified AFTER the extension was installed
		if (isset($_SERVER['4WD_CONFIG']) && strpos($view, 'vierwd_smarty/Resources/Private/Templates') !== false) {
			$viewModifiedTime = filemtime($view);
			$emConf = GeneralUtility::getFileAbsFileName('EXT:vierwd_smarty/ext_emconf.php');
			$extensionInstallTime = filemtime($emConf);
			if ($viewModifiedTime > $extensionInstallTime + 3600) {
				$viewFileName = str_replace(PATH_site, '', $view);
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
