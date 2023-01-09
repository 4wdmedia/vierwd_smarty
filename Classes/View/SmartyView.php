<?php
declare(strict_types = 1);

namespace Vierwd\VierwdSmarty\View;

use Exception;
use Smarty;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

use Vierwd\VierwdSmarty\Resource\ExtResource;
use Vierwd\VierwdSmarty\View\Plugin\Block\FluidPlugin;
use Vierwd\VierwdSmarty\View\Plugin\Block\LinkActionPlugin;
use Vierwd\VierwdSmarty\View\Plugin\Block\TyposcriptPlugin;
use Vierwd\VierwdSmarty\View\Plugin\Functions\FlashMessagesPlugin;
use Vierwd\VierwdSmarty\View\Plugin\Functions\TranslatePlugin;
use Vierwd\VierwdSmarty\View\Plugin\Functions\TypolinkPlugin;
use Vierwd\VierwdSmarty\View\Plugin\Functions\UriActionPlugin;
use Vierwd\VierwdSmarty\View\Plugin\Functions\UriResourcePlugin;

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

class SmartyView implements ViewInterface {

	public ?Smarty $Smarty = null;

	public bool $hasTopLevelViewHelper = false;

	/**
	 * Pattern to be resolved for "@templateRoot" in the other patterns.
	 */
	protected string $templateRootPathPattern = '@packageResourcesPath/Private/Templates';

	/**
	 * Path(s) to the template root. If NULL, then $this->templateRootPathPattern will be used.
	 */
	protected ?array $templateRootPaths = null;

	protected ControllerContext $controllerContext;

	protected array $variables = [];

	protected ?ContentObjectRenderer $contentObject = null;

	protected ?ConfigurationManagerInterface $configurationManager = null;

	protected ?ImageService $imageService = null;

	protected ?TypoLinkCodecService $typoLinkCodecService = null;

	public function __construct(ConfigurationManagerInterface $configurationManager, ImageService $imageService, TypoLinkCodecService $typoLinkCodecService) {
		$this->configurationManager = $configurationManager;
		$this->imageService = $imageService;
		$this->typoLinkCodecService = $typoLinkCodecService;

		$this->controllerContext = GeneralUtility::makeInstance(ControllerContext::class);
	}

	public function setControllerContext(ControllerContext $controllerContext): void {
		$this->controllerContext = $controllerContext;
	}

	public function assign($key, $value): self {
		$this->variables[$key] = $value;
		return $this;
	}

	public function assignMultiple(array $values): self {
		foreach ($values as $key => $value) {
			$this->assign($key, $value);
		}
		return $this;
	}

	/**
	 * Set the root path(s) to the templates.
	 * If set, overrides the one determined from $this->templateRootPathPattern
	 *
	 * @param array $templateRootPaths Root path(s) to the templates. If set, overrides the one determined from $this->templateRootPathPattern
	 */
	public function setTemplateRootPaths(array $templateRootPaths): void {
		$this->templateRootPaths = $templateRootPaths;
	}

	/**
	 * Resolves the template root to be used inside other paths.
	 *
	 * @return array Path(s) to template root directory
	 */
	public function getTemplateRootPaths(): array {
		if ($this->templateRootPaths !== null) {
			return $this->templateRootPaths;
		}
		/** @var \TYPO3\CMS\Extbase\Mvc\Request $actionRequest */
		$actionRequest = $this->controllerContext->getRequest();
		return [str_replace(['@packageResourcesPath', '//'], [ExtensionManagementUtility::extPath($actionRequest->getControllerExtensionKey()) . 'Resources/', '/'], $this->templateRootPathPattern)];
	}

