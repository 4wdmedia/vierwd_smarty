<?php

namespace Vierwd\VierwdSmarty\View;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

function clean($str) {
	if(is_scalar($str)) {
		$str = preg_replace('/&(?!#(?:[0-9]+|x[0-9A-F]+);?)/si', '&amp;', $str);
		$str = str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $str);

		return $str;
	} elseif($str === null) {
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

	$search = array_keys($replacements);
	$replace = array_values($replacements);

	$template = str_replace($search, $replace, $template);
	$template = '{strip}' . $template . '{/strip}';
	return $template;
}

class SmartyView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView {

	public $Smarty;

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
	protected $templateRootPaths = NULL;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Set the root path to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param string $templateRootPath Root path to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 * @return void
	 * @api
	 * @see setTemplateRootPaths()
	 */
	public function setTemplateRootPath($templateRootPath) {
		$this->setTemplateRootPaths(array($templateRootPath));
	}

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
	 * @return string Path to template root directory
	 * @deprecated since fluid 6.2, will be removed two versions later. Use getTemplateRootPaths() instead
	 */
	protected function getTemplateRootPath() {
		GeneralUtility::logDeprecatedFunction();
		$templateRootPaths = $this->getTemplateRootPaths();
		return array_shift($templateRootPaths);
	}

