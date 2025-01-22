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
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

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

class SmartyView extends AbstractTemplateView {

	public ?Smarty $Smarty = null;

	public bool $hasTopLevelViewHelper = false;

	protected ?ContentObjectRenderer $contentObject = null;

	protected ConfigurationManagerInterface $configurationManager;

	protected ImageService $imageService;

	protected RequestInterface $request;
	protected UriBuilder $uriBuilder;

	public function __construct(ConfigurationManagerInterface $configurationManager, ImageService $imageService) {
		parent::__construct();
		$this->configurationManager = $configurationManager;
		$this->imageService = $imageService;

		$this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

		$extbaseAttribute = new ExtbaseRequestParameters();
		$extbaseAttribute->setPluginName('pi1');
		$extbaseAttribute->setControllerExtensionName('VierwdSmarty');
		$extbaseAttribute->setControllerName('Smarty');
		$extbaseAttribute->setControllerActionName('render');
		$request = $GLOBALS['TYPO3_REQUEST']->withAttribute('extbase', $extbaseAttribute);
		$request = GeneralUtility::makeInstance(Request::class, $request);

		$this->setRequest($request);
	}

	public function setContentObject(?ContentObjectRenderer $contentObject): void {
		$this->contentObject = $contentObject;
	}

	public function setRequest(RequestInterface $request): void {
		$this->request = $request;
		$this->uriBuilder->setRequest($request);
		if ($this->baseRenderingContext instanceof RenderingContext) {
			$this->baseRenderingContext->setRequest($request);
		}
	}

	public function getRequest(): RequestInterface {
		return $this->request;
	}

	public function getUriBuilder(): UriBuilder {
		return $this->uriBuilder;
	}

	protected function createSmarty(): void {
		if ($this->Smarty !== null) {
			return;
		}

		$this->Smarty = new Smarty();

		if (!$this->request->getAttribute('noCache')) {
			$this->Smarty->setCacheLifetime(120);
			$this->Smarty->setCompileCheck(0);
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'] ?? null) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vierwd_smarty']['pluginDirs'] as $pluginDir) {
				$this->Smarty->addPluginsDir($pluginDir);
			}
		}

		$translatePlugin = new TranslatePlugin($this->request);
		$this->Smarty->registerPlugin('function', 'translate', $translatePlugin);

		$uriResourcePlugin = new UriResourcePlugin($this->request);
		$this->Smarty->registerPlugin('function', 'uri_resource', $uriResourcePlugin);

		$uriActionPlugin = new UriActionPlugin($this->uriBuilder);
		$this->Smarty->registerPlugin('function', 'uri_action', $uriActionPlugin);

		$typolinkPlugin = new TypolinkPlugin($this->uriBuilder);
		$this->Smarty->registerPlugin('function', 'typolink', $typolinkPlugin);

		$flashMessagesPlugin = new FlashMessagesPlugin($this->baseRenderingContext);
		$this->Smarty->registerPlugin('function', 'flashMessages', $flashMessagesPlugin);

		$linkActionPlugin = new LinkActionPlugin($this->uriBuilder);
		$this->Smarty->registerPlugin('block', 'link_action', $linkActionPlugin);

		$templateProcessor = new TemplatePreprocessor();
		$this->Smarty->registerFilter('pre', $templateProcessor);
		$this->Smarty->registerFilter('variable', 'Vierwd\\VierwdSmarty\\View\\clean');

		$fluidPlugin = new FluidPlugin($this->baseRenderingContext, $this->configurationManager);
		$this->Smarty->registerPlugin('block', 'fluid', $fluidPlugin);

		if ($this->contentObject !== null) {
			$typoscriptPlugin = new TyposcriptPlugin($this->contentObject);
			$this->Smarty->registerPlugin('block', 'typoscript', $typoscriptPlugin);
		}

		// Resource type
		$this->Smarty->registerResource('EXT', new ExtResource());
	}

	public function injectSettings(): void {
		$this->initializeView();
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

		$extensionKey = $this->request->getControllerExtensionKey();

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
	 * Overwrite renderSection of AbstractTemplateView.
	 */
	public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false): string {
		return '';
	}

	/**
	 * @phpstan-return string
	 */
	public function render($view = '') {
		$renderingContext = $this->getRenderingContext();
		assert($renderingContext instanceof RenderingContext);
		$templatePaths = $renderingContext->getTemplatePaths();
		$templatePaths->setFormat('tpl');

		assert($this->Smarty instanceof Smarty);

		$this->Smarty->setTemplateDir(array_reverse($templatePaths->getTemplateRootPaths()));

		$variables = (array)$renderingContext->getVariableProvider()->getAll();

		if ($this->contentObject && !$this->contentObject->data && isset($variables['data'])) {
			$this->contentObject->data = $variables['data'];
		}

		$this->Smarty->assign($variables);
		$extensionName = $this->request->getControllerExtensionName();
		$pluginName = $this->request->getPluginName();
		$controllerName = $renderingContext->getControllerName();
		$controllerAction = $renderingContext->getControllerAction();

		if ($this->configurationManager !== null) {
			$typoScript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
			$this->Smarty->assign('frameworkSettings', $typoScript);
		}

		$formPrefix = GeneralUtility::makeInstance(ExtensionService::class)->getPluginNamespace($extensionName, $pluginName);
		$this->Smarty->assign('formPrefix', $formPrefix);

		$extensionKey = $this->request->getControllerExtensionKey();
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
			'controllerName' => $controllerName,
			'actionName' => $controllerAction,
			'renderingContext' => $this->baseRenderingContext,
			'request' => $this->request,
			'context' => GeneralUtility::makeInstance(Context::class),
			'siteLanguage' => $siteLanguage,
			'typo3Request' => $request,
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

		if ($extensionName == 'VierwdSmarty' && $view && file_exists($view)) {
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
			$view = $templatePaths->resolveTemplateFileForControllerAndActionAndFormat($controllerName, $controllerAction);
		}

		if (!$view) {
			throw new InvalidTemplateResourceException('Could not find Template', 1673272709);
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
				$controller = $this->request->getControllerName();
				$action     = $this->request->getControllerActionName();

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

		$errorReporting = error_reporting();
		error_reporting($errorReporting & ~(E_WARNING | E_NOTICE | E_USER_DEPRECATED));
		$result = $this->Smarty->fetch($view);
		error_reporting($errorReporting);
		return $result;
	}

}