	/**
	 * get template root paths and resolve all relative paths and paths containing EXT:
	 */
	public function resolveTemplateRootPaths(): array {
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

		if (isset($GLOBALS['TSFE']) && !$GLOBALS['TSFE']->headerNoCache()) {
			$this->Smarty->setCacheLifetime(120);
			$this->Smarty->setCompileCheck(0);
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'] ?? null) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'] as $pluginDir) {
				$this->Smarty->addPluginsDir($pluginDir);
			}
		}

		assert($this->controllerContext !== null);

		$translatePlugin = new TranslatePlugin($this->controllerContext);
		$this->Smarty->registerPlugin('function', 'translate', $translatePlugin);

		$uriResourcePlugin = new UriResourcePlugin($this->controllerContext);
		$this->Smarty->registerPlugin('function', 'uri_resource', $uriResourcePlugin);

		$uriActionPlugin = new UriActionPlugin($this->controllerContext);
		$this->Smarty->registerPlugin('function', 'uri_action', $uriActionPlugin);

		$typolinkPlugin = new TypolinkPlugin($this->controllerContext);
		$this->Smarty->registerPlugin('function', 'typolink', $typolinkPlugin);

		$flashMessagesPlugin = new FlashMessagesPlugin($this->controllerContext);
		$this->Smarty->registerPlugin('function', 'flashMessages', $flashMessagesPlugin);

		$linkActionPlugin = new LinkActionPlugin($this->controllerContext);
		$this->Smarty->registerPlugin('block', 'link_action', $linkActionPlugin);

		$templateProcessor = new TemplatePreprocessor();
		$this->Smarty->registerFilter('pre', $templateProcessor);
		$this->Smarty->registerFilter('variable', 'Vierwd\\VierwdSmarty\\View\\clean');

		if ($this->configurationManager !== null) {
			$fluidPlugin = new FluidPlugin($this->controllerContext, $this->configurationManager);
			$this->Smarty->registerPlugin('block', 'fluid', $fluidPlugin);
		}

		if ($this->contentObject !== null) {
			$typoscriptPlugin = new TyposcriptPlugin($this->contentObject);
			$this->Smarty->registerPlugin('block', 'typoscript', $typoscriptPlugin);
		}

		// Resource type
		$this->Smarty->registerResource('EXT', new ExtResource());
	}

	public function initializeView(): void {
		if ($this->contentObject === null) {
			// initialize a new ContentObject
			$this->contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
			$this->contentObject->start([], '_NO_TABLE');
		}
		$this->createSmarty();
		assert($this->Smarty instanceof Smarty);
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
	 * @phpstan-return string
	 */
	public function render(string $view = '') {
		assert($this->Smarty instanceof Smarty);

		$this->Smarty->setTemplateDir($this->resolveTemplateRootPaths());

		if ($this->contentObject && !$this->contentObject->data && isset($this->variables['data'])) {
			$this->contentObject->data = $this->variables['data'];
		}

		$this->Smarty->assign($this->variables);
		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$pluginName = $this->controllerContext->getRequest()->getPluginName();

		if ($this->configurationManager !== null) {
			$typoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			$this->Smarty->assign('frameworkSettings', $typoScript);
		}

		$formPrefix = GeneralUtility::makeInstance(ExtensionService::class)->getPluginNamespace($extensionName, $pluginName);
		$this->Smarty->assign('formPrefix', $formPrefix);

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
			'typolinkService' => $this->typoLinkCodecService,
			'imageService' => $this->imageService,
			// 'settings' => $typoScript['settings'],
			'TSFE' => $GLOBALS['TSFE'] ?? null,
		];

		$this->Smarty->assign($templateVars);
		$settings = $this->Smarty->getTemplateVars('settings');
		$userVars = $settings['typoscript'] ?? [];
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
					header_remove('Content-Length');
					header_remove('Content-Encoding');
				}

				echo '<h1>Invalid Smarty Template change detected</h1>';
				echo '<p>A view within the Smarty template directory was changed instead of creating an override template within the website extension.<br>';
				echo 'View: <b>' . htmlspecialchars($viewFileName) . '</b><br>You cannot commit this file.</p>';
				echo '<p>To fix this error create a template within the website extension.</p>';
				echo '<p>To ignore this message, update the filemtime of the ext_emconf of the Smarty extension:</p>';
				echo '<pre>touch ' . htmlspecialchars($emConf) . '</pre>';
				exit;
			}
		}

		return $this->Smarty->fetch($view);
	}

}