	/**
	 * Resolves the template root to be used inside other paths.
	 *
	 * @return array Path(s) to template root directory
	 */
	public function getTemplateRootPaths() {
		if ($this->templateRootPaths !== NULL) {
			return $this->templateRootPaths;
		}
		/** @var $actionRequest \TYPO3\CMS\Extbase\Mvc\Request */
		$actionRequest = $this->controllerContext->getRequest();
		return array(str_replace('@packageResourcesPath', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', $this->templateRootPathPattern));
	}

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	public function canRender(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext) {
		$this->setControllerContext($controllerContext);
		if ($controllerContext->getRequest()->getControllerObjectName() == 'Vierwd\\VierwdSmarty\\Controller\\SmartyController') {
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

		foreach ($this->getTemplateRootPaths() as $rootPath) {
			$fileName = str_replace('//', '/', $rootPath . '/' . $file);
			$fileName = GeneralUtility::getFileAbsFileName($fileName);
			if (file_exists($fileName)) {
				return $fileName;
			}
		}

		// no view found
		throw new \Exception('Template not found for '.$controller.'->'.$action);
	}

	public function initializeView() {
		$extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();

		if (!class_exists('Smarty')) {
			// use Composer's autoloader
			require_once PATH_site . 'vendor/autoload.php';
		}

		$this->Smarty = new \Smarty;

		$this->Smarty->setCacheLifetime(120);

		// setup Template and caching dirs
		$this->Smarty->setTemplateDir(array_map(function($pathName) {
			$pathName = str_replace('//', '/', $pathName);
			return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($pathName);
		}, $this->getTemplateRootPaths()));

		$extCacheDir = GeneralUtility::getFileAbsFileName('typo3temp/vierwd_smarty/');
		$this->Smarty->compile_dir = $extCacheDir . '/templates_c/' . $extensionKey . '/';
		$this->Smarty->cache_dir   = $extCacheDir . '/smarty/' . $extensionKey . '/';

		if (!is_dir($this->Smarty->cache_dir)) {
			GeneralUtility::mkdir_deep($this->Smarty->cache_dir, '');
		}
		if (!is_dir($this->Smarty->compile_dir)) {
			GeneralUtility::mkdir_deep($this->Smarty->compile_dir, '');
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs']) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'] as $pluginDir) {
				$this->Smarty->addPluginsDir($pluginDir);
			}
		}

		$this->Smarty->registerPlugin('function', 'translate', array($this, 'smarty_translate'));
		$this->Smarty->registerPlugin('function', 'uri_resource', array($this, 'smarty_uri_resource'));
		$this->Smarty->registerPlugin('function', 'uri_action', array($this, 'smarty_uri_action'));
		$this->Smarty->registerPlugin('function', 'typolink', array($this, 'smarty_helper_typolink'));
		$this->Smarty->registerPlugin('modifier', 'typolink', array($this, 'smarty_helper_typolink_url'));
		$this->Smarty->registerPlugin('function', 'flashMessages', array($this, 'smarty_flashMessages'));

		$this->Smarty->registerPlugin('block', 'link_action', array($this, 'smarty_link_action'));

		$this->Smarty->registerFilter('pre', 'Vierwd\\VierwdSmarty\\View\\strip');
		$this->Smarty->registerFilter('variable', 'Vierwd\\VierwdSmarty\\View\\clean');

		// fluid
		$this->Smarty->registerPlugin('block', 'fluid', array($this, 'smarty_fluid'));

		// Typoscript filters
		$this->Smarty->registerPlugin('block', 'typoscript', array($this, 'smarty_typoscript'));

		// custom functions
		$this->Smarty->registerPlugin('function', 'email', array($this, 'smarty_email'));
		$this->Smarty->registerPlugin('function', 'pagebrowser', array($this, 'smarty_pagebrowser'));

		$this->Smarty->registerPlugin('modifier', 'nl2p', array($this, 'smarty_nl2p'));
	}

	/**
	 * translate function for smarty templates.
	 * copy from fluid viewhelper
	 */
	public function smarty_translate($params, $smarty) {
		$request = $this->controllerContext->getRequest();
		$params = $params + array(
			'default' => null,
			'htmlEscape' => true,
			'arguments' => null,
			'extensionName' => $request->getControllerExtensionName(),
		);
		extract($params);

		$value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, $extensionName, $arguments);
		if ($value === NULL) {
			$value = $default;
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

	public function smarty_uri_resource($params, $smarty) {
		$params = $params + array(
			'extensionName' => NULL,
			'absolute' => FALSE,
		);
		extract($params);

		if ($extensionName === NULL) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		$uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = GeneralUtility::getFileAbsFileName($uri);
		$uri = substr($uri, strlen(PATH_site));

		if (TYPO3_MODE === 'BE' && $absolute === FALSE) {
			$uri = '../' . $uri;
		}

		if ($absolute === TRUE) {
			$uri = $this->controllerContext->getRequest()->getBaseURI() . $uri;
		}

		return $uri;
	}

	public function smarty_uri_action($params, $smarty) {
		$params = $params + array(
			'action' => NULL,
			'arguments' => array(),
			'controller' => NULL,
			'extensionName' => NULL,
			'pluginName' => NULL,
			'pageUid' => NULL,
			'pageType' => 0,
			'noCache' => FALSE,
			'noCacheHash' => FALSE,
			'section' => '',
			'format' => '',
			'linkAccessRestrictedPages' => FALSE,
			'additionalParams' => array(),
			'absolute' => FALSE,
			'addQueryString' => FALSE,
			'argumentsToBeExcludedFromQueryString' => array(),
		);
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

		$defaultUrlParams = array(
			'action' => NULL,
			'arguments' => array(),
			'controller' => NULL,
			'extensionName' => NULL,
			'pluginName' => NULL,
			'pageUid' => NULL,
			'pageType' => 0,
			'noCache' => FALSE,
			'noCacheHash' => FALSE,
			'section' => '',
			'format' => '',
			'linkAccessRestrictedPages' => FALSE,
			'additionalParams' => array(),
			'absolute' => FALSE,
			'addQueryString' => FALSE,
			'argumentsToBeExcludedFromQueryString' => array(),
		);

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
	 * @param array $argumentsToBeExcludedFromQueryString arguments to be removed from the URI. Only active if $addQueryString = TRUE
	 * @return string Rendered page URI
	 */
	public function smarty_helper_typolink($params, $smarty) {
		$pageUid = $this->getParam($params, 'pageUid', null);
		$additionalParams = $this->getParam($params, 'additionalParams', array());
		$pageType = $this->getParam($params, 'pageType', 0);
		$noCache = $this->getParam($params, 'noCache');
		$noCacheHash = $this->getParam($params, 'noCacheHash');
		$linkAccessRestrictedPages = $this->getParam($params, 'linkAccessRestrictedPages');
		$absolute = $this->getParam($params, 'absolute');
		$section = $this->getParam($params, 'section');
		$addQueryString = $this->getParam($params, 'addQueryString');
		$argumentsToBeExcludedFromQueryString = $this->getParam($params, 'argumentsToBeExcludedFromQueryString', array());

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
		$cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		return $cObj->getTypoLink_URL($parameter);
	}

	public function smarty_flashMessages($params, $smarty) {
		$params = $params + array(
			'renderMode' => 'ul',
			'class' => 'typo3-messages',
		);
		extract($params);

		$flashMessages = $this->controllerContext->getFlashMessageContainer()->getAllMessagesAndFlush();
		if ($flashMessages === NULL || count($flashMessages) === 0) {
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

	protected function getParam($params, $key, $default = false) {
		return !empty($params[$key]) ? $params[$key] : $default;
	}

	public function smarty_fluid($params, $content, $smarty, &$repeat) {
		if (!isset($content)) {
			return;
		}

		$data = isset($params['data']) ? $params['data'] : array();
		unset($params['data']);
		$data = $params + $data + $smarty->getTemplateVars();

		$fluidView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$fluidView->setControllerContext($this->controllerContext);
		$fluidView->assignMultiple($data);
		$fluidView->setTemplateSource($content);

		$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if ($configuration['view']) {
			$layoutRootPaths = array();
			if ($configuration['view']['layoutRootPaths']) {
				$layoutRootPaths = $configuration['view']['layoutRootPaths'];
			} else if ($configuration['view']['layoutRootPath']) {
				$layoutRootPaths = array($configuration['view']['layoutRootPath']);
			}
			$layoutRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $layoutRootPaths);
			$fluidView->setLayoutRootPaths($layoutRootPaths);

			// Partials
			$partialRootPaths = array();
			if ($configuration['view']['partialRootPaths']) {
				$partialRootPaths = $configuration['view']['partialRootPaths'];
			} else if ($configuration['view']['partialRootPath']) {
				$partialRootPaths = array($configuration['view']['partialRootPath']);
			}
			$partialRootPaths = array_map(function($path) {
				return GeneralUtility::getFileAbsFileName($path);
			}, $partialRootPaths);
			$fluidView->setPartialRootPaths($partialRootPaths);
		}

		return $fluidView->render();
	}

	public function smarty_typoscript($params, $content, $smarty, &$repeat) {
		global $TSFE;

		if (!isset($content)) {
			return;
		}

		$data = isset($params['data']) ? $params['data'] : array();
		unset($params['data']);
		$data = $params + $data;

		$table = isset($data['table']) ? $data['table'] : '_NO_TABLE';
		unset($data['table']);

		$cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$cObj->setParent($this->contentObject->data, $this->contentObject->currentRecord);
		$cObj->currentRecordNumber = $this->contentObject->currentRecordNumber;
		$cObj->currentRecordTotal = $this->contentObject->currentRecordTotal;
		$cObj->parentRecordNumber = $this->contentObject->parentRecordNumber;
		if ($table != '_NO_TABLE') {
			#$data['uid'] = 0;
			$data['_MIGRATED'] = false;
		}
		$cObj->start($data, $table);
		if ($table != '_NO_TABLE') {
			$cObj->data['uid'] = 0;
		}

		// $cObj->setCurrentVal($dataValues[$key][$valueKey]);

		$tsparserObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');

		if (is_array($TSFE->tmpl->setup)) {
			foreach ($TSFE->tmpl->setup as $tsObjectKey => $tsObjectValue) {
				// do not copy int-keys
				if ($tsObjectKey !== intval($tsObjectKey)) {
					$tsparserObj->setup[$tsObjectKey] = $tsObjectValue;
				}
			}
		}

		$tsparserObj->parse($content);

		// save current typoscript setup and change to modified setup
		$oldSetup = $TSFE->tmpl->setup;
		$TSFE->tmpl->setup = $tsparserObj->setup;

		$content = $cObj->cObjGet($tsparserObj->setup, 'COA');

		// reset typoscript
		$TSFE->tmpl->setup = $oldSetup;

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

		$contentObject = $this->configurationManager->getContentObject();

		$conf = array(
			'parameter' => $parameter,
			'ATagParams' => $ATagParams,
		);

		return $contentObject->typoLink($label, $conf);
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
		$conf = array(
			'pageParameterName' => $prefix . '|page',
			'numberOfPages' => $numberOfPages,
			'extraQueryString' => '&' . http_build_query(array($prefix => $params)),
		) + $conf;

		// Get page browser
		$cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		$cObj->start(array(), '');
		return $cObj->cObjGetSingle('USER', $conf);
	}

	public function render($view = '') {
		// setup TypoScript
		$this->contentObject = $this->configurationManager->getContentObject();

		$this->Smarty->assign($this->variables);
		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$pluginName = $this->controllerContext->getRequest()->getPluginName();

		$typoScript = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		$formPrefix = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Service\\ExtensionService')->getPluginNamespace($extensionName, $pluginName);

		$extensionKey = $this->controllerContext->getRequest()->getControllerExtensionKey();
		$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey);

		$templateVars = array(
			'cObj' => $this->contentObject,
			'extensionPath' => $extPath,
			'extensionName' => $extensionName,
			'pluginName' => $pluginName,
			'controllerName' => $this->controllerContext->getRequest()->getControllerName(),
			'actionName' => $this->controllerContext->getRequest()->getControllerActionName(),
			'context' => $this->controllerContext,
			'request' => $this->controllerContext->getRequest(),
			'formPrefix' => $formPrefix,
			//'settings' => $typoScript['settings'],
			'frameworkSettings' => $typoScript,
			'TSFE' => $GLOBALS['TSFE'],
		);
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
			$dirs = (array)$this->Smarty->template_dir;
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


		// test for correct case-sensitivity
		if (isset($_SERVER['4WD_CONFIG']) && substr($view, 0, 7) != 'string:') {
			if (!file_exists($view)) {
				// try to get the file
				$dirs = (array)$this->Smarty->template_dir;
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

		return $this->Smarty->fetch($view);
	}
}